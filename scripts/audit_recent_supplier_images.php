<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$batchIds = array_values(array_filter(array_map(
    static fn (string $value): int => (int) trim($value),
    explode(',', $argv[1] ?? '26,27,28'),
)));
$showItems = in_array('--items', $argv, true);
$showMissingManifest = in_array('--missing-manifest', $argv, true);

$batches = DB::table('product_parser_batches')
    ->whereIn('id', $batchIds)
    ->orderBy('id')
    ->get(['id', 'title', 'supplier_name', 'file_name', 'brand_default', 'status', 'sku_count', 'product_rows']);

$summary = [];
foreach ($batches as $batch) {
    $row = DB::table('product_parser_items as i')
        ->leftJoin('products as p', function ($join): void {
            $join->on('p.source_parser_item_id', '=', 'i.id')
                ->orOn('p.id', '=', 'i.created_product_id')
                ->orOn('p.id', '=', 'i.existing_product_id');
        })
        ->leftJoin('product_parser_image_assets as a', function ($join): void {
            $join->on('a.parser_item_id', '=', 'i.id')->where('a.is_main', '=', 1);
        })
        ->where('i.batch_id', $batch->id)
        ->where('i.status', '!=', 'skipped')
        ->selectRaw('COUNT(DISTINCT i.id) AS items')
        ->selectRaw('COUNT(DISTINCT p.id) AS products')
        ->selectRaw("SUM(CASE WHEN COALESCE(p.main_image, '') LIKE '/images/brand/%' OR COALESCE(p.main_image, '') = '' THEN 1 ELSE 0 END) AS placeholders")
        ->selectRaw('SUM(CASE WHEN COALESCE(a.has_watermark, 0) = 1 THEN 1 ELSE 0 END) AS watermarked')
        ->selectRaw("SUM(CASE WHEN COALESCE(i.tristools_url, '') != '' THEN 1 ELSE 0 END) AS tristools_urls")
        ->first();

    $paths = DB::table('products')
        ->where('source_import_batch_id', $batch->id)
        ->pluck('main_image');
    $row->broken_files = $paths->filter(static function (?string $path): bool {
        if (! is_string($path) || ! str_starts_with($path, '/images/products/')) {
            return true;
        }
        $absolutePath = public_path(ltrim($path, '/'));
        $dimensions = is_file($absolutePath) ? @getimagesize($absolutePath) : false;

        return $dimensions === false || $dimensions[0] < 220 || $dimensions[1] < 220;
    })->count();

    $summary[] = [
        'batch' => $batch,
        'images' => $row,
        'items' => $showItems
            ? DB::table('product_parser_items')
                ->where('batch_id', $batch->id)
                ->where('status', '!=', 'skipped')
                ->orderBy('row_number')
                ->get([
                    'id', 'sku', 'brand', 'raw_name', 'tristools_url',
                    'official_source_url', 'image_source_type', 'created_product_id',
                    'existing_product_id',
                ])
            : null,
    ];
}

echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL;

if ($showMissingManifest) {
    $manifestPath = storage_path('app/parser/supplier-image-manifest.json');
    $manifest = is_file($manifestPath)
        ? json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR)
        : [];
    $missingSkus = collect($manifest)
        ->filter(static fn (array $record): bool => ! ($record['found'] ?? false))
        ->pluck('sku')
        ->filter()
        ->values();
    $missing = DB::table('product_parser_items')
        ->whereIn('batch_id', $batchIds)
        ->whereIn('sku', $missingSkus)
        ->orderBy('row_number')
        ->get(['sku', 'raw_name', 'tristools_url']);
    echo json_encode($missing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL;
}
