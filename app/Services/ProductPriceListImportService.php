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
        private ProductImageProcessorService $imageProcessor,
        private ProductDraftService $drafts,
    ) {
    }

    public function import(ProductParserBatch $batch): void
    {
        $batch->forceFill([
            'status' => 'processing',
            'started_at' => $batch->started_at ?: now(),
        ])->save();
        $batch->addLog('Price list import started', [
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
            $batch->addLog('Price list import failed', ['error' => $e->getMessage()]);

            return;
        }

        $options = $batch->options_json ?: [];
        $context = [
            'brand' => $this->brandValue($batch->brand_default),
            'group' => null,
            'subgroup' => null,
        ];
        $seenSkus = [];
        $stats = [
            'parsed_rows' => 0,
            'product_rows' => 0,
            'created_drafts' => 0,
            'updated_existing' => 0,
            'skipped_rows' => 0,
            'error_rows' => 0,
        ];

        ProductParserItem::where('batch_id', $batch->id)->delete();

        foreach ($parsed['rows'] as $row) {
            $stats['parsed_rows']++;

            try {
                if ($this->isServiceRow($row)) {
                    $this->applyContext($context, $row);
                    $stats['skipped_rows']++;
                    continue;
                }

                if (! $this->isProductRow($row)) {
                    $stats['skipped_rows']++;
                    continue;
                }

                $sku = $this->cleanSku((string) $row['sku']);
                if (isset($seenSkus[Str::lower($sku)])) {
                    $this->skippedItem($batch, $row, $sku, 'Duplicate SKU inside price list.');
                    $stats['skipped_rows']++;
                    continue;
                }
                $seenSkus[Str::lower($sku)] = true;
                $stats['product_rows']++;

                $brand = $this->brandValue($row['brand'] ?? null) ?: $context['brand'];
                $group = $row['group'] ?: $context['group'];
                $subgroup = $row['subgroup'] ?: $context['subgroup'];
                $name = trim((string) $row['name']);
                $price = $this->parsePrice($row['price'] ?? null);
                $stock = $this->parseStock($row['stock'] ?? null);
                $needsStockReview = $stock === null;
                $existing = Product::where('sku', $sku)->first();
                $category = $this->categoryDetector->detect($sku, $name, $brand, $group, $subgroup);
                $content = $this->contentBuilder->build($sku, $name, $brand, $group);
                $item = ProductParserItem::create([
                    'batch_id' => $batch->id,
                    'row_number' => $row['row_number'],
                    'sku' => $sku,
                    'brand' => $brand,
                    'category_id' => $category['category_id'] ?: ($batch->category_default_id ?: null),
                    'status' => $existing ? 'existing_product_found' : ($category['needs_review'] ? 'needs_category_review' : 'parsed'),
                    'confidence_score' => $category['confidence'],
                    'raw_name' => $row['name'],
                    'parsed_name' => $name,
                    'raw_price' => $row['price'],
                    'parsed_price' => $price,
                    'raw_stock' => $row['stock'],
                    'parsed_stock' => $stock,
                    'detected_group' => $group,
                    'detected_subgroup' => $subgroup,
                    'detected_category_id' => $category['detected_category_id'],
                    'detected_category_path' => $category['detected_category_path'],
                    'category_confidence_score' => $category['confidence'],
                    'category_detection_method' => $category['method'],
                    'category_detection_notes_json' => $category['notes'],
                    'needs_category_review' => $category['needs_review'],
                    'needs_stock_review' => $needsStockReview,
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
                        'Price source' => 'ОтпускЦена / retail',
                    ], fn ($value) => $value !== null && $value !== ''),
                    'existing_product_id' => $existing?->id,
                ]);

                if ($existing) {
                    $stats['updated_existing']++;
                    if (($options['search_images'] ?? true) === true && ($options['add_photos_to_existing'] ?? true) === true) {
                        $this->enrichImages($item, $brand);
                        $item->refresh();

                        if (($options['process_images'] ?? true) === true && $item->imageAssets()->where('is_selected', true)->exists()) {
                            $this->imageProcessor->processSelected($item->fresh(['imageAssets', 'batch']));
                            $item->refresh();
                        }

                        $item->forceFill([
                            'needs_image_review' => $item->imageAssets()->count() < 3,
                            'status' => 'existing_product_found',
                        ])->save();
                    }
                    $batch->addLog('Existing product found. No automatic update was made.', ['sku' => $sku, 'product_id' => $existing->id]);
                    continue;
                }

                if (($options['search_images'] ?? true) === true) {
                    $this->enrichImages($item, $brand);
                }

                $item->refresh();
                $needsImageReview = $item->imageAssets()->count() < 3;
                $item->forceFill([
                    'needs_image_review' => $needsImageReview,
                    'status' => $item->needs_category_review ? 'needs_category_review' : 'ready_for_review',
                ])->save();

                if (($options['process_images'] ?? true) === true && $item->imageAssets()->where('is_selected', true)->exists()) {
                    $this->imageProcessor->processSelected($item->fresh(['imageAssets', 'batch']));
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

        $batch->forceFill([
            'sku_count' => $stats['product_rows'],
            'total_rows' => $parsed['total_rows'],
            'parsed_rows' => $stats['parsed_rows'],
            'product_rows' => $stats['product_rows'],
            'created_drafts' => $stats['created_drafts'],
            'updated_existing' => $stats['updated_existing'],
            'skipped_rows' => $stats['skipped_rows'],
            'error_rows' => $stats['error_rows'],
            'status' => $stats['error_rows'] > 0 ? 'completed_with_errors' : 'completed',
            'finished_at' => now(),
        ])->save();
        $batch->addLog('Price list import completed', $stats + ['sheet' => $parsed['sheet'] ?? null]);
    }

    private function enrichImages(ProductParserItem $item, ?string $brand): void
    {
        try {
            $result = $this->search->search($item->sku, $brand, 'auto');

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
            $this->imageCollector->collect($item, $images);
            $item->forceFill([
                'found_images_json' => $images,
                'selected_images_json' => collect($images)->take(4)->values()->all(),
                'source_urls_json' => $result['source_urls'] ?? [],
                'needs_image_review' => count($images) < 3,
            ])->save();
        } catch (Throwable $e) {
            $item->forceFill([
                'needs_image_review' => true,
                'error_message' => trim(($item->error_message ? $item->error_message.' ' : '').'Image search failed: '.$e->getMessage()),
            ])->save();
            $item->batch?->addLog('Image search failed', ['sku' => $item->sku, 'error' => $e->getMessage()]);
        }
    }

    private function skippedItem(ProductParserBatch $batch, array $row, ?string $sku, string $reason): void
    {
        ProductParserItem::create([
            'batch_id' => $batch->id,
            'row_number' => $row['row_number'] ?? null,
            'sku' => $sku ?: 'row-'.$row['row_number'],
            'raw_name' => $row['name'] ?? null,
            'raw_price' => $row['price'] ?? null,
            'raw_stock' => $row['stock'] ?? null,
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
        $lower = Str::lower($text);

        if (Str::contains($lower, ['king tony', 'kingtony'])) {
            $context['brand'] = 'King Tony';
        } elseif (Str::contains($lower, ['m7', 'mighty seven'])) {
            $context['brand'] = 'M7 / Mighty Seven';
        } elseif (mb_strlen($text) <= 80) {
            if ($context['group'] && $context['group'] !== $text) {
                $context['subgroup'] = $text;
            } else {
                $context['group'] = $text;
            }
        }
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

    private function looksLikeSku(string $sku): bool
    {
        return (bool) preg_match('/[0-9]/', $sku) && mb_strlen($sku) <= 80;
    }

    private function brandValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || $value === 'auto' ? null : $value;
    }
}
