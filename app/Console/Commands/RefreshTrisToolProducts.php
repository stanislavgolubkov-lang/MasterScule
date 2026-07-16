<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductParserBatch;
use App\Models\ProductParserItem;
use App\Models\ProductParserSource;
use App\Services\ProductImageCollectorService;
use App\Services\ProductImageProcessorService;
use App\Services\ProductParserContentBuilder;
use App\Services\TrisToolsEnrichmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class RefreshTrisToolProducts extends Command
{
    protected $signature = 'masterscule:refresh-tristool-products
        {--sku= : Refresh one SKU}
        {--limit=25 : Maximum products to process}
        {--commit : Apply updates; without this option the command is a dry-run}
        {--with-images : Re-process gallery images from TrisTool}';

    protected $description = 'Refresh already imported products from saved TrisTool source URLs';

    public function handle(
        TrisToolsEnrichmentService $tristool,
        ProductParserContentBuilder $contentBuilder,
        ProductImageCollectorService $collector,
        ProductImageProcessorService $processor,
    ): int {
        $commit = (bool) $this->option('commit');
        $withImages = (bool) $this->option('with-images');
        $limit = max(1, (int) $this->option('limit'));
        $sku = trim((string) $this->option('sku'));
        $stats = [
            'checked' => 0,
            'matched' => 0,
            'would_update' => 0,
            'updated' => 0,
            'images_processed' => 0,
            'errors' => 0,
        ];

        $products = Product::with(['brand', 'category'])
            ->where('source_domain', 'tristool.md')
            ->whereNotNull('source_url')
            ->when($sku !== '', fn ($query) => $query->where('sku', $sku))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($products as $product) {
            $stats['checked']++;

            try {
                $result = $tristool->enrichUrl((string) $product->source_url, (string) $product->sku, $product->brand?->name);
                if (! ($result['found'] ?? false)) {
                    $stats['errors']++;
                    $this->warn("{$product->sku}: source refresh failed: ".implode(', ', $result['warnings'] ?? []));
                    continue;
                }

                $stats['matched']++;
                $content = $contentBuilder->mergeOfficialContent(
                    [
                        'name_ru' => $product->name_ru ?: $product->name,
                        'name_ro' => $product->name_ro,
                        'short_description_ru' => $product->short_description_ru ?: $product->short_description,
                        'short_description_ro' => $product->short_description_ro,
                        'description_ru' => $product->description_ru ?: $product->description,
                        'description_ro' => $product->description_ro,
                        'needs_translation_review' => (bool) $product->needs_translation_review,
                        'needs_content_review' => (bool) $product->needs_content_review,
                        'generated_content' => (bool) $product->generated_content,
                    ],
                    $this->stripTrisToolTitle((string) ($result['title'] ?? '')),
                    $result['description'] ?? null,
                    (string) $product->sku,
                    $product->brand?->name,
                );

                $category = $this->categoryFromBreadcrumb($result['breadcrumb'] ?? []);
                $updates = [
                    'name' => $content['name_ru'],
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description' => $content['short_description_ru'],
                    'short_description_ru' => $content['short_description_ru'],
                    'short_description_ro' => $content['short_description_ro'],
                    'description' => $content['description_ru'],
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'attributes' => ($result['specs'] ?? []) ?: $product->attributes,
                    'package_contents' => ($result['package_contents'] ?? []) ?: $product->package_contents,
                    'parser_source_urls' => array_values(array_unique(array_filter(array_merge(
                        $product->parser_source_urls ?: [],
                        $result['source_urls'] ?? [],
                        $result['images'] ?? [],
                    )))),
                    'parser_confidence' => max((int) ($product->parser_confidence ?? 0), (int) ($result['confidence'] ?? 0)),
                    'needs_content_review' => (bool) $content['needs_content_review'],
                    'needs_translation_review' => (bool) $content['needs_translation_review'],
                    'generated_content' => (bool) $content['generated_content'],
                ];

                if ($category) {
                    $updates['category_id'] = $category->id;
                    $updates['needs_category_review'] = false;
                }

                $willUpdate = $this->hasChanges($product, $updates);
                if ($willUpdate || $withImages) {
                    $stats['would_update']++;
                }

                $this->line("{$product->sku}: ".($willUpdate || $withImages ? 'refresh ready' : 'already current'));

                if (! $commit) {
                    continue;
                }

                $product->forceFill($updates)->save();
                if ($category) {
                    $product->syncCategoryLinks([$category->id], $category->id, 'tristool_refresh');
                }

                $item = $this->parserItem($product);
                $item->forceFill([
                    'found_title' => $this->stripTrisToolTitle((string) ($result['title'] ?? $product->name)),
                    'found_description' => $result['description'] ?? $item->found_description,
                    'found_specs_json' => ($result['specs'] ?? []) ?: $item->found_specs_json,
                    'found_images_json' => $result['images'] ?? $item->found_images_json,
                    'source_urls_json' => $result['source_urls'] ?? $item->source_urls_json,
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description_ru' => $content['short_description_ru'],
                    'short_description_ro' => $content['short_description_ro'],
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'needs_content_review' => (bool) $content['needs_content_review'],
                    'needs_translation_review' => (bool) $content['needs_translation_review'],
                    'generated_content' => (bool) $content['generated_content'],
                ])->save();

                ProductParserSource::where('parser_item_id', $item->id)->delete();
                foreach ($result['sources'] ?? [] as $source) {
                    ProductParserSource::create([
                        'parser_item_id' => $item->id,
                        'url' => $source['url'],
                        'domain' => $source['domain'] ?? parse_url($source['url'], PHP_URL_HOST),
                        'title' => $source['title'] ?? null,
                        'snippet' => $source['snippet'] ?? null,
                        'source_type' => $source['source_type'] ?? 'tristools_source_refresh',
                        'confidence_score' => $source['confidence_score'] ?? null,
                        'raw_data_json' => $source['raw_data_json'] ?? null,
                    ]);
                }

                if ($withImages && ! empty($result['images'])) {
                    $collector->collect($item->fresh(), $result['images'], 'tristool.md');
                    $this->selectAllFoundImages($item->fresh());
                    $processor->processSelected($item->fresh(['imageAssets', 'batch']));
                    $this->syncProductImages($product->fresh(), $item->fresh(['imageAssets']));
                    $stats['images_processed']++;
                }

                $stats['updated']++;
            } catch (Throwable $exception) {
                $stats['errors']++;
                $this->error("{$product->sku}: {$exception->getMessage()}");
            }
        }

        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());
        $this->info($commit ? 'TrisTool refresh applied.' : 'Dry-run only. Re-run with --commit to apply changes.');

        return self::SUCCESS;
    }

    private function parserItem(Product $product): ProductParserItem
    {
        if ($product->source_parser_item_id && ($item = ProductParserItem::find($product->source_parser_item_id))) {
            return $item;
        }

        return ProductParserItem::firstOrCreate(
            ['sku' => $product->sku, 'existing_product_id' => $product->id],
            [
                'batch_id' => $product->source_import_batch_id ?: $this->refreshBatch()->id,
                'brand' => $product->brand?->name,
                'category_id' => $product->category_id,
                'status' => 'approved',
            ],
        );
    }

    private function refreshBatch(): ProductParserBatch
    {
        return ProductParserBatch::firstOrCreate(
            ['title' => 'TrisTool source refresh'],
            ['source_type' => 'refresh', 'status' => 'running'],
        );
    }

    private function selectAllFoundImages(ProductParserItem $item): void
    {
        $assets = $item->imageAssets()->orderBy('id')->get();
        foreach ($assets as $index => $asset) {
            $asset->forceFill([
                'is_selected' => true,
                'is_main' => $index === 0,
                'status' => $asset->status === 'processed' ? 'processed' : 'found',
                'error_message' => null,
            ])->save();
        }
    }

    private function syncProductImages(Product $product, ProductParserItem $item): void
    {
        $images = $item->imageAssets()
            ->where('is_selected', true)
            ->where('status', 'processed')
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->pluck('processed_path')
            ->filter()
            ->values()
            ->all();

        if ($images === []) {
            return;
        }

        $product->forceFill([
            'main_image' => $images[0],
            'gallery' => $images,
            'needs_image_review' => false,
        ])->save();

        ProductImage::where('product_id', $product->id)->delete();
        foreach ($item->imageAssets()->where('is_selected', true)->where('status', 'processed')->orderByDesc('is_main')->orderBy('id')->get() as $index => $asset) {
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $asset->processed_path,
                'source_url' => $asset->source_url,
                'source_page_url' => $product->source_url,
                'source_domain' => $asset->source_domain,
                'mime_type' => $asset->mime_type,
                'width' => $asset->width,
                'height' => $asset->height,
                'alt' => $product->display_name,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function categoryFromBreadcrumb(array $breadcrumb): ?Category
    {
        $text = Str::lower(implode(' ', $breadcrumb));

        $slug = match (true) {
            Str::contains($text, ['рихтов', 'покраск', 'tinichigerie', 'richtuire']) => 'tinichigerie-si-richtuire',
            default => null,
        };

        return $slug ? Category::where('slug', $slug)->first() : null;
    }

    private function stripTrisToolTitle(string $title): string
    {
        return trim((string) preg_replace('/^\s*TrisTool\.md\s*[-–—:|]\s*/iu', '', $title));
    }

    private function hasChanges(Product $product, array $updates): bool
    {
        foreach ($updates as $field => $value) {
            if ($product->{$field} != $value) {
                return true;
            }
        }

        return false;
    }
}
