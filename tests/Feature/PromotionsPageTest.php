<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotions_page_shows_rich_empty_state_when_no_discounts_exist(): void
    {
        $this
            ->get('/promotions')
            ->assertOk()
            ->assertSee('/images/promotions-coming-soon.webp', false)
            ->assertSee(__('ui.promotions_empty_title'))
            ->assertSee('promotions-empty', false);

        $this->assertFileExists(public_path('images/promotions-coming-soon.webp'));
    }

    public function test_promotions_page_shows_discounted_products_instead_of_empty_state(): void
    {
        $brand = Brand::create([
            'name' => 'Promotion brand',
            'slug' => 'promotion-brand',
            'is_active' => true,
        ]);
        $category = Category::create([
            'name' => 'Promotion category',
            'name_ro' => 'Categorie promoțională',
            'slug' => 'promotion-category',
        ]);
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Promotion product',
            'name_ru' => 'Акционный товар',
            'name_ro' => 'Produs promoțional',
            'slug' => 'promotion-product',
            'sku' => 'PROMO-1',
            'price' => 80,
            'old_price' => 100,
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
            'is_discounted' => true,
            'main_image' => '/images/parser-catalog/m7/sc-9337r.png',
        ]);

        $this
            ->get('/promotions')
            ->assertOk()
            ->assertSee($product->display_name)
            ->assertDontSee('promotions-empty', false);
    }
}
