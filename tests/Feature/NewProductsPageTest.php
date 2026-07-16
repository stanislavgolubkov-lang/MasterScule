<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewProductsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_products_page_shows_fifty_products_distributed_evenly_between_brands(): void
    {
        $category = Category::create([
            'name' => 'Test category',
            'name_ro' => 'Categorie test',
            'slug' => 'test-category',
        ]);

        $brands = collect(range(1, 5))->map(fn (int $brandNumber) => Brand::create([
            'name' => 'Brand '.$brandNumber,
            'slug' => 'brand-'.$brandNumber,
            'is_featured' => $brandNumber <= 2,
            'is_active' => true,
        ]));

        $brands->each(function (Brand $brand) use ($category) {
            foreach (range(1, 12) as $productNumber) {
                Product::create([
                    'brand_id' => $brand->id,
                    'category_id' => $category->id,
                    'name' => "Product {$brand->id}-{$productNumber}",
                    'name_ru' => "Товар {$brand->id}-{$productNumber}",
                    'name_ro' => "Produs {$brand->id}-{$productNumber}",
                    'slug' => "product-{$brand->id}-{$productNumber}",
                    'sku' => "NEW-{$brand->id}-{$productNumber}",
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
                    'is_new' => true,
                    'main_image' => '/images/parser-catalog/m7/sc-9337r.png',
                ]);
            }
        });

        $this
            ->get('/new')
            ->assertOk()
            ->assertViewHas('products', function ($products) use ($brands) {
                return $products->count() === 50
                    && $brands->every(fn (Brand $brand) => $products->where('brand_id', $brand->id)->count() === 10);
            })
            ->assertSee('product-grid-compact', false)
            ->assertSee('editorial-hero-new', false)
            ->assertSee('/images/new-arrivals-hero.webp', false);

        $this->assertFileExists(public_path('images/new-arrivals-hero.webp'));
    }
}
