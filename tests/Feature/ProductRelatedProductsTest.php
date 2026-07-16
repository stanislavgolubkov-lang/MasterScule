<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRelatedProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_shows_up_to_twenty_compact_related_products_without_duplicates(): void
    {
        $primaryBrand = Brand::create([
            'name' => 'Primary brand',
            'slug' => 'primary-brand',
            'is_active' => true,
        ]);
        $secondaryBrand = Brand::create([
            'name' => 'Secondary brand',
            'slug' => 'secondary-brand',
            'is_active' => true,
        ]);
        $primaryCategory = Category::create([
            'name' => 'Primary category',
            'name_ro' => 'Categorie primară',
            'slug' => 'primary-category',
        ]);
        $secondaryCategory = Category::create([
            'name' => 'Secondary category',
            'name_ro' => 'Categorie secundară',
            'slug' => 'secondary-category',
        ]);

        $product = $this->createProduct($primaryBrand, $primaryCategory, 'MAIN-1');

        foreach (range(1, 14) as $number) {
            $this->createProduct($secondaryBrand, $primaryCategory, "CATEGORY-{$number}");
        }

        foreach (range(1, 10) as $number) {
            $this->createProduct($primaryBrand, $secondaryCategory, "BRAND-{$number}");
        }

        $this
            ->get('/product/'.$product->slug)
            ->assertOk()
            ->assertViewHas('relatedProducts', function ($relatedProducts) use ($product, $primaryCategory) {
                return $relatedProducts->count() === 20
                    && $relatedProducts->unique('id')->count() === 20
                    && ! $relatedProducts->contains('id', $product->id)
                    && $relatedProducts->take(14)->every(
                        fn (Product $relatedProduct) => $relatedProduct->category_id === $primaryCategory->id
                    );
            })
            ->assertSee('product-grid-compact product-grid-related', false);
    }

    private function createProduct(Brand $brand, Category $category, string $sku): Product
    {
        return Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Product '.$sku,
            'name_ru' => 'Товар '.$sku,
            'name_ro' => 'Produs '.$sku,
            'slug' => strtolower($sku),
            'sku' => $sku,
            'price' => 100,
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
            'main_image' => '/images/parser-catalog/m7/sc-9337r.png',
        ]);
    }
}
