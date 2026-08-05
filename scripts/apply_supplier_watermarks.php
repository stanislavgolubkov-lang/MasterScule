<?php

declare(strict_types=1);

use App\Services\ProductWatermarkService;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$batchIds = [26, 27, 28];
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--batches=')) {
        $batchIds = array_values(array_filter(array_map(
            static fn (string $value): int => (int) trim($value),
            explode(',', substr($argument, 10)),
        )));
    }
}

$allowedPrefixes = [
    '/images/products/spin/',
    '/images/products/telwin/',
    '/images/products/uhl-mash/',
];

$assets = DB::table('product_parser_image_assets as a')
    ->join('product_parser_items as i', 'i.id', '=', 'a.parser_item_id')
    ->whereIn('i.batch_id', $batchIds)
    ->where('a.has_watermark', false)
    ->whereNotNull('a.processed_path')
    ->orderBy('a.id')
    ->get(['a.id', 'a.processed_path', 'i.batch_id', 'i.sku']);

$watermark = app(ProductWatermarkService::class);
$processedPaths = [];
$errors = [];

foreach ($assets->groupBy('processed_path') as $path => $pathAssets) {
    $path = (string) $path;
    if (! collect($allowedPrefixes)->contains(static fn (string $prefix): bool => str_starts_with($path, $prefix))) {
        $errors[] = "Unsupported product image path: {$path}";
        continue;
    }

    $absolutePath = public_path(ltrim($path, '/'));
    $realPath = realpath($absolutePath);
    $publicRoot = realpath(public_path('images/products'));
    if (! $realPath || ! $publicRoot || ! is_file($realPath)) {
        $errors[] = "Image file not found: {$path}";
        continue;
    }
    $normalizedRoot = rtrim(str_replace('\\', '/', $publicRoot), '/').'/';
    $normalizedPath = str_replace('\\', '/', $realPath);
    if (! str_starts_with($normalizedPath, $normalizedRoot)) {
        $errors[] = "Image escaped product directory: {$path}";
        continue;
    }

    $bytes = file_get_contents($realPath);
    $image = $bytes !== false ? @imagecreatefromstring($bytes) : false;
    if (! $image instanceof \GdImage) {
        $errors[] = "Invalid image: {$path}";
        continue;
    }

    $applied = $watermark->apply($image);
    ob_start();
    imagewebp($image, null, 91);
    $encoded = (string) ob_get_clean();
    imagedestroy($image);

    if (! $applied || $encoded === '' || file_put_contents($realPath, $encoded, LOCK_EX) === false) {
        $errors[] = "Watermark write failed: {$path}";
        continue;
    }

    DB::transaction(function () use ($path, $batchIds, $realPath): void {
        DB::table('product_parser_image_assets')
            ->where('processed_path', $path)
            ->whereIn('parser_item_id', DB::table('product_parser_items')->whereIn('batch_id', $batchIds)->select('id'))
            ->update([
                'has_watermark' => true,
                'updated_at' => now(),
            ]);

        DB::table('product_images')
            ->where('path', $path)
            ->update([
                'file_size' => filesize($realPath) ?: null,
                'updated_at' => now(),
            ]);
    });

    $processedPaths[] = $path;
}

echo json_encode([
    'batches' => $batchIds,
    'candidate_assets' => $assets->count(),
    'watermarked_files' => count($processedPaths),
    'errors' => $errors,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL;

exit($errors === [] ? 0 : 1);
