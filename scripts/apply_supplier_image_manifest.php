<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$manifestPath = storage_path('app/parser/supplier-image-manifest.json');
if (! is_file($manifestPath)) {
    fwrite(STDERR, "Manifest not found: {$manifestPath}\n");
    exit(2);
}

$records = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
$requestedSkus = [];
$excludedSkus = [];
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--sku=')) {
        $requestedSkus = array_values(array_filter(array_map('trim', explode(',', substr($argument, 6)))));
    }
    if (str_starts_with($argument, '--exclude-sku=')) {
        $excludedSkus = array_values(array_filter(array_map('trim', explode(',', substr($argument, 14)))));
    }
}
if ($requestedSkus !== []) {
    $records = array_values(array_filter(
        $records,
        static fn (array $record): bool => in_array((string) ($record['sku'] ?? ''), $requestedSkus, true),
    ));
}
if ($excludedSkus !== []) {
    $records = array_values(array_filter(
        $records,
        static fn (array $record): bool => ! in_array((string) ($record['sku'] ?? ''), $excludedSkus, true),
    ));
}
$applied = 0;
$skipped = 0;
$errors = [];

foreach ($records as $record) {
    if (! ($record['found'] ?? false) || empty($record['path'])) {
        $skipped++;
        continue;
    }

    $absolutePath = public_path(ltrim((string) $record['path'], '/'));
    $dimensions = is_file($absolutePath) ? @getimagesize($absolutePath) : false;
    if ($dimensions === false || $dimensions[0] < 260 || $dimensions[1] < 220) {
        $errors[] = ($record['sku'] ?? '?').': invalid local image';
        continue;
    }

    $item = DB::table('product_parser_items')->where('id', $record['item_id'])->first();
    if (! $item) {
        $errors[] = ($record['sku'] ?? '?').': parser item not found';
        continue;
    }

    $product = DB::table('products')
        ->where('source_parser_item_id', $item->id)
        ->orWhere('id', $item->created_product_id ?: 0)
        ->orWhere('id', $item->existing_product_id ?: 0)
        ->orWhere('sku', $item->sku)
        ->orderByRaw('CASE WHEN source_parser_item_id = ? THEN 0 ELSE 1 END', [$item->id])
        ->first();
    if (! $product) {
        $errors[] = ($record['sku'] ?? '?').': product not found';
        continue;
    }

    DB::transaction(function () use ($record, $item, $product, $absolutePath, $dimensions): void {
        $now = now();
        $path = (string) $record['path'];
        $sourceUrl = (string) ($record['source_url'] ?? $record['source_page_url'] ?? '');
        $sourcePageUrl = (string) ($record['source_page_url'] ?? $sourceUrl);
        $sourceDomain = (string) ($record['source_domain'] ?? parse_url($sourcePageUrl, PHP_URL_HOST) ?? '');
        $sourceType = (string) ($record['source_type'] ?? 'verified_exact_source');
        $isOfficial = (bool) ($record['is_official'] ?? false);
        $confidence = $isOfficial ? 100 : 92;

        $productSources = json_decode((string) ($product->parser_source_urls ?? '[]'), true);
        $productSources = is_array($productSources) ? $productSources : [];
        $productSources = array_values(array_unique(array_filter(array_merge($productSources, [$sourcePageUrl, $sourceUrl]))));

        DB::table('products')->where('id', $product->id)->update([
            'main_image' => $path,
            'gallery' => json_encode([$path], JSON_UNESCAPED_SLASHES),
            'needs_image_review' => false,
            'parser_source_urls' => json_encode($productSources, JSON_UNESCAPED_SLASHES),
            'updated_at' => $now,
        ]);

        DB::table('product_parser_image_assets')->where('parser_item_id', $item->id)->delete();
        DB::table('product_parser_image_assets')->insert([
            'parser_item_id' => $item->id,
            'source_url' => $sourceUrl,
            'source_domain' => $sourceDomain,
            'original_path' => $path,
            'processed_path' => $path,
            'preview_path' => $path,
            'thumb_path' => $path,
            'width' => $dimensions[0],
            'height' => $dimensions[1],
            'mime_type' => 'image/webp',
            'status' => 'processed',
            'is_selected' => true,
            'is_main' => true,
            'has_watermark' => false,
            'background_removed' => false,
            'background_removal_failed' => false,
            'needs_review' => false,
            'error_message' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $itemSources = json_decode((string) ($item->source_urls_json ?? '[]'), true);
        $itemSources = is_array($itemSources) ? $itemSources : [];
        $itemSources = array_values(array_unique(array_filter(array_merge($itemSources, [$sourcePageUrl, $sourceUrl]))));
        DB::table('product_parser_items')->where('id', $item->id)->update([
            'found_images_json' => json_encode([$sourceUrl], JSON_UNESCAPED_SLASHES),
            'selected_images_json' => json_encode([$sourceUrl], JSON_UNESCAPED_SLASHES),
            'processed_images_json' => json_encode([$path], JSON_UNESCAPED_SLASHES),
            'source_urls_json' => json_encode($itemSources, JSON_UNESCAPED_SLASHES),
            'image_source_type' => $sourceType,
            'official_source_url' => $isOfficial ? $sourcePageUrl : ($item->official_source_url ?? null),
            'official_source_domain' => $isOfficial ? $sourceDomain : ($item->official_source_domain ?? null),
            'official_source_confidence' => $isOfficial ? $confidence : ($item->official_source_confidence ?? null),
            'source_match_confidence' => $confidence,
            'needs_image_review' => false,
            'image_reviewed_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('product_images')->where('product_id', $product->id)->delete();
        DB::table('product_images')->insert([
            'product_id' => $product->id,
            'path' => $path,
            'alt' => $product->name_ru ?: $product->name_ro ?: $item->raw_name,
            'sort_order' => 1,
            'source_url' => $sourceUrl,
            'source_page_url' => $sourcePageUrl,
            'source_domain' => $sourceDomain,
            'is_official' => $isOfficial,
            'mime_type' => 'image/webp',
            'width' => $dimensions[0],
            'height' => $dimensions[1],
            'file_size' => filesize($absolutePath) ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    });

    $applied++;
}

echo json_encode([
    'records' => count($records),
    'applied' => $applied,
    'skipped_missing' => $skipped,
    'errors' => $errors,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL;

exit($errors === [] ? 0 : 1);
