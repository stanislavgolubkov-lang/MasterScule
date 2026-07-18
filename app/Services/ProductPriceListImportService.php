<?php

namespace App\Services;

use App\Jobs\ProcessExternalPriceListRowJob;
use App\Jobs\ProcessPriceListRowJob;
use App\Jobs\PrepareAndPublishParserDraftJob;
use App\Models\Product;
use App\Models\ProductParserBatch;
use App\Models\ProductParserItem;
use App\Models\ProductParserSource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductPriceListImportService
{
    public function __construct(
        private ProductPriceListReader $reader,
        private ProductCategoryDetector $categoryDetector,
        private ProductCategoryResolverService $categoryResolver,
        private ProductParserContentBuilder $contentBuilder,
        private ProductTranslationService $translation,
        private ProductSearchService $search,
        private ProductImageCollectorService $imageCollector,
        private ProductParserItemPreparationService $preparation,
        private ProductDraftService $drafts,
    ) {}

    public function dryRun(ProductParserBatch $batch): void
    {
        $this->run($batch, true, true);
    }

    public function import(ProductParserBatch $batch): void
    {
        $this->run($batch, false, false);
    }

    public function queueImport(ProductParserBatch $batch): void
    {
        $this->run($batch, false, true);
    }

    private function run(ProductParserBatch $batch, bool $dryRun, bool $queueRows = false): void
    {
        $options = $batch->options_json ?: [];
        if ($queueRows) {
            $options['staging_complete'] = false;
        }

        $batch->forceFill([
            'status' => 'processing',
            'started_at' => $batch->started_at ?: now(),
            'finished_at' => null,
            'options_json' => $options,
        ])->save();
        $batch->addLog($dryRun ? 'Price list dry-run started' : 'Price list import started', [
            'file' => $batch->file_name,
            'supplier' => $batch->supplier_name,
        ]);

        try {
            $path = Storage::disk('local')->path((string) $batch->file_path);
            $parsed = $this->reader->stream($path, (string) $batch->file_type);
        } catch (Throwable $e) {
            $batch->forceFill([
                'status' => 'failed',
                'error_rows' => 1,
                'finished_at' => now(),
            ])->save();
            $batch->addLog('Price list parsing failed', ['error' => $e->getMessage()]);

            return;
        }

        $hasStockColumn = array_key_exists('stock', $parsed['mapping'] ?? []);

        $rowLimit = $dryRun ? 0 : max(0, (int) ($options['row_limit'] ?? 0));
        $context = $this->initialContext($batch);
        $seenSkus = [];
        $existingProducts = $this->existingProductsIndex();
        $stats = $this->emptyStats();
        $queuedItemIds = [];

        if (! $dryRun && $this->batchIsCancelled($batch)) {
            return;
        }
        ProductParserItem::where('batch_id', $batch->id)->delete();

        foreach ($parsed['rows'] as $row) {
            if (! $dryRun && $stats['parsed_rows'] % 25 === 0 && $this->batchIsCancelled($batch)) {
                break;
            }

            $stats['parsed_rows']++;

            try {
                if ($this->isServiceRow($row)) {
                    $this->applyContext($context, $row);
                    $stats['service_rows']++;
                    $stats['skipped_rows']++;

                    continue;
                }

                if (! $this->isProductRow($row)) {
                    $stats['skipped_rows']++;

                    continue;
                }

                if ($rowLimit > 0 && $stats['product_rows'] >= $rowLimit) {
                    continue;
                }

                $sku = $this->cleanSku((string) $row['sku']);
                $normalizedSku = $this->normalizeSku($sku);
                $brand = $this->brandValue($row['brand'] ?? null) ?: $context['brand'];
                // Products use SKU as the global identity, so a repeated SKU must
                // never create two drafts even when the supplier changes brand text.
                $duplicateKey = $normalizedSku;

                if (isset($seenSkus[$duplicateKey])) {
                    $this->skippedItem($batch, $row, $sku, 'Duplicate SKU inside price list.', $brand, $normalizedSku, $context);
                    $stats['duplicate_sku_count']++;
                    $stats['skipped_rows']++;

                    continue;
                }

                $seenSkus[$duplicateKey] = true;
                $stats['product_rows']++;

                $group = $row['group'] ?: $context['group'];
                $subgroup = $row['subgroup'] ?: $context['subgroup'];
                $vehicleApplication = $context['vehicle_application'];
                $name = trim((string) $row['name']);
                $price = $this->parsePrice($row['price'] ?? null);
                $stock = $this->parseStock($row['stock'] ?? null);
                if ($stock === null && $hasStockColumn) {
                    $stock = 0;
                }
                $needsPriceReview = $price === null;
                $needsStockReview = $stock === null;
                $existing = $this->findExistingProduct($sku, $brand, $existingProducts);
                $category = $this->categoryDetector->detect($sku, $name, $brand, $group, $subgroup, $vehicleApplication);
                $content = $this->contentBuilder->build($sku, $name, $brand, $group, $category);
                $content = $this->contentBuilder->ensureComplete($content, $sku, $name, $brand, $group, $category);

                if ($needsPriceReview) {
                    $stats['rows_without_price']++;
                }

                if ($needsStockReview) {
                    $stats['rows_without_stock']++;
                }

                if ($category['needs_review']) {
                    $stats['rows_without_category']++;
                }

                if ($existing) {
                    $stats['existing_sku_count']++;
                    $stats['updated_existing']++;
                } else {
                    $stats['new_sku_count']++;
                    if (! $category['needs_review']) {
                        $stats['planned_drafts']++;
                    }
                }

                $item = ProductParserItem::create([
                    'batch_id' => $batch->id,
                    'row_number' => $row['row_number'],
                    'sku' => $sku,
                    'normalized_sku' => $normalizedSku,
                    'brand' => $brand,
                    'category_id' => $category['category_id'] ?: ($batch->category_default_id ?: null),
                    'status' => $queueRows
                        ? 'tristool_queued'
                        : $this->initialItemStatus($dryRun, $existing !== null, $category['needs_review']),
                    'processing_stage' => $queueRows ? 'tristool_queued' : null,
                    'confidence_score' => $category['confidence'],
                    'raw_name' => $row['name'],
                    'parsed_name' => $name,
                    'raw_price' => $row['price'],
                    'parsed_price' => $price,
                    'raw_stock' => $row['stock'],
                    'parsed_stock' => $stock,
                    'detected_group' => $group,
                    'detected_subgroup' => $subgroup,
                    'vehicle_application' => $vehicleApplication,
                    'detected_category_id' => $category['detected_category_id'],
                    'detected_category_path' => $category['detected_category_path'],
                    'category_confidence_score' => $category['confidence'],
                    'category_detection_method' => $category['method'],
                    'category_detection_notes_json' => $category['notes'],
                    'needs_category_review' => $category['needs_review'],
                    'needs_stock_review' => $needsStockReview,
                    'needs_price_review' => $needsPriceReview,
                    'needs_translation_review' => (bool) ($content['needs_translation_review'] ?? true),
                    'needs_content_review' => (bool) ($content['needs_content_review'] ?? true),
                    'generated_content' => (bool) ($content['generated_content'] ?? true),
                    'translation_source_type' => $content['translation_source_type'] ?? 'generated_pending_review',
                    'needs_image_review' => ! $dryRun,
                    'approval_status' => 'pending_review',
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description_ru' => $content['short_description_ru'],
                    'short_description_ro' => $content['short_description_ro'],
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'source_file_name' => $batch->file_name,
                    'import_row_json' => $row['raw'],
                    'found_title' => $content['name_ru'],
                    'found_description' => $content['description_ru'],
                    'found_specs_json' => array_filter([
                        'Brand' => $brand,
                        'SKU' => $sku,
                        'Stock' => $stock,
                        'Group' => $group,
                        'Subgroup' => $subgroup,
                        'Vehicle application' => $vehicleApplication,
                    ], fn ($value) => $value !== null && $value !== ''),
                    'existing_product_id' => $existing?->id,
                ]);

                if ($queueRows) {
                    $queuedItemIds[] = $item->id;

                    continue;
                }

                if ($dryRun) {
                    continue;
                }

                if ($existing) {
                    $isParserDraft = $existing->status === 'draft'
                        && (int) $existing->source_import_batch_id === (int) $batch->id;

                    if (($options['search_images'] ?? true) === true && ($options['add_photos_to_existing'] ?? true) === true) {
                        $this->enrichImages($item, $brand);
                        $item->refresh();

                        $this->preparation->prepare(
                            $item->fresh(['imageAssets', 'batch']),
                            ($options['process_images'] ?? true) === true,
                        );
                        $item->refresh();

                        $item->forceFill([
                            'status' => 'existing_product_found',
                        ])->save();
                    }

                    if ($isParserDraft) {
                        $this->drafts->refreshParserDraft($item->fresh(['imageAssets', 'category', 'batch']), $existing);
                        $stats['created_drafts']++;

                        continue;
                    }

                    $batch->addLog('Existing product found. No automatic update was made.', ['sku' => $sku, 'product_id' => $existing->id]);

                    continue;
                }

                if ($item->needs_category_review) {
                    $item->forceFill(['status' => 'needs_category_review'])->save();

                    continue;
                }

                if (($options['search_images'] ?? true) === true) {
                    $this->enrichImages($item, $brand);
                }

                $item->refresh();
                $item->forceFill([
                    'status' => $item->needs_category_review ? 'needs_category_review' : 'ready_for_review',
                ])->save();

                if (($options['search_images'] ?? true) === true) {
                    $this->preparation->prepare(
                        $item->fresh(['imageAssets', 'batch']),
                        ($options['process_images'] ?? true) === true,
                    );
                    $item->refresh();
                }

                if (($options['create_drafts_automatically'] ?? true) === true && ! $item->needs_category_review) {
                    $this->drafts->createDraft($item->fresh(['imageAssets', 'category', 'batch']));
                    $stats['created_drafts']++;
                }
            } catch (Throwable $e) {
                $stats['error_rows']++;
                $batch->addLog('Price list row failed', [
                    'row' => $row['row_number'] ?? null,
                    'sku' => $row['sku'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $stats['sku_count'] = $stats['product_rows'];
        $report = $stats + [
            'sheet' => $parsed['sheet'] ?? null,
            'headers' => $parsed['headers'] ?? [],
            'mapping' => $parsed['mapping'] ?? [],
            'dry_run' => $dryRun,
            'queued_rows' => $queueRows ? count($queuedItemIds) : 0,
        ];

        $cancelled = $this->batchIsCancelled($batch);

        $batchValues = [
            'sku_count' => $stats['product_rows'],
            'total_rows' => $parsed['total_rows'],
            'parsed_rows' => $stats['parsed_rows'],
            'product_rows' => $stats['product_rows'],
            'created_drafts' => $stats['created_drafts'],
            'updated_existing' => $stats['updated_existing'],
            'skipped_rows' => $stats['skipped_rows'],
            'service_rows' => $stats['service_rows'],
            'new_sku_count' => $stats['new_sku_count'],
            'existing_sku_count' => $stats['existing_sku_count'],
            'duplicate_sku_count' => $stats['duplicate_sku_count'],
            'rows_without_price' => $stats['rows_without_price'],
            'rows_without_stock' => $stats['rows_without_stock'],
            'rows_without_category' => $stats['rows_without_category'],
            'planned_drafts' => $stats['planned_drafts'],
            'error_rows' => $stats['error_rows'],
            'dry_run_report_json' => $report,
        ];

        if ($queueRows && ! $cancelled) {
            $options['staging_complete'] = true;
            $batchValues['options_json'] = $options;
            $batchValues['status'] = 'processing';
            $batchValues['finished_at'] = null;
        } else {
            $batchValues['status'] = $cancelled
                ? 'cancelled'
                : ($dryRun
                    ? ($stats['error_rows'] > 0 ? 'completed_with_errors' : 'dry_run_completed')
                    : ($stats['error_rows'] > 0 ? 'completed_with_errors' : 'completed'));
            $batchValues['finished_at'] = now();
        }

        $batch->forceFill($batchValues)->save();
        $batch->addLog(
            $cancelled
                ? 'Price list import cancelled'
                : ($queueRows
                    ? 'Price list rows queued for enrichment'
                    : ($dryRun ? 'Price list dry-run completed' : 'Price list import completed')),
            $report
        );

        if ($queueRows && ! $cancelled) {
            $this->dispatchQueuedItems($batch, $queuedItemIds);
            $this->finalizeQueuedImport($batch);
        }
    }

    public function processQueuedItem(ProductParserItem $item): void
    {
        $this->processExternalQueuedItem($item);
    }

    public function processFastQueuedItem(ProductParserItem $item): void
    {
        $item->loadMissing(['batch', 'existingProduct', 'imageAssets', 'category']);
        $batch = $item->batch;

        if (! $batch || $this->batchIsCancelled($batch)) {
            $this->rejectIfPresent($item);

            return;
        }

        $existing = $item->existingProduct ?: Product::where('sku', $item->sku)->first();
        if ($existing && (int) $item->existing_product_id !== (int) $existing->id) {
            $item->forceFill(['existing_product_id' => $existing->id])->save();
        }

        $sourceFound = $this->enrichImages($item, $item->brand, 'tristool');
        $item->refresh();
        $item->forceFill(['tristool_checked_at' => now()])->save();

        if (! $sourceFound || ! $this->itemIsAutomaticallyComplete($item)) {
            if (($batch->options_json['source_mode'] ?? null) === 'tristool_only') {
                $item->forceFill([
                    'status' => 'needs_manual_review',
                    'processing_stage' => 'tristool_manual',
                    'needs_source_review' => ! $sourceFound || $item->needs_source_review,
                    'error_message' => $sourceFound
                        ? 'TrisTool-only search returned an incomplete product card. External recovery is disabled.'
                        : 'TrisTool-only search did not return an acceptable product card. External recovery is disabled.',
                ])->save();

                return;
            }

            $this->queueExternalCheck($item);

            return;
        }

        $this->finalizeResolvedItem($item, $existing, 'tristool_ready');
    }

    public function processExternalQueuedItem(ProductParserItem $item): void
    {
        $item->loadMissing(['batch', 'existingProduct', 'imageAssets', 'category']);
        $batch = $item->batch;

        if (! $batch || $this->batchIsCancelled($batch)) {
            $this->rejectIfPresent($item);

            return;
        }

        $existing = $item->existingProduct ?: Product::where('sku', $item->sku)->first();

        if ($existing && (int) $item->existing_product_id !== (int) $existing->id) {
            $item->forceFill(['existing_product_id' => $existing->id])->save();
        }

        $item->increment('external_attempts');
        $sourceFound = $this->enrichImages($item, $item->brand, 'external');
        $item->refresh();
        $item->forceFill(['external_checked_at' => now()])->save();

        if (! $sourceFound) {
            if ($this->applyGysBrandFallback($item)) {
                $this->finalizeResolvedItem($item->fresh(), $existing, 'brand_logo_ready');

                return;
            }

            $item->forceFill([
                'status' => 'needs_manual_review',
                'processing_stage' => 'external_manual',
                'needs_source_review' => true,
                'needs_content_review' => true,
                'needs_translation_review' => true,
                'needs_image_review' => true,
                'error_message' => 'All automatic TrisTool and external-source recovery attempts were exhausted.',
            ])->save();

            return;
        }

        if ($item->needs_category_review) {
            $item->forceFill([
                'status' => 'needs_manual_review',
                'processing_stage' => 'external_manual',
                'error_message' => trim(($item->error_message ? $item->error_message.' ' : '')
                    .'Automatic category detection and category creation were exhausted.'),
            ])->save();

            return;
        }
        $item->refresh();

        $approvedGysRecovery = Str::contains(Str::upper((string) $item->brand), 'GYS')
            && (bool) ($batch->options_json['approve_gys_ordered_recovery'] ?? false);

        if ($approvedGysRecovery && $item->imageAssets()->doesntExist()) {
            if ($this->applyGysBrandFallback($item)) {
                $this->finalizeResolvedItem($item->fresh(), $existing, 'brand_logo_ready');
            }

            return;
        }

        if ($approvedGysRecovery) {
            $reviewedAt = now();
            $item->forceFill([
                'needs_source_review' => false,
                'needs_content_review' => false,
                'needs_translation_review' => false,
                'needs_image_review' => false,
                'source_reviewed_at' => $reviewedAt,
                'image_reviewed_at' => $reviewedAt,
                'translation_reviewed_at' => $reviewedAt,
            ])->save();
        }

        $item = $this->freshActiveItem($item);
        if (! $item) {
            return;
        }

        if ($item->needs_source_review
            || $item->needs_content_review
            || $item->needs_translation_review
            || $item->imageAssets()->doesntExist()) {
            $item->forceFill([
                'status' => 'needs_manual_review',
                'processing_stage' => 'external_manual',
            ])->save();

            return;
        }

        $this->finalizeResolvedItem($item, $existing, 'external_ready');
    }

    public function prepareApprovedGysRecovery(ProductParserItem $item, bool $forceLogo = false): void
    {
        $item = ProductParserItem::with(['batch', 'imageAssets', 'category'])->findOrFail($item->id);
        if (! Str::contains(Str::upper((string) $item->brand), 'GYS')) {
            throw new \RuntimeException('Only GYS parser rows can use this approved recovery flow.');
        }

        $existing = Product::where('sku', $item->sku)->first();
        if ($existing?->status === 'published' && $existing->is_active) {
            $item->forceFill([
                'status' => 'approved',
                'processing_stage' => 'published',
                'approval_status' => 'approved',
                'existing_product_id' => $existing->id,
                'error_message' => null,
            ])->save();

            return;
        }

        if (! $forceLogo && $item->imageAssets->isEmpty() && ! $item->external_checked_at) {
            $this->enrichImages($item, $item->brand, 'external');
            $item->refresh()->load('imageAssets');
            $item->forceFill(['external_checked_at' => now()])->save();
        }

        if ($forceLogo || $item->imageAssets->isEmpty()) {
            $this->applyGysBrandFallback($item);
            $item->refresh()->load('imageAssets');
        }

        $reviewedAt = now();
        $item->forceFill([
            'needs_source_review' => false,
            'needs_content_review' => false,
            'needs_translation_review' => false,
            'needs_image_review' => false,
            'source_reviewed_at' => $reviewedAt,
            'image_reviewed_at' => $reviewedAt,
            'translation_reviewed_at' => $reviewedAt,
            'error_message' => null,
        ])->save();

        $stage = $item->image_source_type === 'brand_logo_fallback'
            ? 'brand_logo_ready'
            : 'external_ready';
        $this->finalizeResolvedItem($item->fresh(), $existing, $stage);

        $item->refresh()->load(['createdProduct', 'existingProduct']);
        $draft = $item->createdProduct ?: $item->existingProduct;
        if ($draft?->status === 'draft') {
            $item->forceFill([
                'status' => 'image_publish_queued',
                'processing_stage' => 'image_publish_queued',
            ])->save();
            PrepareAndPublishParserDraftJob::dispatch($item->id);
        }
    }

    private function finalizeResolvedItem(ProductParserItem $item, ?Product $existing, string $processingStage): void
    {
        $item->loadMissing(['batch', 'imageAssets', 'category']);
        $batch = $item->batch;
        if (! $batch || $this->batchIsCancelled($batch)) {
            $this->rejectIfPresent($item);

            return;
        }

        $options = $batch->options_json ?: [];
        $dryRun = $batch->import_mode === 'dry_run';

        if ($existing) {
            $isParserDraft = $existing->status === 'draft'
                && $existing->source_import_batch_id !== null;

            if (! $dryRun && ($options['add_photos_to_existing'] ?? true) === true) {
                $fresh = $this->freshActiveItem($item);
                if (! $fresh) {
                    return;
                }
                $this->preparation->prepare(
                    $fresh,
                    ($options['process_images'] ?? true) === true,
                );
            }

            if ($isParserDraft) {
                $fresh = $this->freshActiveItem($item, ['imageAssets', 'category', 'batch']);
                if ($fresh) {
                    if ((int) $existing->source_import_batch_id === (int) $batch->id) {
                        $this->drafts->refreshParserDraft($fresh, $existing);
                    } else {
                        $this->drafts->refreshDraftFromSearch($fresh, $existing);
                    }
                    $fresh->forceFill([
                        'processing_stage' => $processingStage,
                        'existing_product_id' => $existing->id,
                    ])->save();
                }

                return;
            }

            if ($fresh = $this->freshActiveItem($item)) {
                $fresh->forceFill([
                    'status' => 'existing_product_found',
                    'processing_stage' => $processingStage,
                    'error_message' => null,
                ])->save();
            }

            return;
        }

        $item->forceFill([
            'status' => $dryRun ? 'dry_run_ready' : 'ready_for_review',
            'processing_stage' => $processingStage,
            'error_message' => null,
        ])->save();

        if (! $dryRun) {
            $this->preparation->prepare(
                $item->load(['imageAssets', 'batch']),
                ($options['process_images'] ?? true) === true,
            );
            $item = $this->freshActiveItem($item);
            if (! $item) {
                return;
            }
        }

        if (! $dryRun && ($options['create_drafts_automatically'] ?? true) === true) {
            $item = $this->freshActiveItem($item, ['imageAssets', 'category', 'batch']);
            if ($item) {
                $this->drafts->createDraft($item);
            }
        }
    }

    private function itemIsAutomaticallyComplete(ProductParserItem $item): bool
    {
        $item->loadMissing('imageAssets');

        return ! $item->needs_category_review
            && ! $item->needs_source_review
            && ! $item->needs_content_review
            && ! $item->needs_translation_review
            && $item->imageAssets->isNotEmpty();
    }

    private function applyGysBrandFallback(ProductParserItem $item): bool
    {
        if (! Str::contains(Str::upper((string) $item->brand), 'GYS')) {
            return false;
        }

        $sourceUrl = '/images/products/gys-product.svg';
        if (! is_file(public_path(ltrim($sourceUrl, '/')))) {
            return false;
        }

        $contentSku = strtr($item->sku, [
            'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K', 'М' => 'M', 'Н' => 'H',
            'О' => 'O', 'Р' => 'P', 'С' => 'C', 'Т' => 'T', 'Х' => 'X', 'У' => 'Y',
            'а' => 'a', 'в' => 'b', 'е' => 'e', 'к' => 'k', 'м' => 'm', 'н' => 'h',
            'о' => 'o', 'р' => 'p', 'с' => 'c', 'т' => 't', 'х' => 'x', 'у' => 'y',
        ]);
        $content = $this->contentBuilder->ensureComplete(
            [],
            $contentSku,
            (string) ($item->raw_name ?: $item->parsed_name ?: $item->sku),
            'GYS',
        );

        $item->imageAssets()->delete();
        $item->imageAssets()->create([
            'source_url' => $sourceUrl,
            'source_domain' => (string) config('store.domain_label', 'masterscule.ro'),
            'processed_path' => $sourceUrl,
            'preview_path' => $sourceUrl,
            'thumb_path' => $sourceUrl,
            'width' => 320,
            'height' => 152,
            'mime_type' => 'image/svg+xml',
            'status' => 'processed',
            'is_selected' => true,
            'is_main' => true,
            'has_watermark' => false,
            'needs_review' => false,
        ]);

        $reviewedAt = now();
        $item->forceFill([
            'found_title' => $item->raw_name ?: $item->parsed_name ?: 'GYS '.$item->sku,
            'found_description' => $content['description_ru'],
            'found_images_json' => [$sourceUrl],
            'selected_images_json' => [$sourceUrl],
            'processed_images_json' => [$sourceUrl],
            'source_urls_json' => ['https://www.gys.com.ua/'],
            'fallback_source_url' => 'https://www.gys.com.ua/',
            'fallback_source_domain' => 'gys.com.ua',
            'fallback_source_used' => true,
            'source_match_confidence' => 100,
            'content_source_type' => 'price_list_approved_fallback',
            'image_source_type' => 'brand_logo_fallback',
            'translation_source_type' => 'approved_generated_content',
            'name_ru' => $content['name_ru'],
            'name_ro' => $content['name_ro'],
            'short_description_ru' => $content['short_description_ru'],
            'short_description_ro' => $content['short_description_ro'],
            'description_ru' => $content['description_ru'],
            'description_ro' => $content['description_ro'],
            'generated_content' => true,
            'needs_source_review' => false,
            'needs_content_review' => false,
            'needs_translation_review' => false,
            'needs_image_review' => false,
            'source_reviewed_at' => $reviewedAt,
            'image_reviewed_at' => $reviewedAt,
            'translation_reviewed_at' => $reviewedAt,
            'error_message' => null,
        ])->save();

        return true;
    }

    private function queueExternalCheck(ProductParserItem $item): void
    {
        if (! $item->batch || $this->batchIsCancelled($item->batch)) {
            $this->rejectIfPresent($item);

            return;
        }

        $item->forceFill([
            'status' => 'external_check_queued',
            'processing_stage' => 'external_queued',
            'error_message' => 'TrisTool fast check did not return a complete product card. External recovery queued.',
        ])->save();
        ProcessExternalPriceListRowJob::dispatch($item->id);
    }

    private function dispatchQueuedItems(ProductParserBatch $batch, array $itemIds): void
    {
        $dispatched = 0;
        $failed = 0;
        $queue = (($batch->options_json['source_mode'] ?? null) === 'tristool_only')
            ? 'parser-tristool'
            : 'parser-fast';

        foreach ($itemIds as $index => $itemId) {
            if ($index % 50 === 0 && $this->batchIsCancelled($batch)) {
                ProductParserItem::whereIn('id', array_slice($itemIds, $index))
                    ->whereIn('status', ['queued', 'tristool_queued'])
                    ->update(['status' => 'rejected', 'processing_stage' => 'rejected']);
                break;
            }

            try {
                ProcessPriceListRowJob::dispatch($itemId, $queue);
                $dispatched++;
            } catch (Throwable $e) {
                $failed++;
                ProductParserItem::whereKey($itemId)->update([
                    'status' => 'failed',
                    'error_message' => 'Unable to enqueue row: '.$e->getMessage(),
                ]);
            }
        }

        $freshBatch = ProductParserBatch::find($batch->id);
        if (! $freshBatch || $freshBatch->status === 'cancelled') {
            return;
        }

        $options = $freshBatch->options_json ?: [];
        $options['dispatched_rows'] = $dispatched;
        $freshBatch->forceFill([
            'options_json' => $options,
            'error_rows' => max((int) $freshBatch->error_rows, $failed),
        ])->save();
        $freshBatch->addLog('Price list row dispatch completed', [
            'queued' => count($itemIds),
            'dispatched' => $dispatched,
            'failed' => $failed,
        ]);
    }

    private function freshActiveItem(ProductParserItem $item, array $relations = ['imageAssets', 'batch']): ?ProductParserItem
    {
        $fresh = ProductParserItem::with($relations)->find($item->id);

        if (! $fresh || ! $fresh->batch || $fresh->batch->status === 'cancelled') {
            if ($fresh) {
                $this->rejectIfPresent($fresh);
            }

            return null;
        }

        return $fresh;
    }

    private function rejectIfPresent(ProductParserItem $item): void
    {
        ProductParserItem::whereKey($item->id)
            ->whereNotIn('status', ['approved', 'draft_created', 'existing_product_found'])
            ->update(['status' => 'rejected', 'processing_stage' => 'rejected']);
    }

    private function batchIsCancelled(ProductParserBatch $batch): bool
    {
        $status = ProductParserBatch::whereKey($batch->id)->value('status');

        return $status === null || $status === 'cancelled';
    }

    public function finalizeQueuedImport(ProductParserBatch $batch): void
    {
        Cache::store(config('product_parser.lock_store', 'file'))
            ->lock('parser-price-list-finalize:'.$batch->id, 30)
            ->get(function () use ($batch) {
            $batch = ProductParserBatch::find($batch->id);
            if (! $batch) {
                return;
            }
            $options = $batch->options_json ?: [];

            if ($batch->status === 'cancelled' || ! ($options['staging_complete'] ?? false)) {
                return;
            }

            $pending = $batch->items()
                ->whereIn('status', [
                    'queued',
                    'searching',
                    'tristool_queued',
                    'tristool_searching',
                    'external_check_queued',
                    'external_searching',
                    'image_publish_queued',
                    'processing_images',
                    'parsed',
                ])
                ->exists();

            if ($pending) {
                return;
            }

            $report = $batch->dry_run_report_json ?: [];
            $failedRows = $batch->items()->where('status', 'failed')->count();
            $errorRows = max((int) ($report['error_rows'] ?? 0), $failedRows);
            $createdDrafts = $batch->items()->whereNotNull('created_product_id')->count();
            $rowsWithoutCategory = $batch->items()->where(function ($query) {
                $query->whereNull('category_id')
                    ->orWhere('needs_category_review', true);
            })->count();
            $plannedDrafts = $batch->items()
                ->whereNull('existing_product_id')
                ->whereIn('status', ['dry_run_ready', 'ready_for_review', 'draft_created'])
                ->count();
            $status = $errorRows > 0
                ? 'completed_with_errors'
                : ($batch->import_mode === 'dry_run' ? 'dry_run_completed' : 'completed');

            if ($batch->status === $status && $batch->finished_at) {
                return;
            }

            $report['created_drafts'] = $createdDrafts;
            $report['error_rows'] = $errorRows;
            $report['rows_without_category'] = $rowsWithoutCategory;
            $report['planned_drafts'] = $plannedDrafts;
            $report['queued_rows'] = 0;

            $batch->forceFill([
                'created_drafts' => $createdDrafts,
                'error_rows' => $errorRows,
                'rows_without_category' => $rowsWithoutCategory,
                'planned_drafts' => $plannedDrafts,
                'dry_run_report_json' => $report,
                'status' => $status,
                'finished_at' => now(),
            ])->save();
            $batch->addLog(
                $batch->import_mode === 'dry_run' ? 'Price list dry-run enrichment completed' : 'Price list import completed',
                $report,
            );
        });
    }

    private function emptyStats(): array
    {
        return [
            'parsed_rows' => 0,
            'product_rows' => 0,
            'created_drafts' => 0,
            'updated_existing' => 0,
            'skipped_rows' => 0,
            'service_rows' => 0,
            'new_sku_count' => 0,
            'existing_sku_count' => 0,
            'duplicate_sku_count' => 0,
            'rows_without_price' => 0,
            'rows_without_stock' => 0,
            'rows_without_category' => 0,
            'planned_drafts' => 0,
            'error_rows' => 0,
        ];
    }

    private function initialItemStatus(bool $dryRun, bool $existing, bool $needsCategoryReview): string
    {
        if ($existing) {
            return 'existing_product_found';
        }

        if ($needsCategoryReview) {
            return 'needs_category_review';
        }

        return $dryRun ? 'dry_run_ready' : 'parsed';
    }

    private function initialContext(ProductParserBatch $batch): array
    {
        return [
            'brand' => $this->brandValue($batch->brand_default) ?: $this->brandFromText((string) $batch->file_name),
            'group' => null,
            'subgroup' => null,
            'vehicle_application' => null,
        ];
    }

    private function enrichImages(ProductParserItem $item, ?string $brand, string $scope = 'all'): bool
    {
        try {
            $result = match ($scope) {
                'tristool' => $this->search->searchTrisToolForParser($item->sku, $brand, $item->raw_name),
                'external' => $this->search->searchExternalForParser($item->sku, $brand, $item->raw_name),
                default => $this->search->searchForParser($item->sku, $brand, 'auto', false, $item->raw_name),
            };
            if (! ($result['found'] ?? false)) {
                return false;
            }

            foreach ($result['sources'] ?? [] as $source) {
                ProductParserSource::updateOrCreate(
                    [
                        'parser_item_id' => $item->id,
                        'url' => $source['url'],
                    ],
                    [
                        'domain' => $source['domain'] ?? parse_url($source['url'], PHP_URL_HOST),
                        'title' => $source['title'] ?? null,
                        'snippet' => $source['snippet'] ?? null,
                        'source_type' => $source['source_type'] ?? 'generic',
                        'confidence_score' => $source['confidence_score'] ?? null,
                        'raw_data_json' => $source['raw_data_json'] ?? null,
                    ],
                );
            }

            $this->categoryResolver->resolveFromSourceResult($item, $result);
            $item->refresh();

            $images = array_values(array_filter($result['images'] ?? []));
            $imageSourceDomain = parse_url($images[0] ?? '', PHP_URL_HOST)
                ?: ($result['official_source_domain'] ?? null);
            $this->imageCollector->collect($item, $images, $imageSourceDomain);
            $images = $item->imageAssets()->pluck('source_url')->values()->all();
            $translated = $this->translation->bilingual($result);
            $contentVariants = collect($result['content_variants'] ?? [])
                ->map(function (array $variant) {
                    $bilingual = $this->translation->bilingual($variant);

                    return $variant + [
                        'name_ru' => $bilingual['name_ru'],
                        'name_ro' => $bilingual['name_ro'],
                        'description_ru' => $bilingual['description_ru'],
                        'description_ro' => $bilingual['description_ro'],
                        'translation_complete' => $bilingual['complete'],
                    ];
                })
                ->values()
                ->all();
            $content = [
                'name_ru' => $translated['name_ru'],
                'name_ro' => $translated['name_ro'],
                'short_description_ru' => $translated['short_description_ru'],
                'short_description_ro' => $translated['short_description_ro'],
                'description_ru' => $translated['description_ru'],
                'description_ro' => $translated['description_ro'],
                'needs_translation_review' => ! $translated['complete'],
                'needs_content_review' => ! filled($result['description'] ?? $result['description_ru'] ?? $result['description_ro'] ?? null),
                'generated_content' => false,
                'translation_source_type' => $translated['translation_source_type'],
            ];
            $content = $this->contentBuilder->ensureComplete(
                $content,
                $item->sku,
                (string) ($item->raw_name ?: $item->found_title ?: $item->sku),
                $brand,
            );
            $tristoolsSource = collect($result['sources'] ?? [])
                ->first(fn (array $source) => str_contains((string) ($source['domain'] ?? ''), 'tristool.md'));
            $tristoolsUrl = is_array($tristoolsSource) ? ($tristoolsSource['url'] ?? null) : null;
            $tristoolsConfidence = $tristoolsUrl
                ? ($tristoolsSource['confidence_score'] ?? $result['confidence'] ?? null)
                : null;
            $item->forceFill([
                'found_title' => $result['title'] ?? $item->found_title,
                'found_description' => $result['description'] ?? $item->found_description,
                'found_specs_json' => array_merge($item->found_specs_json ?: [], ($result['specs'] ?? []) + [
                    '_package_contents' => $result['package_contents'] ?? [],
                    '_breadcrumb' => $result['breadcrumb'] ?? [],
                    '_breadcrumb_ro' => $result['breadcrumb_ro'] ?? [],
                    '_official_breadcrumb' => $result['official_breadcrumb'] ?? [],
                    '_content_variants' => $contentVariants,
                    '_automation_attempts' => $result['automation_attempts'] ?? 1,
                    '_automation_exhausted' => $result['automation_exhausted'] ?? false,
                ]),
                'found_images_json' => $images,
                'selected_images_json' => collect($images)->take(1)->values()->all(),
                'source_urls_json' => $result['source_urls'] ?? [],
                'tristools_url' => $tristoolsUrl,
                'tristools_match_confidence' => $tristoolsConfidence,
                'needs_image_review' => $images === [],
                'official_source_url' => $result['official_source_url'] ?? null,
                'official_source_domain' => $result['official_source_domain'] ?? null,
                'official_source_confidence' => $result['official_source_confidence'] ?? null,
                'fallback_source_url' => $result['fallback_source_url'] ?? null,
                'fallback_source_domain' => $result['fallback_source_domain'] ?? null,
                'fallback_source_used' => (bool) ($result['fallback_source_used'] ?? false),
                'source_match_confidence' => $result['source_match_confidence'] ?? ($result['confidence'] ?? 0),
                'needs_source_review' => (bool) ($result['needs_source_review'] ?? true),
                'content_source_type' => $result['content_source_type'] ?? null,
                'image_source_type' => $result['image_source_type'] ?? null,
                'name_ru' => $content['name_ru'],
                'name_ro' => $content['name_ro'],
                'short_description_ru' => $content['short_description_ru'],
                'short_description_ro' => $content['short_description_ro'],
                'description_ru' => $content['description_ru'],
                'description_ro' => $content['description_ro'],
                'needs_translation_review' => ! $translated['complete'],
                'needs_content_review' => ! filled($result['description'] ?? $result['description_ru'] ?? $result['description_ro'] ?? null),
                'generated_content' => false,
                'translation_source_type' => $translated['translation_source_type'],
                'error_message' => null,
            ])->save();

            return true;
        } catch (Throwable $e) {
            $item->forceFill([
                'needs_image_review' => true,
                'error_message' => trim(($item->error_message ? $item->error_message.' ' : '').'Image search failed: '.$e->getMessage()),
            ])->save();
            $item->batch?->addLog('Image search failed', ['sku' => $item->sku, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function skippedItem(ProductParserBatch $batch, array $row, ?string $sku, string $reason, ?string $brand = null, ?string $normalizedSku = null, array $context = []): void
    {
        $isDuplicate = str_contains($reason, 'Duplicate SKU');
        $hasStock = trim((string) ($row['stock'] ?? '')) !== '';

        ProductParserItem::create([
            'batch_id' => $batch->id,
            'row_number' => $row['row_number'] ?? null,
            'sku' => $sku ?: 'row-'.$row['row_number'],
            'normalized_sku' => $normalizedSku ?: ($sku ? $this->normalizeSku($sku) : null),
            'brand' => $brand,
            'raw_name' => $row['name'] ?? null,
            'raw_price' => $row['price'] ?? null,
            'parsed_price' => $isDuplicate && $hasStock ? $this->parsePrice($row['price'] ?? null) : null,
            'raw_stock' => $row['stock'] ?? null,
            'parsed_stock' => $isDuplicate && $hasStock ? $this->parseStock($row['stock'] ?? null) : null,
            'detected_group' => $context['group'] ?? null,
            'detected_subgroup' => $context['subgroup'] ?? null,
            'vehicle_application' => $context['vehicle_application'] ?? null,
            'status' => 'skipped',
            'error_message' => $reason,
            'import_row_json' => $row['raw'] ?? [],
        ]);
    }

    private function isProductRow(array $row): bool
    {
        $sku = $this->cleanSku((string) ($row['sku'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        $hasPriceOrStock = trim((string) ($row['price'] ?? '')) !== '' || trim((string) ($row['stock'] ?? '')) !== '';

        return $sku !== '' && $name !== '' && $hasPriceOrStock && $this->looksLikeSku($sku);
    }

    private function isServiceRow(array $row): bool
    {
        $sku = $this->cleanSku((string) ($row['sku'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        $price = trim((string) ($row['price'] ?? ''));
        $stock = trim((string) ($row['stock'] ?? ''));

        return ($sku === '' || ! $this->looksLikeSku($sku)) && $name !== '' && $price === '' && $stock === '';
    }

    private function applyContext(array &$context, array $row): void
    {
        $text = trim((string) ($row['name'] ?: collect($row['raw_values'] ?? [])->first(fn ($value) => trim((string) $value) !== '')));

        if ($brand = $this->brandFromText($text)) {
            $context['brand'] = $brand;
            $context['group'] = null;
            $context['subgroup'] = null;
            $context['vehicle_application'] = null;
        } elseif ($this->looksLikeVehicleApplication($text)) {
            $context['vehicle_application'] = $this->normalizeVehicleApplication($text);
        } elseif ($this->looksLikeTopSection($text)) {
            $context['group'] = null;
            $context['subgroup'] = null;
            $context['vehicle_application'] = null;
        } elseif (mb_strlen($text) <= 80) {
            if ($context['group'] && $context['group'] !== $text) {
                $context['subgroup'] = $text;
            } else {
                $context['group'] = $text;
            }
        }
    }

    private function existingProductsIndex(): array
    {
        $index = [];

        Product::query()
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->select([
                'products.id',
                'products.sku',
                'products.brand_id',
                'products.status',
                'products.source_import_batch_id',
                'brands.name as index_brand_name',
            ])
            ->orderBy('products.id')
            ->cursor()
            ->each(function (Product $product) use (&$index) {
                $normalizedSku = $this->normalizeSku($product->sku);
                if ($normalizedSku === '') {
                    return;
                }

                $brandKey = $this->brandKey($product->getAttribute('index_brand_name'));
                unset($product->index_brand_name);
                $index[$brandKey.'|'.$normalizedSku] = $product;
                $index['*|'.$normalizedSku] ??= $product;
            });

        return $index;
    }

    private function findExistingProduct(string $sku, ?string $brand, array $existingProducts): ?Product
    {
        $normalizedSku = $this->normalizeSku($sku);

        return $existingProducts[$this->brandKey($brand).'|'.$normalizedSku]
            ?? $existingProducts['*|'.$normalizedSku]
            ?? null;
    }

    private function brandFromText(string $text): ?string
    {
        $lower = Str::lower($text);

        return match (true) {
            Str::contains($lower, ['king tony', 'kingtony']) => 'King Tony',
            Str::contains($lower, ['m7', 'mighty seven']) => 'M7 / Mighty Seven',
            Str::contains($lower, ['jtc']) => 'JTC',
            preg_match('/(^|\W)gys(\W|$)/iu', $lower) === 1 => 'GYS',
            Str::contains($lower, ['hoegert', 'högert', 'hogert', 'gtv']) => 'Hoegert',
            Str::contains($lower, ['tongrun', 'torin', 'big red']) => 'Torin BIG RED',
            default => null,
        };
    }

    private function looksLikeTopSection(string $text): bool
    {
        $lower = Str::lower($text);

        return Str::contains($lower, ['01 оборудование', 'оборудование']) && mb_strlen($text) <= 40;
    }

    private function looksLikeVehicleApplication(string $text): bool
    {
        $lower = Str::lower($text);

        return Str::contains($lower, [
            'benz',
            'bmw',
            'mercedes',
            'vw',
            'audi',
            'vag',
            'opel',
            'renault',
            'toyota',
            'ford',
            'volvo',
            'fiat',
            'peugeot',
            'citroen',
            'mazda',
            'nissan',
            'honda',
        ]) && mb_strlen($text) <= 120;
    }

    private function normalizeVehicleApplication(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?: $text);

        return mb_strtoupper($text, 'UTF-8');
    }

    private function parsePrice(?string $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(["\xc2\xa0", ' '], '', $value);
        $value = preg_replace('/[^0-9,\.\-]/', '', $value) ?: '';

        if (substr_count($value, ',') === 1 && substr_count($value, '.') === 0) {
            $value = str_replace(',', '.', $value);
        } elseif (substr_count($value, ',') > 0 && substr_count($value, '.') > 0) {
            $value = str_replace(',', '', $value);
        }

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    private function parseStock(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^0-9\-]/', '', $value) ?: '';

        return is_numeric($value) ? max(0, (int) $value) : null;
    }

    private function cleanSku(string $sku): string
    {
        return trim(preg_replace('/\s+/u', '', $sku) ?: '');
    }

    private function normalizeSku(string $sku): string
    {
        return Str::lower(preg_replace('/[^a-z0-9]/i', '', $sku) ?: '');
    }

    private function brandKey(?string $brand): string
    {
        $brand = trim((string) $brand);

        return $brand === '' ? '*' : Str::lower(preg_replace('/[^a-z0-9]+/i', '', $brand) ?: $brand);
    }

    private function looksLikeSku(string $sku): bool
    {
        if (mb_strlen($sku) > 80) {
            return false;
        }

        return (bool) preg_match('/[0-9]/', $sku)
            || (bool) preg_match('/^[A-Z]{2,}[A-Z0-9]*[-_\/][A-Z0-9]+$/i', $sku);
    }

    private function brandValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || $value === 'auto' ? null : $value;
    }
}
