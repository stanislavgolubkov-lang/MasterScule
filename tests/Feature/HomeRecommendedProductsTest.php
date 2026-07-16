<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRecommendedProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_fifty_recommended_products_distributed_between_brands(): void
    {
        $category = Category::create([
            'name' => 'Recommended category',
            'name_ro' => 'Categorie recomandată',
            'slug' => 'recommended-category',
        ]);

        $brands = collect(range(1, 5))->map(fn (int $brandNumber) => Brand::create([
            'name' => 'Recommended brand '.$brandNumber,
            'slug' => 'recommended-brand-'.$brandNumber,
            'is_featured' => $brandNumber <= 2,
            'is_active' => true,
        ]));

        $brands->each(function (Brand $brand) use ($category) {
            foreach (range(1, 12) as $productNumber) {
                Product::create([
                    'brand_id' => $brand->id,
                    'category_id' => $category->id,
                    'name' => "Recommended product {$brand->id}-{$productNumber}",
                    'name_ru' => "Рекомендуемый товар {$brand->id}-{$productNumber}",
                    'name_ro' => "Produs recomandat {$brand->id}-{$productNumber}",
                    'slug' => "recommended-product-{$brand->id}-{$productNumber}",
                    'sku' => "REC-{$brand->id}-{$productNumber}",
                    'price' => 100 + $productNumber,
                    'currency' => 'MDL',
                    'stock_quantity' => 5,
                    'stock_status' => 'in_stock',
                    'status' => 'published',
                    'approval_status' => 'approved',
                    'needs_review' => false,
                    'needs_stock_review' => false,
                    'needs_image_review' => false,
                    'needs_category_review' => false,
                    'needs_translation_review' => false,
                    'needs_price_review' => false,
                    'is_active' => true,
                    'is_featured' => $productNumber <= 2,
                    'is_bestseller' => $productNumber === 3,
                    'is_new' => $productNumber >= 10,
                    'main_image' => '/images/parser-catalog/m7/sc-9337r.png',
                ]);
            }
        });

        $this
            ->get('/')
            ->assertOk()
            ->assertViewHas('featuredProducts', function ($products) use ($brands) {
                return $products->count() === 50
                    && $brands->every(fn (Brand $brand) => $products->where('brand_id', $brand->id)->count() === 10);
            })
            ->assertSee('product-grid-compact home-recommended-grid', false);
    }
}
