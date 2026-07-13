<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function localImageExists(?string $path): ?bool
{
    if (! $path || preg_match('~^https?://~i', $path)) {
        return null;
    }

    $normalized = ltrim(str_replace('\\', '/', $path), '/');
    $candidates = [
        public_path($normalized),
        public_path('storage/'.preg_replace('~^storage/~', '', $normalized)),
        storage_path('app/public/'.preg_replace('~^storage/~', '', $normalized)),
    ];

    foreach (array_unique($candidates) as $candidate) {
        if (is_file($candidate)) {
            return true;
        }
    }

    return false;
}

$products = Product::with(['brand:id,name', 'categories:id'])->get();
$activeProducts = $products->filter(fn (Product $product) => $product->is_active
    && $product->stock_status === 'in_stock'
    && (int) $product->stock_quantity > 0);
$activeProductIds = array_fill_keys($activeProducts->modelKeys(), true);

$imageStats = [
    'missing_main' => 0,
    'placeholder_main' => 0,
    'broken_local_main' => 0,
    'external_main' => 0,
    'main_path_without_sku' => 0,
    'gallery_0' => 0,
    'gallery_1' => 0,
    'gallery_2' => 0,
    'gallery_3_plus' => 0,
    'gallery_images_total' => 0,
    'broken_local_gallery' => 0,
    'placeholder_gallery' => 0,
    'external_gallery' => 0,
    'needs_image_review' => 0,
];

$activeImageStats = $imageStats;
$imageUsage = [];
$brokenImageSamples = [];
$brokenGallerySamples = [];

$collectImageStats = function (Product $product, array &$stats) use (&$brokenImageSamples, &$brokenGallerySamples): void {
    $main = trim((string) $product->main_image);
    if ($main === '') {
        $stats['missing_main']++;
    } elseif (preg_match('~placeholder|fallback|no[-_ ]?image~i', $main)) {
        $stats['placeholder_main']++;
    } elseif (preg_match('~^https?://~i', $main)) {
        $stats['external_main']++;
    } elseif (localImageExists($main) === false) {
        $stats['broken_local_main']++;
        if (count($brokenImageSamples) < 25) {
            $brokenImageSamples[] = ['id' => $product->id, 'sku' => $product->sku, 'image' => $main];
        }
    }

    $normalizedSku = Str::lower(preg_replace('/[^a-z0-9]/i', '', (string) $product->sku) ?: '');
    $normalizedPath = Str::lower(preg_replace('/[^a-z0-9]/i', '', $main) ?: '');
    if ($main !== '' && str_contains($main, '/storage/parser/') && $normalizedSku !== '' && ! str_contains($normalizedPath, $normalizedSku)) {
        $stats['main_path_without_sku']++;
    }

    $gallery = array_values(array_filter((array) $product->gallery));
    $galleryCount = count($gallery);
    $bucket = $galleryCount >= 3 ? 'gallery_3_plus' : 'gallery_'.$galleryCount;
    $stats[$bucket]++;
    $stats['gallery_images_total'] += $galleryCount;

    foreach ($gallery as $galleryImage) {
        if (preg_match('~placeholder|fallback|no[-_ ]?image~i', $galleryImage)) {
            $stats['placeholder_gallery']++;
        } elseif (preg_match('~^https?://~i', $galleryImage)) {
            $stats['external_gallery']++;
        } elseif (localImageExists($galleryImage) === false) {
            $stats['broken_local_gallery']++;
            if (count($brokenGallerySamples) < 25) {
                $brokenGallerySamples[] = ['id' => $product->id, 'sku' => $product->sku, 'image' => $galleryImage];
            }
        }
    }

    if ($product->needs_image_review) {
        $stats['needs_image_review']++;
    }
};

foreach ($products as $product) {
    $collectImageStats($product, $imageStats);

    if (isset($activeProductIds[$product->id])) {
        $collectImageStats($product, $activeImageStats);
    }

    if ($product->main_image) {
        $imageUsage[$product->main_image][] = $product->sku;
    }
}

$sharedImages = collect($imageUsage)
    ->filter(fn (array $skus) => count(array_unique($skus)) > 1)
    ->map(fn (array $skus) => array_values(array_unique($skus)))
    ->sortByDesc(fn (array $skus) => count($skus));

$descriptionGroups = $products
    ->filter(fn (Product $product) => trim((string) $product->description) !== '')
    ->groupBy(fn (Product $product) => hash('sha256', trim((string) $product->description)))
    ->filter(fn ($group) => $group->count() >= 10)
    ->sortByDesc->count();

$brands = Brand::query()->orderBy('name')->get()->map(function (Brand $brand) use ($products) {
    $brandProducts = $products->where('brand_id', $brand->id);

    return [
        'brand' => $brand->name,
        'total' => $brandProducts->count(),
        'active_for_sale' => $brandProducts->filter(fn (Product $product) => $product->is_active
            && $product->stock_status === 'in_stock'
            && (int) $product->stock_quantity > 0)->count(),
        'missing_main' => $brandProducts->filter(fn (Product $product) => blank($product->main_image))->count(),
        'active_missing_main' => $brandProducts->filter(fn (Product $product) => $product->is_active
            && $product->stock_status === 'in_stock'
            && (int) $product->stock_quantity > 0
            && blank($product->main_image))->count(),
        'gallery_lt_2' => $brandProducts->filter(fn (Product $product) => count(array_filter((array) $product->gallery)) < 2)->count(),
        'active_gallery_lt_2' => $brandProducts->filter(fn (Product $product) => $product->is_active
            && $product->stock_status === 'in_stock'
            && (int) $product->stock_quantity > 0
            && count(array_filter((array) $product->gallery)) < 2)->count(),
        'needs_image_review' => $brandProducts->where('needs_image_review', true)->count(),
    ];
})->values();

$categories = Category::with('products:id')->orderBy('sort_order')->get()->map(function (Category $category) use ($products) {
    $primaryIds = $products->where('category_id', $category->id)->pluck('id');
    $pivotIds = $category->products->pluck('id');

    return [
        'id' => $category->id,
        'slug' => $category->slug,
        'name_ru' => $category->name,
        'name_ro' => $category->name_ro,
        'parent_id' => $category->parent_id,
        'products_unique' => $primaryIds->merge($pivotIds)->unique()->count(),
        'is_active' => (bool) $category->is_active,
    ];
});

$textLooksCyrillic = fn (?string $value): bool => (bool) preg_match('/\p{Cyrillic}/u', (string) $value);

$report = [
    'generated_at' => now()->toIso8601String(),
    'totals' => [
        'products' => $products->count(),
        'active_for_sale' => $activeProducts->count(),
        'categories' => Category::count(),
        'brands' => Brand::count(),
        'pages' => Page::count(),
        'multi_category_products' => $products->filter(fn (Product $product) => $product->categories->count() > 1)->count(),
        'without_primary_category' => $products->whereNull('category_id')->count(),
        'without_category_links' => $products->filter(fn (Product $product) => $product->categories->isEmpty())->count(),
    ],
    'images_all' => $imageStats,
    'images_active_for_sale' => $activeImageStats,
    'images_by_brand' => $brands,
    'broken_image_samples' => $brokenImageSamples,
    'broken_gallery_samples' => $brokenGallerySamples,
    'shared_main_images' => [
        'paths_used_by_multiple_skus' => $sharedImages->count(),
        'products_affected' => $sharedImages->sum(fn (array $skus) => count($skus)),
        'largest_samples' => $sharedImages->take(20)->all(),
    ],
    'localization' => [
        'missing_name_ru' => $products->filter(fn (Product $product) => blank($product->name))->count(),
        'missing_name_ro' => $products->filter(fn (Product $product) => blank($product->name_ro))->count(),
        'missing_description_ru' => $products->filter(fn (Product $product) => blank($product->description))->count(),
        'missing_description_ro' => $products->filter(fn (Product $product) => blank($product->description_ro))->count(),
        'same_name_ru_ro' => $products->filter(fn (Product $product) => trim((string) $product->name) === trim((string) $product->name_ro))->count(),
        'same_description_ru_ro' => $products->filter(fn (Product $product) => trim((string) $product->description) === trim((string) $product->description_ro))->count(),
        'cyrillic_in_ro_name' => $products->filter(fn (Product $product) => $textLooksCyrillic($product->name_ro))->count(),
        'cyrillic_in_ro_description' => $products->filter(fn (Product $product) => $textLooksCyrillic($product->description_ro))->count(),
        'categories_missing_ru' => $categories->filter(fn (array $category) => blank($category['name_ru']))->count(),
        'categories_missing_ro' => $categories->filter(fn (array $category) => blank($category['name_ro']))->count(),
    ],
    'content_quality' => [
        'duplicate_ru_description_groups_10_plus' => $descriptionGroups->count(),
        'products_in_duplicate_ru_description_groups' => $descriptionGroups->sum->count(),
        'largest_duplicate_description_groups' => $descriptionGroups->take(20)->map(fn ($group) => [
            'count' => $group->count(),
            'sample_skus' => $group->take(8)->pluck('sku')->values()->all(),
            'sample' => Str::limit((string) $group->first()->description, 180),
        ])->values(),
    ],
    'categories' => $categories->values(),
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
