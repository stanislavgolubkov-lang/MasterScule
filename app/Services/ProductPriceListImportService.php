<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductParserBatch;
use App\Models\ProductParserItem;
use App\Models\ProductParserSource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductPriceListImportService
{
    public function __construct(
        private ProductPriceListReader $reader,
        private ProductCategoryDetector $categoryDetector,
        private ProductParserContentBuilder $contentBuilder,
        private ProductSearchService $search,
        private ProductImageCollectorService $imageCollector,
        private ProductParserItemPreparationService $preparation,
        private ProductDraftService $drafts,
    ) {}

    public function dryRun(ProductParserBatch $batch): void
    {
        $this->run($batch, true);
    }

    public function import(ProductParserBatch $batch): void
    {
        $this->run($batch, false);
    }

    private function run(ProductParserBatch $batch, bool $dryRun): void
    {
        $batch->forceFill([
            'status' => 'processing',
            'started_at' => $batch->started_at ?: now(),
        ])->save();
        $batch->addLog($dryRun ? 'Price list dry-run started' : 'Price list import started', [
            'file' => $batch->file_name,
            'supplier' => $batch->supplier_name,
        ]);

        try {
            $path = Storage::disk('local')->path((string) $batch->file_path);
            $parsed = $this->reader->read($path, (string) $batch->file_type);
        } catch (Throwable $e) {
            $batch->forceFill([
                'status' => 'failed',
                'error_rows' => 1,
                'finished_at' => now(),
            ])->save();
            $batch->addLog('Price list parsing failed', ['error' => $e->getMessage()]);

            return;
        }

        $options = $batch->options_json ?: [];
        $rowLimit = $dryRun ? 0 : max(0, (int) ($options['row_limit'] ?? 0));
        $context = $this->initialContext($batch);
        $seenSkus = [];
        $existingProducts = $this->existingProductsIndex();
        $stats = $this->emptyStats();

        ProductParserItem::where('batch_id', $batch->id)->delete();

        foreach ($parsed['rows'] as $row) {
            if (! $dryRun && $batch->fresh()->status === 'cancelled') {
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
                $duplicateKey = $this->brandKey($brand).'|'.$normalizedSku;

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
                $needsPriceReview = $price === null;
                $needsStockReview = $stock === null;
                $existing = $this->findExistingProduct($sku, $brand, $existingProducts);
                $category = $this->categoryDetector->detect($sku, $name, $brand, $group, $subgroup, $vehicleApplication);
                $content = $this->contentBuilder->build($sku, $name, $brand, $group, $category);

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
                    'status' => $this->initialItemStatus($dryRun, $existing !== null, $category['needs_review']),
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
                        'Retail price' => $price,
                        'Stock' => $stock,
                        'Group' => $group,
                        'Subgroup' => $subgroup,
                        'Vehicle application' => $vehicleApplication,
                        'Price source' => 'ОтпускЦена / retail',
                    ], fn ($value) => $value !== null && $value !== ''),
                    'existing_product_id' => $existing?->id,
                ]);

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
        ];

        $cancelled = $batch->fresh()->status === 'cancelled';

        $batch->forceFill([
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
            'status' => $cancelled
                ? 'cancelled'
                : ($dryRun
                    ? ($stats['error_rows'] > 0 ? 'completed_with_errors' : 'dry_run_completed')
                    : ($stats['error_rows'] > 0 ? 'completed_with_errors' : 'completed')),
            'finished_at' => now(),
        ])->save();
        $batch->addLog(
            $cancelled ? 'Price list import cancelled' : ($dryRun ? 'Price list dry-run completed' : 'Price list import completed'),
            $report
        );
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

    private function enrichImages(ProductParserItem $item, ?string $brand): void
    {
        try {
            $result = $this->search->searchForParser($item->sku, $brand, 'auto', false, $item->raw_name);

            foreach ($result['sources'] ?? [] as $source) {
                ProductParserSource::create([
                    'parser_item_id' => $item->id,
                    'url' => $source['url'],
                    'domain' => $source['domain'] ?? parse_url($source['url'], PHP_URL_HOST),
                    'title' => $source['title'] ?? null,
                    'snippet' => $source['snippet'] ?? null,
                    'source_type' => $source['source_type'] ?? 'generic',
                    'confidence_score' => $source['confidence_score'] ?? null,
                    'raw_data_json' => $source['raw_data_json'] ?? null,
                ]);
            }

            $images = array_values(array_filter($result['images'] ?? []));
            $imageSourceDomain = $result['fallback_source_used'] ?? false
                ? ($result['fallback_source_domain'] ?? null)
                : ($result['official_source_domain'] ?? null);
            $this->imageCollector->collect($item, $images, $imageSourceDomain);
            $content = $this->contentBuilder->mergeOfficialContent(
                [
                    'name_ru' => $item->name_ru,
                    'name_ro' => $item->name_ro,
                    'short_description_ru' => $item->short_description_ru,
                    'short_description_ro' => $item->short_description_ro,
                    'description_ru' => $item->description_ru,
                    'description_ro' => $item->description_ro,
                    'needs_translation_review' => $item->needs_translation_review,
                    'needs_content_review' => $item->needs_content_review,
                    'generated_content' => $item->generated_content,
                ],
                $result['title'] ?? null,
                $result['description'] ?? null,
                $item->sku,
                $brand,
            );
            $item->forceFill([
                'found_title' => $result['title'] ?? $item->found_title,
                'found_description' => $result['description'] ?? $item->found_description,
                'found_specs_json' => array_merge($item->found_specs_json ?: [], $result['specs'] ?? []),
                'found_images_json' => $images,
                'selected_images_json' => collect($images)->take(1)->values()->all(),
                'source_urls_json' => $result['source_urls'] ?? [],
                'tristools_url' => collect($result['sources'] ?? [])->firstWhere('domain', 'tristool.md')['url'] ?? (($result['source_urls'][0] ?? null)),
                'tristools_match_confidence' => $result['confidence'] ?? null,
                'needs_image_review' => true,
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
                'needs_translation_review' => (bool) $content['needs_translation_review'],
                'needs_content_review' => (bool) $content['needs_content_review'],
                'generated_content' => (bool) $content['generated_content'],
            ])->save();
        } catch (Throwable $e) {
            $item->forceFill([
                'needs_image_review' => true,
                'error_message' => trim(($item->error_message ? $item->error_message.' ' : '').'Image search failed: '.$e->getMessage()),
            ])->save();
            $item->batch?->addLog('Image search failed', ['sku' => $item->sku, 'error' => $e->getMessage()]);
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

        Product::with('brand:id,name')
            ->select(['id', 'sku', 'brand_id', 'status', 'source_import_batch_id'])
            ->get()
            ->each(function (Product $product) use (&$index) {
                $normalizedSku = $this->normalizeSku($product->sku);
                if ($normalizedSku === '') {
                    return;
                }

                $brandKey = $this->brandKey($product->brand?->name);
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
