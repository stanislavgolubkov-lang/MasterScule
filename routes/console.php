<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductParserBatch;
use App\Models\ProductParserItem;
use App\Models\ProductParserSource;
use App\Services\Catalog\ProductPublicationGuard;
use App\Services\ProductCatalogClassifier;
use App\Services\ProductFallbackImageService;
use App\Services\ProductImageCollectorService;
use App\Services\ProductImageProcessorService;
use App\Services\ProductPriceListImportService;
use App\Services\ProductSearchService;
use App\Support\ProductLocalizer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('masterscule:import-tristool-products {--king=200} {--m7=100}', function () {
    $this->error('Direct TrisTools catalog import is disabled. Use the admin parser enrichment workflow.');

    return 1;

    $targets = [
        [
            'brand_name' => 'King Tony',
            'brand_slug' => 'king-tony',
            'query' => 'King Tony',
            'limit' => max(0, (int) $this->option('king')),
        ],
        [
            'brand_name' => 'M7 / Mighty Seven',
            'brand_slug' => 'm7-mighty-seven',
            'query' => 'Mighty Seven',
            'limit' => max(0, (int) $this->option('m7')),
        ],
    ];

    $totalImported = 0;

    foreach ($targets as $target) {
        $brand = ensureTrisToolBrand($target['brand_name'], $target['brand_slug']);
        $current = Product::where('brand_id', $brand->id)->count();

        if ($current >= $target['limit']) {
            $this->info("{$target['brand_name']}: already has {$current} products, target is {$target['limit']}.");

            continue;
        }

        $page = 1;
        $seen = [];

        $this->info("{$target['brand_name']}: {$current}/{$target['limit']} products. Importing missing products...");

        while ($current < $target['limit'] && $page <= 160) {
            $url = 'https://tristool.md/ru/search?searchword='.rawurlencode($target['query']).'&p='.$page;
            $response = tristoolHttp()->withHeaders([
                'User-Agent' => 'MasterScule.md product import/1.0',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->timeout(30)->retry(2, 500)->get($url);

            if (! $response->successful()) {
                $this->warn("Page {$page} failed with HTTP {$response->status()}.");
                $page++;

                continue;
            }

            $cards = parseTrisToolCards($response->body());

            if ($cards === []) {
                $page++;

                continue;
            }

            foreach ($cards as $card) {
                if ($current >= $target['limit']) {
                    break;
                }

                $sku = trim($card['sku']);
                if ($sku === '' || isset($seen[$sku]) || Product::where('sku', $sku)->exists()) {
                    $seen[$sku] = true;

                    continue;
                }

                $title = cleanTrisToolTitle($card['title']);
                if ($title === '') {
                    continue;
                }

                $seen[$sku] = true;
                $category = categoryForTrisToolTitle($title, $target['brand_slug']);
                $image = downloadTrisToolImage($card['image'], $sku, $target['brand_slug']);
                $price = parseMdlPrice($card['price']);
                $oldPrice = (($current + $page) % 7 === 0) ? round($price * 1.12, 2) : null;

                $displayName = ProductLocalizer::name($title, $target['brand_name'], $sku);

                $product = Product::create([
                    'brand_id' => $brand->id,
                    'category_id' => $category->id,
                    'name' => normalizeProductName($title, $target['brand_name']),
                    'name_ro' => $displayName,
                    'slug' => uniqueProductSlug($title, $sku),
                    'sku' => $sku,
                    'short_description' => ProductLocalizer::shortDescription($displayName, $target['brand_name']),
                    'description' => ProductLocalizer::fullDescription($displayName, $target['brand_name'], $sku),
                    'description_ro' => ProductLocalizer::fullDescription($displayName, $target['brand_name'], $sku),
                    'price' => $price,
                    'old_price' => $oldPrice,
                    'currency' => 'MDL',
                    'stock_quantity' => 4 + ((crc32($sku) % 18)),
                    'stock_status' => 'in_stock',
                    'main_image' => $image,
                    'gallery' => [$image],
                    'attributes' => attributesForTrisToolTitle($title, $sku, $target['brand_name']),
                    'package_contents' => packageForTrisToolTitle($title),
                    'rating' => 4.5 + ((crc32($sku) % 5) / 10),
                    'reviews_count' => 6 + (crc32($sku) % 48),
                    'status' => 'draft',
                    'approval_status' => 'pending_review',
                    'needs_review' => true,
                    'needs_image_review' => true,
                    'needs_translation_review' => true,
                    'is_active' => false,
                    'is_featured' => $current < 16,
                    'is_bestseller' => $current % 6 === 0,
                    'is_new' => $current % 5 === 0,
                    'is_discounted' => $oldPrice !== null,
                    'warranty' => '24 luni',
                    'meta_title' => $displayName.' | MasterScule.md',
                    'meta_description' => Str::limit(ProductLocalizer::shortDescription($displayName, $target['brand_name']), 150),
                ]);

                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'path' => $image],
                    ['alt' => $product->name, 'sort_order' => 1]
                );
                $product->syncCategoryLinks([$category->id], $category->id, 'tristool_import');

                $current++;
                $totalImported++;
            }

            $this->line("Page {$page}: {$current}/{$target['limit']} {$target['brand_name']} products.");
            $page++;
        }

        if ($current < $target['limit']) {
            $this->warn("{$target['brand_name']}: target not reached. Current count: {$current}.");
        }
    }

    $this->info("Done. Imported {$totalImported} new products.");
})->purpose('Disabled: direct TrisTools catalog import');

Artisan::command('masterscule:repair-tristool-mdl-prices {--apply} {--force} {--limit=0}', function () {
    $dryRun = ! (bool) $this->option('apply');
    if (! $dryRun && ! $this->option('force')) {
        $this->error('Price changes require both --apply and --force.');

        return 1;
    }
    $limit = max(0, (int) $this->option('limit'));
    $query = Product::where('main_image', 'like', '%/tristool/%')
        ->whereNull('source_parser_item_id')
        ->orderBy('id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $checked = 0;
    $changed = 0;
    $notFound = 0;

    foreach ($query->get() as $product) {
        $checked++;
        $card = findTrisToolCardBySku($product->sku);

        if (! $card) {
            $notFound++;
            $this->warn("{$product->sku}: price not found on TrisTool.");

            continue;
        }

        $newPrice = parseMdlPrice($card['price']);
        $newOldPrice = $product->old_price !== null ? round($newPrice * 1.12, 2) : null;
        $oldPrice = (float) $product->price;

        if (abs($oldPrice - $newPrice) < 0.01 && $product->currency === 'MDL') {
            continue;
        }

        $changed++;
        $this->line(sprintf(
            '%s: %s MDL -> %s MDL%s',
            $product->sku,
            number_format($oldPrice, 2, '.', ''),
            number_format($newPrice, 2, '.', ''),
            $dryRun ? ' [dry-run]' : ''
        ));

        if (! $dryRun) {
            $product->forceFill([
                'price' => $newPrice,
                'old_price' => $newOldPrice,
                'currency' => 'MDL',
                'is_discounted' => $newOldPrice !== null,
            ])->save();
        }
    }

    $this->info(json_encode([
        'checked' => $checked,
        'changed' => $changed,
        'not_found' => $notFound,
        'dry_run' => $dryRun,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->purpose('Repair legacy TrisTool imports that stored RON-converted values as MDL');

Artisan::command('masterscule:localize-products', function () {
    $this->error('Automatic bulk localization is disabled. Review RU/RO text in admin instead.');

    return 1;

    $updated = 0;

    Product::with('brand')->chunkById(100, function ($products) use (&$updated) {
        foreach ($products as $product) {
            $displayName = ProductLocalizer::name($product->name, $product->brand?->name ?? '', $product->sku);
            $description = ProductLocalizer::fullDescription($displayName, $product->brand?->name ?? '', $product->sku);

            $product->forceFill([
                'name_ro' => $displayName,
                'short_description' => ProductLocalizer::shortDescription($displayName, $product->brand?->name ?? ''),
                'description_ro' => $description,
                'description' => $description,
                'meta_title' => $displayName.' | MasterScule.md',
                'meta_description' => Str::limit(ProductLocalizer::shortDescription($displayName, $product->brand?->name ?? ''), 150),
            ])->save();

            $updated++;
        }
    });

    $this->info("Updated {$updated} product texts in RO.");
})->purpose('Normalize product display text for RO locale');

Artisan::command('masterscule:audit-product-categories {--apply} {--limit=0}', function (ProductCatalogClassifier $classifier) {
    $apply = (bool) $this->option('apply');
    $limit = max(0, (int) $this->option('limit'));
    $query = Product::with(['brand', 'category', 'categories'])->orderBy('id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $stats = [
        'checked' => 0,
        'changed_primary' => 0,
        'linked_multi_category' => 0,
        'missing_primary_category' => 0,
        'by_primary_slug' => [],
        'apply' => $apply,
    ];
    $samples = [];

    foreach ($query->get() as $product) {
        $stats['checked']++;
        $result = $classifier->classify($product);
        $primary = Category::where('slug', $result['primary_slug'])->first();

        if (! $primary) {
            $stats['missing_primary_category']++;

            continue;
        }

        $categoryIds = $classifier->idsForSlugs($result['category_slugs']);
        $confidenceById = $classifier->confidenceById($result['scores']);
        $oldSlug = $product->category?->slug;

        $stats['by_primary_slug'][$primary->slug] = ($stats['by_primary_slug'][$primary->slug] ?? 0) + 1;

        if ($oldSlug !== $primary->slug) {
            $stats['changed_primary']++;

            if (count($samples) < 30) {
                $samples[] = [
                    'sku' => $product->sku,
                    'brand' => $product->brand?->name,
                    'old' => $oldSlug,
                    'new' => $primary->slug,
                    'name' => $product->name,
                ];
            }
        }

        if (count($categoryIds) > 1) {
            $stats['linked_multi_category']++;
        }

        if ($apply) {
            $product->forceFill(['category_id' => $primary->id])->save();
            $product->syncCategoryLinks($categoryIds, $primary->id, 'catalog_audit', $confidenceById);
        }
    }

    arsort($stats['by_primary_slug']);

    $this->info(json_encode([
        'stats' => $stats,
        'primary_change_samples' => $samples,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->purpose('Audit and optionally reassign products into primary and additional catalog categories');

Artisan::command('masterscule:enrich-product-images {--apply} {--limit=0} {--min=3} {--quiet-output} {--fallback-only}', function (
    ProductSearchService $search,
    ProductImageCollectorService $collector,
    ProductImageProcessorService $processor,
    ProductFallbackImageService $fallbackImages
) {
    $apply = (bool) $this->option('apply');
    $limit = max(0, (int) $this->option('limit'));
    $minImages = max(2, min(4, (int) $this->option('min')));
    $quiet = (bool) $this->option('quiet-output');
    $fallbackOnly = (bool) $this->option('fallback-only');
    $isUsableImage = fn (?string $path): bool => filled($path) && ! Str::contains((string) $path, ['placeholder', 'product-placeholder']);
    $galleryFor = function (Product $product) use ($isUsableImage): array {
        return collect([$product->main_image])
            ->merge($product->gallery ?: [])
            ->merge($product->images->pluck('path'))
            ->filter(fn ($path) => $isUsableImage($path))
            ->unique()
            ->values()
            ->all();
    };
    $syncImages = function (Product $product, array $images): void {
        ProductImage::where('product_id', $product->id)->delete();

        foreach (array_values(array_filter($images)) as $index => $path) {
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'alt' => $product->display_name,
                'sort_order' => $index + 1,
            ]);
        }
    };

    $query = Product::with(['brand', 'images'])
        ->orderBy('id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $batch = $apply
        ? ProductParserBatch::firstOrCreate(
            ['title' => 'CLI catalog image audit enrichment'],
            ['source_type' => 'batch', 'sku_count' => 0, 'status' => 'running', 'options_json' => ['image_limit' => 4]]
        )
        : null;

    $stats = [
        'checked' => 0,
        'already_ok' => 0,
        'needs_images' => 0,
        'searched' => 0,
        'updated' => 0,
        'still_below_min' => 0,
        'not_found' => 0,
        'fallback_added' => 0,
        'errors' => 0,
        'apply' => $apply,
        'min_images' => $minImages,
        'fallback_only' => $fallbackOnly,
    ];
    $samples = [];

    foreach ($query->get() as $product) {
        $stats['checked']++;
        $currentImages = $galleryFor($product);

        if (count($currentImages) >= $minImages) {
            $stats['already_ok']++;

            if ($apply && $product->images->count() < count($currentImages)) {
                $syncImages($product, $currentImages);
            }

            continue;
        }

        $stats['needs_images']++;

        if (! $apply) {
            if (count($samples) < 30) {
                $samples[] = [
                    'sku' => $product->sku,
                    'brand' => $product->brand?->name,
                    'images' => count($currentImages),
                    'name' => $product->name,
                ];
            }

            continue;
        }

        if ($fallbackOnly) {
            $nextImages = collect($currentImages)
                ->merge($fallbackImages->generate($product, $minImages - count($currentImages)))
                ->filter(fn ($path) => $isUsableImage($path))
                ->unique()
                ->take($minImages)
                ->values()
                ->all();
            $stats['fallback_added'] += max(0, count($nextImages) - count($currentImages));

            if ($nextImages !== []) {
                $product->forceFill([
                    'main_image' => $nextImages[0],
                    'gallery' => $nextImages,
                    'needs_image_review' => true,
                ])->save();
                $syncImages($product, $nextImages);
                $stats['updated']++;
            }

            if (count($nextImages) < $minImages) {
                $stats['still_below_min']++;
            }

            if (! $quiet) {
                $this->line("Images {$product->sku}: ".count($currentImages).' -> '.count($nextImages).' (fallback-only)');
            }

            continue;
        }

        try {
            $stats['searched']++;
            $item = ProductParserItem::firstOrCreate(
                ['batch_id' => $batch->id, 'sku' => $product->sku],
                [
                    'brand' => $product->brand?->name,
                    'category_id' => $product->category_id,
                    'status' => 'queued',
                    'name_ru' => $product->name,
                    'name_ro' => $product->name_ro,
                    'description_ru' => $product->description,
                    'description_ro' => $product->description_ro,
                    'found_title' => $product->display_name,
                    'found_description' => $product->description ?: $product->short_description,
                    'existing_product_id' => $product->id,
                ]
            );

            $result = $search->search($product->sku, $product->brand?->name, 'auto', false);
            $images = array_values(array_unique(array_filter($result['images'] ?? [])));

            if (count($images) < $minImages) {
                $looseQuery = trim(implode(' ', array_filter([$product->sku, $product->name, $product->name_ro])));
                $loose = $search->searchLoose($looseQuery, $product->brand?->name);
                $images = array_values(array_unique(array_filter(array_merge($images, $loose['images'] ?? []))));
            }

            if ($images === []) {
                $stats['not_found']++;
                $nextImages = collect($currentImages)
                    ->merge($fallbackImages->generate($product, $minImages - count($currentImages)))
                    ->filter(fn ($path) => $isUsableImage($path))
                    ->unique()
                    ->take($minImages)
                    ->values()
                    ->all();
                $stats['fallback_added'] += max(0, count($nextImages) - count($currentImages));

                if ($nextImages !== []) {
                    $product->forceFill([
                        'main_image' => $nextImages[0],
                        'gallery' => $nextImages,
                        'needs_image_review' => true,
                    ])->save();
                    $syncImages($product, $nextImages);
                    $stats['updated']++;
                } else {
                    $product->forceFill(['needs_image_review' => true])->save();
                }

                if (count($nextImages) < $minImages) {
                    $stats['still_below_min']++;
                }

                if (! $quiet) {
                    $this->line("Images {$product->sku}: ".count($currentImages).' -> '.count($nextImages).' (fallback)');
                }

                continue;
            }

            $collector->collect($item, $images);
            $processor->processSelected($item->fresh(['imageAssets', 'batch']));

            $processed = $item->fresh()->processed_images_json ?: [];
            $nextImages = collect($currentImages)
                ->merge($processed)
                ->filter(fn ($path) => $isUsableImage($path))
                ->unique()
                ->take(max($minImages, 3))
                ->values()
                ->all();
            $fallbackUsed = false;

            if (count($nextImages) < $minImages) {
                $fallbacks = $fallbackImages->generate($product, $minImages - count($nextImages));
                $nextImages = collect($nextImages)
                    ->merge($fallbacks)
                    ->filter(fn ($path) => $isUsableImage($path))
                    ->unique()
                    ->take($minImages)
                    ->values()
                    ->all();
                $fallbackUsed = $fallbacks !== [];
                $stats['fallback_added'] += count($fallbacks);
            }

            if ($nextImages !== []) {
                $product->forceFill([
                    'main_image' => $nextImages[0],
                    'gallery' => $nextImages,
                    'needs_image_review' => $fallbackUsed || count($nextImages) < $minImages,
                    'parser_confidence' => $result['confidence'] ?? $product->parser_confidence,
                    'parser_source_urls' => array_values(array_unique(array_merge(
                        $product->parser_source_urls ?: [],
                        $result['source_urls'] ?? []
                    ))),
                ])->save();
                $syncImages($product, $nextImages);
                $stats['updated']++;
            }

            if (count($nextImages) < $minImages) {
                $stats['still_below_min']++;
            }

            if (! $quiet) {
                $this->line("Images {$product->sku}: ".count($currentImages).' -> '.count($nextImages));
            }
        } catch (Throwable $e) {
            $stats['errors']++;
            $product->forceFill(['needs_image_review' => true])->save();

            if (count($samples) < 30) {
                $samples[] = [
                    'sku' => $product->sku,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    $this->info(json_encode([
        'stats' => $stats,
        'samples' => $samples,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->purpose('Audit and optionally enrich product cards until each has 2-3 usable images');

Artisan::command('masterscule:fetch-real-product-images {--apply} {--limit=100} {--min=3} {--brand=} {--sku=} {--after-id=0} {--only-review} {--missing-main} {--available-only} {--replace-fallback} {--quiet-output}', function (
    ProductSearchService $search,
    ProductImageCollectorService $collector,
    ProductImageProcessorService $processor
) {
    $apply = (bool) $this->option('apply');
    $limit = max(0, (int) $this->option('limit'));
    $minImages = max(1, min(4, (int) $this->option('min')));
    $brandFilter = trim((string) $this->option('brand'));
    $skuFilter = trim((string) $this->option('sku'));
    $afterId = max(0, (int) $this->option('after-id'));
    $onlyReview = (bool) $this->option('only-review');
    $missingMain = (bool) $this->option('missing-main');
    $availableOnly = (bool) $this->option('available-only');
    $replaceFallback = (bool) $this->option('replace-fallback');
    $quiet = (bool) $this->option('quiet-output');

    $isFallback = fn (?string $path): bool => filled($path) && Str::contains((string) $path, ['fallback', 'placeholder', 'product-placeholder']);
    $isRealImage = fn (?string $path): bool => filled($path) && ! $isFallback($path);
    $galleryFor = function (Product $product) use ($isRealImage): array {
        return collect([$product->main_image])
            ->merge($product->gallery ?: [])
            ->merge($product->images->pluck('path'))
            ->filter(fn ($path) => $isRealImage($path))
            ->unique()
            ->values()
            ->all();
    };
    $hasFallback = function (Product $product) use ($isFallback): bool {
        return collect([$product->main_image])
            ->merge($product->gallery ?: [])
            ->merge($product->images->pluck('path'))
            ->contains(fn ($path) => $isFallback($path));
    };
    $syncImages = function (Product $product, array $images): void {
        ProductImage::where('product_id', $product->id)->delete();

        foreach (array_values(array_filter($images)) as $index => $path) {
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'alt' => $product->display_name,
                'sort_order' => $index + 1,
            ]);
        }
    };

    $query = Product::with(['brand', 'images'])->orderBy('id');

    if ($skuFilter !== '') {
        $query->where('sku', $skuFilter);
    }

    if ($afterId > 0) {
        $query->where('id', '>', $afterId);
    }

    if ($brandFilter !== '') {
        $query->whereHas('brand', fn ($brand) => $brand->where('name', 'like', '%'.$brandFilter.'%'));
    }

    if ($onlyReview) {
        $query->where(function ($products) {
            $products
                ->where('needs_image_review', true)
                ->orWhere('main_image', 'like', '%fallback%')
                ->orWhereHas('images', fn ($images) => $images->where('path', 'like', '%fallback%'));
        });
    }

    if ($missingMain) {
        $query->where(function ($products) {
            $products
                ->whereNull('main_image')
                ->orWhere('main_image', '')
                ->orWhere('main_image', 'like', '%placeholder%')
                ->orWhere('main_image', 'like', '%product-placeholder%');
        });
    }

    if ($availableOnly) {
        $query->availableForSale();
    }

    if ($limit > 0) {
        $query->limit($limit);
    }

    $batch = $apply
        ? ProductParserBatch::create([
            'title' => 'CLI real product image fetch '.now()->format('Y-m-d H:i:s'),
            'source_type' => 'image_search',
            'sku_count' => 0,
            'status' => 'running',
            'options_json' => [
                'min_images' => $minImages,
                'brand' => $brandFilter,
                'sku' => $skuFilter,
                'after_id' => $afterId,
                'missing_main' => $missingMain,
                'available_only' => $availableOnly,
                'replace_fallback' => $replaceFallback,
            ],
        ])
        : null;

    $stats = [
        'checked' => 0,
        'already_real_ok' => 0,
        'searched' => 0,
        'updated' => 0,
        'cleaned_fallback' => 0,
        'not_found' => 0,
        'processed_empty' => 0,
        'still_below_min' => 0,
        'errors' => 0,
        'apply' => $apply,
        'min_images' => $minImages,
        'fallback_generated' => 0,
        'last_id' => null,
    ];
    $samples = [];

    foreach ($query->get() as $product) {
        $stats['checked']++;
        $stats['last_id'] = $product->id;
        $currentReal = $galleryFor($product);
        $productHasFallback = $hasFallback($product);

        if (count($currentReal) >= $minImages && (! $replaceFallback || ! $productHasFallback)) {
            $stats['already_real_ok']++;

            if ($apply && $product->needs_image_review) {
                $product->forceFill(['needs_image_review' => false])->save();
                $syncImages($product, $currentReal);
            }

            continue;
        }

        if (! $apply) {
            if (count($samples) < 30) {
                $samples[] = [
                    'sku' => $product->sku,
                    'brand' => $product->brand?->name,
                    'real_images' => count($currentReal),
                    'has_fallback' => $productHasFallback,
                    'name' => $product->name,
                ];
            }

            continue;
        }

        try {
            $stats['searched']++;
            $item = ProductParserItem::updateOrCreate(
                ['batch_id' => $batch->id, 'sku' => $product->sku],
                [
                    'brand' => $product->brand?->name,
                    'category_id' => $product->category_id,
                    'status' => 'queued',
                    'name_ru' => $product->name,
                    'name_ro' => $product->name_ro,
                    'description_ru' => $product->description,
                    'description_ro' => $product->description_ro,
                    'found_title' => $product->display_name,
                    'found_description' => $product->description ?: $product->short_description,
                    'existing_product_id' => $product->id,
                ]
            );

            $result = $search->search($product->sku, $product->brand?->name, 'auto', false);
            $images = array_values(array_unique(array_filter($result['images'] ?? [])));

            if (count($images) < $minImages) {
                $looseQuery = trim(implode(' ', array_filter([$product->sku, $product->brand?->name, $product->name, $product->name_ro])));
                $loose = $search->searchLoose($looseQuery, $product->brand?->name);
                $images = array_values(array_unique(array_filter(array_merge($images, $loose['images'] ?? []))));
            }

            if ($images === []) {
                $stats['not_found']++;

                if ($currentReal !== []) {
                    $product->forceFill([
                        'main_image' => $currentReal[0],
                        'gallery' => $currentReal,
                        'needs_image_review' => true,
                    ])->save();
                    $syncImages($product, $currentReal);
                    $stats['cleaned_fallback']++;
                } else {
                    $product->forceFill(['needs_image_review' => true])->save();
                }

                if (count($samples) < 30) {
                    $samples[] = [
                        'sku' => $product->sku,
                        'brand' => $product->brand?->name,
                        'result' => 'not_found',
                    ];
                }

                if (! $quiet) {
                    $this->warn("Real images not found: {$product->sku}");
                }

                continue;
            }

            $collector->collect($item, $images);
            $processor->processSelected($item->fresh(['imageAssets', 'batch']));
            $processed = collect($item->fresh()->processed_images_json ?: [])
                ->filter(fn ($path) => $isRealImage($path))
                ->unique()
                ->values()
                ->all();

            if ($processed === []) {
                $stats['processed_empty']++;

                if ($currentReal !== []) {
                    $product->forceFill([
                        'main_image' => $currentReal[0],
                        'gallery' => $currentReal,
                        'needs_image_review' => true,
                    ])->save();
                    $syncImages($product, $currentReal);
                    $stats['cleaned_fallback']++;
                } else {
                    $product->forceFill(['needs_image_review' => true])->save();
                }

                if (count($samples) < 30) {
                    $samples[] = [
                        'sku' => $product->sku,
                        'brand' => $product->brand?->name,
                        'result' => 'download_or_process_failed',
                        'candidate_count' => count($images),
                    ];
                }

                if (! $quiet) {
                    $this->warn("Real images failed processing: {$product->sku}");
                }

                continue;
            }

            $nextImages = collect($processed)
                ->merge($currentReal)
                ->filter(fn ($path) => $isRealImage($path))
                ->unique()
                ->take(max($minImages, 3))
                ->values()
                ->all();

            $product->forceFill([
                'main_image' => $nextImages[0],
                'gallery' => $nextImages,
                'needs_image_review' => count($nextImages) < $minImages,
                'parser_confidence' => $result['confidence'] ?? $product->parser_confidence,
                'parser_source_urls' => array_values(array_unique(array_merge(
                    $product->parser_source_urls ?: [],
                    $result['source_urls'] ?? []
                ))),
            ])->save();
            $syncImages($product, $nextImages);
            $stats['updated']++;

            if (count($nextImages) < $minImages) {
                $stats['still_below_min']++;
            }

            if (! $quiet) {
                $this->line("Real images {$product->sku}: ".count($currentReal).' -> '.count($nextImages));
            }
        } catch (Throwable $e) {
            $stats['errors']++;
            $product->forceFill(['needs_image_review' => true])->save();

            if (count($samples) < 30) {
                $samples[] = [
                    'sku' => $product->sku,
                    'brand' => $product->brand?->name,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    if ($batch) {
        $batch->forceFill([
            'status' => $stats['errors'] > 0 ? 'completed_with_errors' : 'completed',
            'sku_count' => $stats['checked'],
            'finished_at' => now(),
            'options_json' => array_merge($batch->options_json ?: [], ['stats' => $stats]),
        ])->save();
    }

    $this->info(json_encode([
        'stats' => $stats,
        'samples' => $samples,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->purpose('Fetch real product images by SKU and replace fallback-generated product images');

Artisan::command('masterscule:parser-price-dry-run {paths*}', function () {
    $importer = app(ProductPriceListImportService::class);

    foreach ($this->argument('paths') as $path) {
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            continue;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $safeName = Str::slug(pathinfo($path, PATHINFO_FILENAME)) ?: uniqid('price_', true);
        $storedPath = 'parser/imports/cli-dry-run/'.now()->format('YmdHis').'/'.$safeName.'.'.$extension;
        Storage::disk('local')->put($storedPath, file_get_contents($path));

        $batch = ProductParserBatch::create([
            'title' => 'CLI dry-run - '.basename($path),
            'source_type' => 'price_list',
            'supplier_name' => 'CLI price import',
            'file_name' => basename($path),
            'file_path' => $storedPath,
            'file_type' => $extension,
            'price_type' => 'retail_price',
            'import_mode' => 'dry_run',
            'status' => 'pending',
            'options_json' => [
                'search_images' => false,
                'process_images' => false,
                'create_drafts_automatically' => false,
                'add_photos_to_existing' => false,
            ],
        ]);

        $importer->dryRun($batch);
        $batch->refresh();

        $this->line(sprintf(
            '%s | status=%s | rows=%d | products=%d | service=%d | new=%d | existing=%d | no_category=%d | no_stock=%d | errors=%d',
            $batch->file_name,
            $batch->status,
            $batch->total_rows,
            $batch->product_rows,
            $batch->service_rows,
            $batch->new_sku_count,
            $batch->existing_sku_count,
            $batch->rows_without_category,
            $batch->rows_without_stock,
            $batch->error_rows
        ));
    }
})->purpose('Run safe dry-run reports for supplier price lists');

Artisan::command('masterscule:parser-price-test-import {paths*} {--limit=20} {--no-images}', function () {
    $importer = app(ProductPriceListImportService::class);
    $limit = max(1, (int) $this->option('limit'));
    $searchImages = ! (bool) $this->option('no-images');

    foreach ($this->argument('paths') as $path) {
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            continue;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $safeName = Str::slug(pathinfo($path, PATHINFO_FILENAME)) ?: uniqid('price_', true);
        $storedPath = 'parser/imports/cli-test-import/'.now()->format('YmdHis').'/'.$safeName.'.'.$extension;
        Storage::disk('local')->put($storedPath, file_get_contents($path));

        $batch = ProductParserBatch::create([
            'title' => 'CLI test import - '.basename($path),
            'source_type' => 'price_list',
            'supplier_name' => 'CLI price import',
            'file_name' => basename($path),
            'file_path' => $storedPath,
            'file_type' => $extension,
            'price_type' => 'retail_price',
            'import_mode' => 'create_drafts',
            'status' => 'pending',
            'options_json' => [
                'row_limit' => $limit,
                'search_images' => $searchImages,
                'process_images' => $searchImages,
                'create_drafts_automatically' => true,
                'add_photos_to_existing' => false,
            ],
        ]);

        $importer->import($batch);
        $batch->refresh();

        $this->line(sprintf(
            '%s | status=%s | products=%d | drafts=%d | existing=%d | no_category=%d | no_images=%d | errors=%d',
            $batch->file_name,
            $batch->status,
            $batch->product_rows,
            $batch->created_drafts,
            $batch->existing_sku_count,
            $batch->rows_without_category,
            $batch->items()->where('needs_image_review', true)->count(),
            $batch->error_rows
        ));
    }
})->purpose('Create a limited draft test import from supplier price lists');

Artisan::command('masterscule:parser-price-import {paths*} {--no-images}', function () {
    $importer = app(ProductPriceListImportService::class);
    $searchImages = ! (bool) $this->option('no-images');

    foreach ($this->argument('paths') as $path) {
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            continue;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $safeName = Str::slug(pathinfo($path, PATHINFO_FILENAME)) ?: uniqid('price_', true);
        $storedPath = 'parser/imports/cli-full-import/'.now()->format('YmdHis').'/'.$safeName.'.'.$extension;
        Storage::disk('local')->put($storedPath, file_get_contents($path));

        $batch = ProductParserBatch::create([
            'title' => 'CLI full import - '.basename($path),
            'source_type' => 'price_list',
            'supplier_name' => 'CLI price import',
            'file_name' => basename($path),
            'file_path' => $storedPath,
            'file_type' => $extension,
            'price_type' => 'retail_price',
            'import_mode' => 'create_drafts',
            'status' => 'pending',
            'options_json' => [
                'search_images' => $searchImages,
                'process_images' => $searchImages,
                'create_drafts_automatically' => true,
                'add_photos_to_existing' => false,
                'replace_existing_photos' => false,
            ],
        ]);

        $importer->import($batch);
        $batch->refresh();

        $this->line(sprintf(
            'batch=%d | %s | status=%s | products=%d | drafts=%d | existing=%d | no_price=%d | no_category=%d | errors=%d',
            $batch->id,
            $batch->file_name,
            $batch->status,
            $batch->product_rows,
            $batch->created_drafts,
            $batch->existing_sku_count,
            $batch->rows_without_price,
            $batch->rows_without_category,
            $batch->error_rows
        ));
    }
})->purpose('Import complete supplier price lists without row limits');

Artisan::command('masterscule:sync-price-list-prices {--batch=*} {--apply} {--force}', function () {
    $batchIds = array_values(array_filter(array_map('intval', (array) $this->option('batch'))));
    $dryRun = ! (bool) $this->option('apply');
    if (! $dryRun && ! $this->option('force')) {
        $this->error('Price changes require both --apply and --force.');

        return 1;
    }
    $items = ProductParserItem::query()
        ->with(['createdProduct', 'existingProduct'])
        ->whereNotNull('parsed_price')
        ->whereHas('batch', function ($query) {
            $query->where('source_type', 'price_list')
                ->where('import_mode', 'create_drafts')
                ->whereIn('status', ['completed', 'completed_with_errors']);
        })
        ->when($batchIds !== [], fn ($query) => $query->whereIn('batch_id', $batchIds))
        ->orderBy('batch_id')
        ->orderBy('id')
        ->get()
        ->groupBy(fn ($item) => $item->normalized_sku ?: Str::lower(preg_replace('/[^a-z0-9]/i', '', (string) $item->sku) ?: $item->sku))
        ->map(function ($group) {
            return $group
                ->sort(function ($a, $b) {
                    $stockPriority = ((int) ($b->parsed_stock !== null)) <=> ((int) ($a->parsed_stock !== null));

                    return $stockPriority
                        ?: ($b->batch_id <=> $a->batch_id)
                        ?: ($b->id <=> $a->id);
                })
                ->first();
        })
        ->values();

    $checked = 0;
    $updated = 0;
    $missingProducts = 0;
    $missingPrices = 0;

    foreach ($items as $item) {
        $checked++;
        $price = $item->parsed_price !== null ? round((float) $item->parsed_price, 2) : null;

        if ($price === null) {
            $missingPrices++;

            continue;
        }

        $product = $item->createdProduct
            ?: $item->existingProduct
            ?: Product::where('sku', $item->sku)->first();

        if (! $product) {
            $missingProducts++;

            continue;
        }

        $oldPrice = round((float) $product->price, 2);
        $needsUpdate = abs($oldPrice - $price) >= 0.01
            || $product->currency !== 'MDL'
            || $product->old_price !== null
            || (bool) $product->is_discounted;

        if (! $needsUpdate) {
            continue;
        }

        $updated++;

        if (! $dryRun) {
            $product->forceFill([
                'price' => $price,
                'old_price' => null,
                'currency' => 'MDL',
                'is_discounted' => false,
            ])->save();
        }
    }

    $this->info(json_encode([
        'checked_items' => $checked,
        'updated_products' => $updated,
        'missing_products' => $missingProducts,
        'missing_prices' => $missingPrices,
        'batch_filter' => $batchIds,
        'dry_run' => $dryRun,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->purpose('Synchronize product prices exactly from imported retail price-list rows');

Artisan::command('masterscule:publish-parser-drafts {--limit=0} {--publish} {--force-reviewed}', function (
    ProductPublicationGuard $publicationGuard,
    ProductSearchService $search,
    ProductImageCollectorService $collector,
    ProductImageProcessorService $processor
) {
    $limit = max(0, (int) $this->option('limit'));
    $publish = (bool) $this->option('publish');
    $forceReviewed = (bool) $this->option('force-reviewed');
    $query = Product::with(['brand', 'category'])->where('status', 'draft')->orderBy('id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $stats = ['checked' => 0, 'ready' => 0, 'blocked' => 0, 'published' => 0, 'blocked_reasons' => []];

    foreach ($query->get() as $product) {
        $stats['checked']++;
        $result = $publicationGuard->evaluate($product, $forceReviewed);

        if (! $result['allowed']) {
            $stats['blocked']++;
            foreach ($result['error_codes'] as $code) {
                $stats['blocked_reasons'][$code] = ($stats['blocked_reasons'][$code] ?? 0) + 1;
            }

            continue;
        }

        $stats['ready']++;
        if ($publish) {
            $publicationGuard->publish($product, $forceReviewed);
            $stats['published']++;
        }
    }

    $this->info($publish
        ? 'Publication completed. Only guard-approved products were published.'
        : 'Dry-run only. No products were published. Use --publish to publish allowed products.');
    $this->info(json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    return 0;

    $limit = max(0, (int) $this->option('limit'));
    $skipImages = (bool) $this->option('skip-images');
    $quietOutput = (bool) $this->option('quiet-output');
    $query = Product::with(['brand', 'images'])
        ->where(function ($builder) {
            $builder
                ->where('status', 'draft')
                ->orWhere('is_active', false)
                ->orWhereNull('description')
                ->orWhere('description', '')
                ->orWhereNull('description_ro')
                ->orWhere('description_ro', '')
                ->orWhereNull('main_image')
                ->orWhere('main_image', '')
                ->orWhere('main_image', 'like', '%placeholder%');
        })
        ->orderBy('id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $products = $query->get();
    $stats = [
        'checked' => 0,
        'images_found' => 0,
        'images_processed' => 0,
        'descriptions_filled' => 0,
        'published' => 0,
        'image_review' => 0,
        'errors' => 0,
    ];

    foreach ($products as $product) {
        $stats['checked']++;
        $item = $product->source_parser_item_id
            ? ProductParserItem::with(['batch', 'imageAssets'])->find($product->source_parser_item_id)
            : null;

        if (! $item) {
            $batch = ProductParserBatch::firstOrCreate(
                ['title' => 'CLI publish enrichment'],
                [
                    'source_type' => 'batch',
                    'sku_count' => 0,
                    'status' => 'running',
                    'options_json' => ['image_limit' => 4],
                ]
            );
            $item = ProductParserItem::firstOrCreate(
                ['batch_id' => $batch->id, 'sku' => $product->sku],
                [
                    'brand' => $product->brand?->name,
                    'category_id' => $product->category_id,
                    'status' => 'queued',
                    'name_ru' => $product->name,
                    'name_ro' => $product->name_ro,
                    'description_ru' => $product->description,
                    'description_ro' => $product->description_ro,
                    'found_title' => $product->display_name,
                    'found_description' => $product->description ?: $product->short_description,
                ]
            );
        }

        $hasUsableImage = $product->main_image && ! str_contains($product->main_image, 'placeholder');

        if (! $skipImages && ! $hasUsableImage) {
            try {
                $result = $search->search($product->sku, $product->brand?->name, 'auto', false);
                if (empty($result['images'] ?? [])) {
                    $looseQuery = trim(implode(' ', array_filter([
                        $product->sku,
                        $product->name,
                        $product->name_ro,
                    ])));
                    $looseResult = $search->searchLoose($looseQuery, $product->brand?->name);
                    $result = ! empty($looseResult['images'] ?? [])
                        ? $looseResult
                        : array_merge($result, [
                            'sources' => array_merge($result['sources'] ?? [], $looseResult['sources'] ?? []),
                            'source_urls' => array_values(array_unique(array_merge($result['source_urls'] ?? [], $looseResult['source_urls'] ?? []))),
                        ]);
                }
                ProductParserSource::where('parser_item_id', $item->id)->delete();

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
                if ($images) {
                    $stats['images_found']++;
                    $collector->collect($item, $images);
                    $processor->processSelected($item->fresh(['imageAssets', 'batch']));
                    $processed = $item->fresh()->processed_images_json ?: [];

                    if ($processed) {
                        $product->forceFill([
                            'main_image' => $processed[0],
                            'gallery' => $processed,
                            'parser_confidence' => $result['confidence'] ?? $product->parser_confidence,
                            'parser_source_urls' => $result['source_urls'] ?? $product->parser_source_urls,
                            'needs_image_review' => count($processed) < 3,
                        ])->save();

                        ProductImage::where('product_id', $product->id)->delete();
                        foreach ($processed as $index => $path) {
                            ProductImage::create([
                                'product_id' => $product->id,
                                'path' => $path,
                                'alt' => $product->display_name,
                                'sort_order' => $index + 1,
                            ]);
                        }

                        $stats['images_processed']++;
                    }
                }

                if (! $images) {
                    $product->forceFill(['needs_image_review' => true])->save();
                }
            } catch (Throwable $e) {
                $stats['errors']++;
                $product->forceFill(['needs_image_review' => true])->save();
                $item->forceFill([
                    'status' => 'failed',
                    'error_message' => trim(($item->error_message ? $item->error_message.' ' : '').'Publish enrichment image search failed: '.$e->getMessage()),
                ])->save();
            }
        }

        $description = $product->description ?: $item->description_ru ?: $item->found_description ?: $product->short_description;
        $descriptionRo = $product->description_ro ?: $item->description_ro ?: $item->found_description ?: $description;

        if (! $product->description || ! $product->description_ro) {
            $stats['descriptions_filled']++;
        }

        $product->forceFill([
            'description' => $description,
            'description_ro' => $descriptionRo,
            'status' => 'draft',
            'approval_status' => 'pending_review',
            'is_active' => false,
            'needs_review' => true,
            'needs_image_review' => ! ($product->fresh()->main_image && ! str_contains((string) $product->fresh()->main_image, 'placeholder')),
        ])->save();

        $item->forceFill([
            'status' => 'approved',
            'approval_status' => 'approved',
            'created_product_id' => $product->id,
        ])->save();

        if ($product->fresh()->needs_image_review) {
            $stats['image_review']++;
        }

        $stats['published']++;
        if (! $quietOutput) {
            $this->line("Published {$product->sku} ({$stats['checked']}/{$products->count()})");
        }
    }

    $this->info(json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
})->purpose('Dry-run parser draft publication and publish guard-approved products with --publish');

if (! function_exists('ensureTrisToolBrand')) {
    function ensureTrisToolBrand(string $name, string $slug): Brand
    {
        $logo = $slug === 'king-tony' ? '/images/brand/king-tony.png' : '/images/brand/m7.png';

        return Brand::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => 'Brand profesional de scule si echipamente pentru service auto.',
                'logo' => $logo,
                'is_featured' => true,
                'is_active' => true,
            ]
        );
    }

    function parseTrisToolCards(string $html): array
    {
        preg_match_all(
            '/<a class="cl-item[\s\S]*?href="(?<href>[^"]+)"[\s\S]*?<img[^>]+src="(?<img>[^"]+)"[\s\S]*?<h6[^>]*>(?<title>[\s\S]*?)<\/h6>[\s\S]*?<span class="article"[^>]*>(?<sku>[\s\S]*?)<\/span>[\s\S]*?<span class="item-price"[^>]*>[\s\S]*?(?<price>[0-9 ]+,[0-9]{2}) MDL/i',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        return array_map(fn ($match) => [
            'href' => $match['href'],
            'image' => $match['img'],
            'title' => html_entity_decode(strip_tags($match['title']), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'sku' => html_entity_decode(strip_tags($match['sku']), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'price' => $match['price'],
        ], $matches);
    }

    function cleanTrisToolTitle(string $title): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    function normalizeProductName(string $title, string $brandName): string
    {
        $brand = str_contains($brandName, 'M7') ? 'M7' : 'King Tony';
        $name = trim($title);

        if (! Str::contains(Str::lower($name), Str::lower($brand))) {
            $name .= ' '.$brand;
        }

        return Str::limit($name, 130, '');
    }

    function uniqueProductSlug(string $title, string $sku): string
    {
        $base = Str::slug(Str::limit($title, 70, '').'-'.$sku);

        if ($base === '') {
            $base = Str::slug('produs-'.$sku);
        }

        $slug = $base;
        $index = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$index++;
        }

        return $slug;
    }

    function parseMdlPrice(string $price): float
    {
        $mdl = (float) str_replace([' ', ','], ['', '.'], $price);

        return round($mdl, 2);
    }

    function categoryForTrisToolTitle(string $title, string $brandSlug): Category
    {
        $lower = mb_strtolower($title, 'UTF-8');
        $slug = match (true) {
            str_contains($lower, 'компресс') => 'compresoare',
            str_contains($lower, 'динамометр') => 'chei-dinamometrice',
            str_contains($lower, 'домкрат') || str_contains($lower, 'подъем') || str_contains($lower, 'подъём') || str_contains($lower, 'стойка') => 'cricuri-si-ridicare',
            str_contains($lower, 'тележ') || str_contains($lower, 'шкаф') || str_contains($lower, 'держател') || str_contains($lower, 'органайзер') => 'dulapuri-si-organizare',
            str_contains($lower, 'пневмат') || str_contains($lower, 'гайков') || str_contains($lower, 'шлиф') || str_contains($lower, 'дрель') || str_contains($lower, 'пила') || $brandSlug === 'm7-mighty-seven' => 'scule-pneumatice',
            str_contains($lower, 'голов') || str_contains($lower, 'насад') || str_contains($lower, 'бит') || str_contains($lower, 'трещ') || str_contains($lower, 'вороток') || str_contains($lower, 'удлин') => 'tubulare-si-clichete',
            str_contains($lower, 'набор') || str_contains($lower, 'комплект') || str_contains($lower, 'кейс') => 'seturi-de-scule',
            default => 'chei-si-surubelnite',
        };

        return Category::where('slug', $slug)->first() ?? Category::firstOrFail();
    }

    function downloadTrisToolImage(string $source, string $sku, string $brandSlug): string
    {
        $extension = strtolower(pathinfo(parse_url($source, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg');
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
        $filename = Str::slug($brandSlug.'-'.$sku).'.'.$extension;
        $relative = '/images/products/tristool/'.$brandSlug.'/'.$filename;
        $path = public_path(ltrim($relative, '/'));

        File::ensureDirectoryExists(dirname($path));

        if (! File::exists($path)) {
            $response = tristoolHttp()->timeout(30)->retry(2, 500)->get(tristoolAssetUrl($source));

            if ($response->successful() && $response->body() !== '') {
                File::put($path, $response->body());
            }
        }

        return File::exists($path) ? $relative : '/images/products/product-placeholder-toolbox.svg';
    }

    function findTrisToolCardBySku(string $sku): ?array
    {
        $searchUrl = 'https://tristool.md/ru/search?searchword='.rawurlencode($sku);
        $response = tristoolHttp()->withHeaders([
            'User-Agent' => 'MasterScule.md price repair/1.0',
            'Accept' => 'text/html,application/xhtml+xml',
        ])->timeout(20)->retry(1, 350)->get($searchUrl);

        if (! $response->successful()) {
            return null;
        }

        $needle = normalizeTrisToolSku($sku);

        return collect(parseTrisToolCards($response->body()))
            ->map(fn ($card) => $card + ['sku_score' => tristoolSkuScore($needle, normalizeTrisToolSku($card['sku']))])
            ->filter(fn ($card) => $card['sku_score'] > 0)
            ->sortByDesc('sku_score')
            ->first();
    }

    function normalizeTrisToolSku(string $sku): string
    {
        return Str::lower(preg_replace('/[^a-z0-9]/i', '', $sku));
    }

    function tristoolSkuScore(string $needle, string $found): int
    {
        if ($needle === '' || $found === '') {
            return 0;
        }

        if ($needle === $found) {
            return 100;
        }

        if ('sc'.$needle === $found) {
            return 94;
        }

        if (Str::endsWith($found, $needle)) {
            return 88;
        }

        return Str::contains($found, $needle) ? 82 : 0;
    }

    function tristoolHttp(): PendingRequest
    {
        return Http::withOptions([
            'proxy' => '',
            'verify' => false,
        ]);
    }

    function tristoolAssetUrl(string $source): string
    {
        $url = Str::startsWith($source, ['http://', 'https://']) ? $source : 'https://tristool.md/'.ltrim($source, '/');
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $path = implode('/', array_map('rawurlencode', explode('/', $parts['path'] ?? '')));

        return $parts['scheme'].'://'.$parts['host'].$path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    function shortProductDescription(string $title, string $brandName): string
    {
        $brand = str_contains($brandName, 'M7') ? 'M7 / Mighty Seven' : 'King Tony';

        return "{$brand}: produs profesional pentru service auto, atelier si garaj. Model: {$title}.";
    }

    function fullProductDescription(string $title, string $brandName, string $sku): string
    {
        $brand = str_contains($brandName, 'M7') ? 'M7 / Mighty Seven' : 'King Tony';

        return "Produs {$brand}, cod {$sku}, adaugat in catalogul MasterScule.md pentru service-uri auto, ateliere si clienti care cauta scule fiabile. Cardul include denumire, cod produs, pret in MDL, imagine, stoc disponibil, garantie si caracteristici tehnice de baza. Potrivit pentru utilizare profesionala si pentru garaje bine echipate.";
    }

    function attributesForTrisToolTitle(string $title, string $sku, string $brandName): array
    {
        $attributes = [
            'Brand' => str_contains($brandName, 'M7') ? 'M7 / Mighty Seven' : 'King Tony',
            'Cod produs' => $sku,
            'Utilizare' => 'Service auto / atelier / garaj',
            'Garantie' => '24 luni',
        ];

        if (preg_match('/([0-9]+)\s*(?:Nm|Нм)/iu', $title, $match)) {
            $attributes['Cuplu maxim'] = $match[1].' Nm';
        }

        if (preg_match('/([0-9]+)\s*(?:предмет|piese|шт|pcs)/iu', $title, $match)) {
            $attributes['Numar piese'] = $match[1];
        }

        if (preg_match('/(1\/4|3\/8|1\/2|3\/4|1")/u', $title, $match)) {
            $attributes['Antrenare'] = $match[1];
        }

        if (preg_match('/([0-9]+)\s*(?:мм|mm)/iu', $title, $match)) {
            $attributes['Dimensiune'] = $match[1].' mm';
        }

        if (preg_match('/([0-9]+)\s*(?:V|В)/u', $title, $match)) {
            $attributes['Tensiune'] = $match[1].' V';
        }

        return $attributes;
    }

    function packageForTrisToolTitle(string $title): array
    {
        $lower = mb_strtolower($title, 'UTF-8');

        if (str_contains($lower, 'набор') || str_contains($lower, 'комплект')) {
            return ['Set scule', 'Cutie / organizator', 'Documentatie tehnica'];
        }

        if (str_contains($lower, 'аккумулятор') || str_contains($lower, '18в') || str_contains($lower, '18 v')) {
            return ['Scula principala', 'Ambalaj', 'Documentatie tehnica'];
        }

        return ['Produs principal', 'Ambalaj', 'Documentatie tehnica'];
    }
}
