<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$delete = in_array('--delete', $argv, true);
$references = [];
$productReferences = [];

$addReference = function (mixed $value) use (&$addReference, &$references): void {
    if (is_array($value)) {
        foreach ($value as $item) {
            $addReference($item);
        }

        return;
    }

    if (! is_string($value) || trim($value) === '') {
        return;
    }

    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $addReference($decoded);

        return;
    }

    $path = parse_url(trim($value), PHP_URL_PATH) ?: trim($value);
    if (str_starts_with($path, '/storage/')) {
        $path = substr($path, strlen('/storage/'));
    } elseif (str_starts_with($path, 'storage/')) {
        $path = substr($path, strlen('storage/'));
    }

    $path = ltrim(str_replace('\\', '/', $path), '/');
    if (str_starts_with($path, 'products/')) {
        $references[$path] = true;
    }
};

$addProductReference = function (mixed $value) use (&$addProductReference, &$productReferences): void {
    if (is_array($value)) {
        foreach ($value as $item) {
            $addProductReference($item);
        }

        return;
    }

    if (! is_string($value) || trim($value) === '') {
        return;
    }

    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $addProductReference($decoded);

        return;
    }

    $path = parse_url(trim($value), PHP_URL_PATH) ?: trim($value);
    if (str_starts_with($path, '/storage/')) {
        $path = substr($path, strlen('/storage/'));
    } elseif (str_starts_with($path, 'storage/')) {
        $path = substr($path, strlen('storage/'));
    }

    $path = ltrim(str_replace('\\', '/', $path), '/');
    if (str_starts_with($path, 'products/')) {
        $productReferences[$path] = true;
    }
};

DB::table('products')
    ->select(['main_image', 'gallery'])
    ->orderBy('id')
    ->each(function (object $product) use ($addReference, $addProductReference): void {
        $addReference($product->main_image);
        $addReference($product->gallery);
        $addProductReference($product->main_image);
        $addProductReference($product->gallery);
    });

DB::table('product_images')
    ->orderBy('id')
    ->pluck('path')
    ->each(function (string $path) use ($addReference, $addProductReference): void {
        $addReference($path);
        $addProductReference($path);
    });

DB::table('product_parser_image_assets')
    ->select(['original_path', 'processed_path', 'preview_path', 'thumb_path'])
    ->orderBy('id')
    ->each(function (object $asset) use ($addReference): void {
        foreach ((array) $asset as $path) {
            $addReference($path);
        }
    });

DB::table('product_parser_items')
    ->select(['found_images_json', 'selected_images_json', 'processed_images_json'])
    ->orderBy('id')
    ->each(function (object $item) use ($addReference): void {
        foreach ((array) $item as $paths) {
            $addReference($paths);
        }
    });

$disk = Storage::disk('public');
$files = collect($disk->allFiles('products'));
$orphans = $files
    ->reject(fn (string $path): bool => isset($references[$path]))
    ->values();
$missing = collect(array_keys($references))
    ->reject(fn (string $path): bool => $disk->exists($path))
    ->values();
$parserOnly = $files
    ->filter(fn (string $path): bool => isset($references[$path]) && ! isset($productReferences[$path]))
    ->values();

$grouped = $orphans
    ->groupBy(function (string $path): string {
        $parts = explode('/', $path);

        return ($parts[1] ?? 'unknown').'/'.($parts[2] ?? 'unknown');
    })
    ->map(function ($paths) use ($disk): array {
        return [
            'files' => $paths->count(),
            'size_mb' => round($paths->sum(fn (string $path): int => $disk->size($path)) / 1048576, 2),
        ];
    })
    ->sortKeys();

$parserOnlyGrouped = $parserOnly
    ->groupBy(function (string $path): string {
        $parts = explode('/', $path);

        return ($parts[1] ?? 'unknown').'/'.($parts[2] ?? 'unknown');
    })
    ->map(function ($paths) use ($disk): array {
        return [
            'files' => $paths->count(),
            'size_mb' => round($paths->sum(fn (string $path): int => $disk->size($path)) / 1048576, 2),
        ];
    })
    ->sortKeys();

$protectedReferences = $productReferences;
$addProtectedReference = function (mixed $value) use (&$addProtectedReference, &$protectedReferences): void {
    if (is_array($value)) {
        foreach ($value as $item) {
            $addProtectedReference($item);
        }

        return;
    }

    if (! is_string($value) || trim($value) === '') {
        return;
    }

    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $addProtectedReference($decoded);

        return;
    }

    $path = parse_url(trim($value), PHP_URL_PATH) ?: trim($value);
    if (str_starts_with($path, '/storage/')) {
        $path = substr($path, strlen('/storage/'));
    } elseif (str_starts_with($path, 'storage/')) {
        $path = substr($path, strlen('storage/'));
    }

    $path = ltrim(str_replace('\\', '/', $path), '/');
    if (str_starts_with($path, 'products/')) {
        $protectedReferences[$path] = true;
    }
};

$terminalStatuses = ['approved', 'skipped', 'rejected', 'failed', 'not_found'];

DB::table('product_parser_image_assets as assets')
    ->join('product_parser_items as items', 'items.id', '=', 'assets.parser_item_id')
    ->select([
        'assets.is_selected',
        'assets.original_path',
        'assets.processed_path',
        'assets.preview_path',
        'assets.thumb_path',
        'items.status as item_status',
    ])
    ->orderBy('assets.id')
    ->each(function (object $asset) use ($addProtectedReference, $terminalStatuses): void {
        if ((bool) $asset->is_selected || ! in_array($asset->item_status, $terminalStatuses, true)) {
            foreach (['original_path', 'processed_path', 'preview_path', 'thumb_path'] as $column) {
                $addProtectedReference($asset->{$column});
            }
        }
    });

DB::table('product_parser_items')
    ->select(['status', 'found_images_json', 'selected_images_json', 'processed_images_json'])
    ->whereNotIn('status', $terminalStatuses)
    ->orderBy('id')
    ->each(function (object $item) use ($addProtectedReference): void {
        foreach (['found_images_json', 'selected_images_json', 'processed_images_json'] as $column) {
            $addProtectedReference($item->{$column});
        }
    });

$safeRemovable = $files
    ->reject(fn (string $path): bool => isset($protectedReferences[$path]))
    ->values();
$recentCutoff = time() - 86400;
$recentOrphans = $orphans
    ->filter(fn (string $path): bool => $disk->lastModified($path) > $recentCutoff)
    ->values();
$deletionCandidates = $orphans
    ->reject(fn (string $path): bool => $disk->lastModified($path) > $recentCutoff)
    ->values();

$safeRemovableGrouped = $safeRemovable
    ->groupBy(function (string $path): string {
        $parts = explode('/', $path);

        return ($parts[1] ?? 'unknown').'/'.($parts[2] ?? 'unknown');
    })
    ->map(function ($paths) use ($disk): array {
        return [
            'files' => $paths->count(),
            'size_mb' => round($paths->sum(fn (string $path): int => $disk->size($path)) / 1048576, 2),
        ];
    })
    ->sortKeys();

$parserOnlyBytes = $parserOnly->sum(fn (string $path): int => $disk->size($path));
$safeRemovableBytes = $safeRemovable->sum(fn (string $path): int => $disk->size($path));
$orphanBytes = $orphans->sum(fn (string $path): int => $disk->size($path));
$deletionCandidateBytes = $deletionCandidates->sum(fn (string $path): int => $disk->size($path));

$deleted = 0;
$deletedBytes = 0;

if ($delete) {
    $root = realpath(storage_path('app/public/products'));
    if ($root === false) {
        throw new RuntimeException('Product image root does not exist.');
    }

    $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/').'/';

    foreach ($deletionCandidates as $path) {
        $absolutePath = realpath(storage_path('app/public/'.$path));
        if ($absolutePath === false || ! is_file($absolutePath)) {
            continue;
        }

        $normalizedPath = str_replace('\\', '/', $absolutePath);
        if (! str_starts_with($normalizedPath, $normalizedRoot)) {
            throw new RuntimeException('Refusing to delete a file outside the product image root: '.$path);
        }

        $size = filesize($absolutePath) ?: 0;
        if ($disk->delete($path)) {
            $deleted++;
            $deletedBytes += $size;
        }
    }
}

$report = [
    'mode' => $delete ? 'delete-orphans' : 'dry-run',
    'files_total' => $files->count(),
    'referenced_paths' => count($references),
    'product_referenced_paths' => count($productReferences),
    'parser_only_files' => $parserOnly->count(),
    'parser_only_size_mb' => round($parserOnlyBytes / 1048576, 2),
    'parser_only_by_source_brand' => $parserOnlyGrouped,
    'safe_removable_files' => $safeRemovable->count(),
    'safe_removable_size_mb' => round($safeRemovableBytes / 1048576, 2),
    'safe_removable_by_source_brand' => $safeRemovableGrouped,
    'orphan_files' => $orphans->count(),
    'orphan_size_mb' => round($orphanBytes / 1048576, 2),
    'deletion_eligible_files' => $deletionCandidates->count(),
    'deletion_eligible_size_mb' => round($deletionCandidateBytes / 1048576, 2),
    'recent_orphans_deferred' => $recentOrphans->count(),
    'missing_references' => $missing->count(),
    'orphans_by_source_brand' => $grouped,
    'orphan_examples' => $orphans->take(30),
    'missing_examples' => $missing->take(30),
    'parser_item_statuses' => DB::table('product_parser_items')
        ->selectRaw('status, COUNT(*) as items')
        ->groupBy('status')
        ->orderByDesc('items')
        ->get(),
    'parser_assets_by_item_status' => DB::table('product_parser_image_assets as assets')
        ->join('product_parser_items as items', 'items.id', '=', 'assets.parser_item_id')
        ->selectRaw('items.status, COUNT(*) as assets, SUM(CASE WHEN assets.processed_path IS NOT NULL THEN 1 ELSE 0 END) as processed')
        ->groupBy('items.status')
        ->orderByDesc('assets')
        ->get(),
    'deleted_files' => $deleted,
    'deleted_size_mb' => round($deletedBytes / 1048576, 2),
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
