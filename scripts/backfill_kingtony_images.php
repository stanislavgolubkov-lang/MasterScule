<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductParserBatch;
use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use App\Services\ProductImageProcessorService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$limit = (int) ($argv[1] ?? 350);
$afterId = (int) ($argv[2] ?? 0);
$processor = app(ProductImageProcessorService::class);
$batch = ProductParserBatch::create([
    'title' => 'CLI King Tony fast official PNG backfill '.now()->format('Y-m-d H:i:s'),
    'source_type' => 'image_search',
    'sku_count' => 0,
    'status' => 'running',
    'options_json' => [
        'source' => 'kingtony.com/upload/products/{sku}.png',
        'limit' => $limit,
        'after_id' => $afterId,
        'order' => $afterId > 0 ? 'id_asc_after_id' : 'stock_desc',
    ],
]);

$products = Product::availableForSale()
    ->whereHas('brand', fn ($q) => $q->where('name', 'like', '%King Tony%'))
    ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
    ->where(function ($q) {
        $q->whereNull('main_image')
            ->orWhere('main_image', '')
            ->orWhere('main_image', 'like', '%placeholder%')
            ->orWhere('main_image', 'like', '%product-placeholder%');
    })
    ->with('brand:id,name')
    ->when($afterId > 0, fn ($q) => $q->orderBy('id'), fn ($q) => $q->orderByDesc('stock_quantity'))
    ->limit($limit)
    ->get();

$stats = [
    'checked' => 0,
    'found' => 0,
    'processed' => 0,
    'not_found' => 0,
    'failed' => 0,
    'last_id' => null,
];
$samples = [];

foreach ($products as $product) {
    $stats['checked']++;
    $stats['last_id'] = $product->id;
    $url = 'https://www.kingtony.com/upload/products/'.rawurlencode($product->sku).'.png';

    try {
        $response = Http::withOptions(['proxy' => '', 'verify' => false])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 MasterScule Image Backfill/1.0'])
            ->timeout(6)
            ->head($url);
        $exists = $response->successful()
            && Str::contains(Str::lower((string) $response->header('content-type')), ['image/png', 'image/jpeg', 'image/webp']);
    } catch (Throwable) {
        $exists = false;
    }

    if (! $exists) {
        $stats['not_found']++;
        if (count($samples) < 20) {
            $samples[] = ['sku' => $product->sku, 'result' => 'not_found'];
        }
        continue;
    }

    $stats['found']++;

    try {
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => $product->sku,
            'normalized_sku' => Str::lower(preg_replace('/[^a-z0-9]/i', '', $product->sku) ?: $product->sku),
            'brand' => $product->brand?->name,
            'category_id' => $product->category_id,
            'status' => 'queued',
            'name_ru' => $product->name,
            'name_ro' => $product->name_ro,
            'found_title' => $product->display_name,
            'existing_product_id' => $product->id,
            'found_images_json' => [$url],
            'selected_images_json' => [$url],
            'source_urls_json' => [$url],
            'tristools_match_confidence' => 94,
        ]);

        ProductParserImageAsset::create([
            'parser_item_id' => $item->id,
            'source_url' => $url,
            'source_domain' => 'www.kingtony.com',
            'status' => 'found',
            'is_selected' => true,
            'is_main' => true,
            'needs_review' => false,
        ]);

        $processor->processSelected($item->fresh(['imageAssets', 'batch']));
        $processed = $item->fresh()->processed_images_json ?: [];

        if ($processed === []) {
            $stats['failed']++;
            $product->forceFill(['needs_image_review' => true])->save();
            continue;
        }

        $product->forceFill([
            'main_image' => $processed[0],
            'gallery' => $processed,
            'needs_image_review' => count($processed) < 2,
            'parser_source_urls' => array_values(array_unique(array_merge($product->parser_source_urls ?: [], [$url]))),
            'parser_confidence' => max((int) ($product->parser_confidence ?? 0), 94),
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

        $stats['processed']++;
    } catch (Throwable $e) {
        $stats['failed']++;
        $product->forceFill(['needs_image_review' => true])->save();
        if (count($samples) < 20) {
            $samples[] = ['sku' => $product->sku, 'error' => $e->getMessage()];
        }
    }
}

$batch->forceFill([
    'status' => $stats['failed'] > 0 ? 'completed_with_errors' : 'completed',
    'sku_count' => $stats['checked'],
    'finished_at' => now(),
    'options_json' => array_merge($batch->options_json ?: [], ['stats' => $stats]),
])->save();

echo json_encode(['stats' => $stats, 'samples' => $samples], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
