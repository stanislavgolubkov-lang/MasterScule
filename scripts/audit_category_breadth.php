<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$limit = max(1, (int) ($argv[1] ?? 12));
$filterSlug = trim((string) ($argv[2] ?? ''));
$categories = DB::table('categories as parent')
    ->join('categories as child', 'child.parent_id', '=', 'parent.id')
    ->leftJoin('products', 'products.category_id', '=', 'parent.id')
    ->groupBy('parent.id', 'parent.slug', 'parent.name_ro', 'parent.name')
    ->havingRaw('COUNT(DISTINCT products.id) > 0')
    ->when($filterSlug !== '', fn ($query) => $query->where('parent.slug', $filterSlug))
    ->orderByRaw('COUNT(DISTINCT products.id) DESC')
    ->get([
        'parent.id',
        'parent.slug',
        'parent.name_ro',
        'parent.name',
        DB::raw('COUNT(DISTINCT products.id) AS products_count'),
    ]);

$payload = $categories->map(function ($category) use ($limit): array {
    $children = DB::table('categories')
        ->where('parent_id', $category->id)
        ->orderBy('sort_order')
        ->orderBy('slug')
        ->get(['slug', 'name_ro', 'name', 'is_assignable']);
    $products = DB::table('products')
        ->where('category_id', $category->id)
        ->orderBy('sku')
        ->limit($limit)
        ->get(['id', 'sku', 'name_ru', 'name_ro', 'status']);

    return [
        'slug' => $category->slug,
        'products_count' => (int) $category->products_count,
        'children' => $children,
        'sample_products' => $products,
    ];
});

echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
