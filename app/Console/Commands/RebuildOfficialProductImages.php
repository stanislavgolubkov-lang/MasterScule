<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductParserBatch;
use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use App\Models\ProductParserSource;
use App\Services\ProductImageCollectorService;
use App\Services\ProductImageProcessorService;
use App\Services\ProductParserContentBuilder;
use App\Services\ProductSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RebuildOfficialProductImages extends Command
{
    protected $signature = 'masterscule:rebuild-official-product-images
        {--purge : Remove every current product image file and database reference first}
        {--purge-only : Stop after removing current product media}
        {--force : Confirm the destructive purge operation}
        {--limit=0 : Maximum products to process}
        {--after-id=0 : Start after this product ID}
        {--brand= : Filter by brand name}
        {--sku= : Process one exact SKU}
        {--min=3 : Desired image count per product}
        {--available-only : Process only products currently available for sale}
        {--shard=0 : Zero-based shard number}
        {--shards=1 : Total number of independent shards}
        {--quiet-output : Do not print each product result}';

    protected $description = 'Delete legacy product media and rebuild it from exact-SKU official manufacturer sources only';

    public function handle(
        ProductSearchService $search,
        ProductImageCollectorService $collector,
        ProductImageProcessorService $processor,
        ProductParserContentBuilder $contentBuilder,
    ): int {
        if ($this->option('purge')) {
            if (! $this->option('force')) {
                $this->error('The --purge operation requires --force.');

                return self::FAILURE;
            }

            $this->purgeProductMedia();

            if ($this->option('purge-only')) {
                return self::SUCCESS;
            }
        }

        $limit = max(0, (int) $this->option('limit'));
        $afterId = max(0, (int) $this->option('after-id'));
        $brand = trim((string) $this->option('brand'));
        $sku = trim((string) $this->option('sku'));
        $minimum = max(1, min(4, (int) $this->option('min')));
        $shards = max(1, (int) $this->option('shards'));
        $shard = max(0, min($shards - 1, (int) $this->option('shard')));
        $quiet = (bool) $this->option('quiet-output');

        $query = Product::with('brand')->where('id', '>', $afterId)->orderBy('id');

        if ($brand !== '') {
            $query->whereHas('brand', fn ($brands) => $brands->where('name', 'like', '%'.$brand.'%'));
        }

        if ($sku !== '') {
            $query->where('sku', $sku);
        }

        if ($this->option('available-only')) {
            $query->availableForSale();
        }

        if ($shards > 1) {
            $query->whereRaw('MOD(products.id, ?) = ?', [$shards, $shard]);
        }

        $batch = ProductParserBatch::create([
            'title' => 'Official media rebuild '.now()->format('Y-m-d H:i:s').' shard '.$shard.'/'.$shards,
            'source_type' => 'official_media_rebuild',
            'sku_count' => 0,
            'status' => 'processing',
            'started_at' => now(),
            'options_json' => [
                'official_only' => true,
                'brand' => $brand,
                'sku' => $sku,
                'minimum_images' => $minimum,
                'after_id' => $afterId,
                'shard' => $shard,
                'shards' => $shards,
            ],
        ]);
        $stats = [
            'checked' => 0,
            'updated' => 0,
            'with_three_or_more' => 0,
            'below_minimum' => 0,
            'not_found' => 0,
            'errors' => 0,
            'last_id' => null,
        ];

        foreach ($query->cursor() as $product) {
            if ($limit > 0 && $stats['checked'] >= $limit) {
                break;
            }

            $stats['checked']++;
            $stats['last_id'] = $product->id;

            try {
                $result = $search->search($product->sku, $product->brand?->name, 'auto', false);
                $images = collect($result['images'] ?? [])->filter()->unique()->take(4)->values()->all();
                $sourceItem = ProductParserItem::find($product->source_parser_item_id)
                    ?: ProductParserItem::where('sku', $product->sku)->latest('id')->first();
                $content = $contentBuilder->build(
                    $product->sku,
                    $sourceItem?->raw_name ?: $sourceItem?->parsed_name ?: $product->name,
                    $product->brand?->name,
                    $sourceItem?->detected_group,
                );

                if ($result['official_content_found'] ?? false) {
                    $content = $contentBuilder->mergeOfficialContent(
                        $content,
                        $result['title'] ?? null,
                        $result['description'] ?? null,
                        $product->sku,
                        $product->brand?->name,
                    );
                }

                if ($images === []) {
                    $product->forceFill(['needs_image_review' => true])->save();
                    $stats['not_found']++;

                    if (! $quiet) {
                        $this->warn("Official image not found: {$product->sku}");
                    }

                    continue;
                }

                $item = ProductParserItem::create([
                    'batch_id' => $batch->id,
                    'sku' => $product->sku,
                    'brand' => $product->brand?->name,
                    'category_id' => $product->category_id,
                    'status' => 'images_found',
                    'confidence_score' => $result['confidence'] ?? 96,
                    'found_title' => $product->display_name,
                    'found_images_json' => $images,
                    'source_urls_json' => $result['source_urls'] ?? [],
                    'existing_product_id' => $product->id,
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description_ru' => $content['short_description_ru'],
                    'short_description_ro' => $content['short_description_ro'],
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'needs_image_review' => count($images) < $minimum,
                    'approval_status' => 'approved',
                ]);

                foreach ($result['sources'] ?? [] as $source) {
                    if (($source['source_type'] ?? null) !== 'official_brand' || Str::contains(Str::lower((string) ($source['url'] ?? '')), 'tristool.')) {
                        continue;
                    }

                    ProductParserSource::create([
                        'parser_item_id' => $item->id,
                        'url' => $source['url'],
                        'domain' => $source['domain'] ?? parse_url($source['url'], PHP_URL_HOST),
                        'title' => $source['title'] ?? null,
                        'snippet' => $source['snippet'] ?? null,
                        'source_type' => 'official_brand',
                        'confidence_score' => $source['confidence_score'] ?? 96,
                        'raw_data_json' => $source['raw_data_json'] ?? null,
                    ]);
                }

                $collector->collect($item, $images);
                $processor->processSelected($item->fresh(['imageAssets', 'batch']));
                $assets = $item->imageAssets()
                    ->where('status', 'processed')
                    ->orderByDesc('is_main')
                    ->orderBy('id')
                    ->get();

                if ($assets->isEmpty()) {
                    $product->forceFill(['needs_image_review' => true])->save();
                    $stats['errors']++;

                    continue;
                }

                $paths = $assets->pluck('processed_path')->filter()->unique()->values()->all();
                $officialPage = collect($result['sources'] ?? [])->firstWhere('source_type', 'official_brand')['url'] ?? null;

                DB::transaction(function () use ($product, $assets, $paths, $officialPage, $minimum, $result, $content) {
                    ProductImage::where('product_id', $product->id)->delete();

                    foreach ($assets as $index => $asset) {
                        $relative = Str::after((string) $asset->processed_path, '/storage/');
                        $absolute = Storage::disk('public')->path($relative);

                        ProductImage::create([
                            'product_id' => $product->id,
                            'path' => $asset->processed_path,
                            'source_url' => $asset->source_url,
                            'source_page_url' => $officialPage,
                            'source_domain' => parse_url((string) $officialPage, PHP_URL_HOST) ?: $asset->source_domain,
                            'is_official' => true,
                            'mime_type' => 'image/webp',
                            'width' => $asset->width,
                            'height' => $asset->height,
                            'file_size' => is_file($absolute) ? filesize($absolute) : null,
                            'alt' => $product->display_name,
                            'sort_order' => $index + 1,
                        ]);
                    }

                    $product->forceFill([
                        'name' => $content['name_ru'],
                        'name_ro' => $content['name_ro'],
                        'short_description' => $content['short_description_ru'],
                        'description' => $content['description_ru'],
                        'description_ro' => $content['description_ro'],
                        'main_image' => $paths[0],
                        'gallery' => $paths,
                        'needs_image_review' => count($paths) < $minimum,
                        'parser_confidence' => $result['confidence'] ?? 96,
                        'parser_source_urls' => $result['source_urls'] ?? [],
                        'attributes' => array_merge($product->attributes ?: [], $result['specs'] ?? []),
                    ])->save();
                });

                $stats['updated']++;
                count($paths) >= 3 ? $stats['with_three_or_more']++ : $stats['below_minimum']++;

                if (! $quiet) {
                    $this->line("Official images {$product->sku}: ".count($paths));
                }
            } catch (Throwable $exception) {
                $stats['errors']++;
                $product->forceFill(['needs_image_review' => true])->save();

                if (! $quiet) {
                    $this->error("{$product->sku}: {$exception->getMessage()}");
                }
            }
        }

        $batch->forceFill([
            'status' => $stats['errors'] > 0 ? 'completed_with_errors' : 'completed',
            'sku_count' => $stats['checked'],
            'finished_at' => now(),
            'options_json' => array_merge($batch->options_json ?: [], ['stats' => $stats]),
        ])->save();

        $this->info(json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function purgeProductMedia(): void
    {
        $targets = [
            public_path('images/products'),
            storage_path('app/public/products'),
            storage_path('app/public/parser/imports'),
        ];
        $roots = [realpath(public_path()) ?: public_path(), realpath(storage_path('app/public')) ?: storage_path('app/public')];

        foreach ($targets as $target) {
            $normalized = str_replace('\\', '/', $target);
            $insideAllowedRoot = collect($roots)->contains(fn ($root) => Str::startsWith($normalized, str_replace('\\', '/', $root).'/'));

            if (! $insideAllowedRoot) {
                throw new \RuntimeException('Refusing to delete media outside the project roots: '.$target);
            }

            if (File::isDirectory($target)) {
                File::deleteDirectory($target);
            }
        }

        DB::transaction(function () {
            ProductImage::query()->delete();
            ProductParserImageAsset::query()->delete();
            ProductParserSource::where('domain', 'tristool.md')->delete();
            ProductParserItem::query()->update([
                'found_images_json' => null,
                'selected_images_json' => null,
                'processed_images_json' => null,
                'needs_image_review' => true,
                'tristools_url' => null,
                'tristools_match_confidence' => null,
            ]);
            Product::query()->update([
                'main_image' => null,
                'gallery' => null,
                'needs_image_review' => true,
                'parser_source_urls' => null,
            ]);
        });

        $this->info('All legacy product media files and image references were removed.');
    }
}
