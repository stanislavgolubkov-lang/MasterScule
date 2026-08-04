<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_responses_include_security_headers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_secure_responses_include_hsts(): void
    {
        $this->get('https://localhost/')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_sitemap_contains_public_catalog_urls_only(): void
    {
        $brand = Brand::create([
            'name' => 'Sitemap brand',
            'slug' => 'sitemap-brand',
            'is_active' => true,
        ]);
        $category = Category::create([
            'name' => 'Категория карты сайта',
            'name_ro' => 'Categorie sitemap',
            'slug' => 'sitemap-category',
            'is_active' => true,
        ]);
        $published = Product::create($this->productData($brand, $category, 'published-sitemap-product', 'SITEMAP-1'));
        Product::create(array_replace($this->productData($brand, $category, 'draft-sitemap-product', 'SITEMAP-2'), [
            'status' => 'draft',
            'is_active' => false,
        ]));

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('catalog', $category->slug), false)
            ->assertSee(route('brand.show', $brand->slug), false)
            ->assertSee(route('product.show', $published->slug), false)
            ->assertDontSee('draft-sitemap-product', false);
    }

    private function productData(Brand $brand, Category $category, string $slug, string $sku): array
    {
        return [
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Товар для карты сайта',
            'name_ru' => 'Товар для карты сайта',
            'name_ro' => 'Produs pentru sitemap',
            'slug' => $slug,
            'sku' => $sku,
            'description' => 'Подробное описание товара для проверки карты сайта.',
            'description_ru' => 'Подробное описание товара для проверки карты сайта.',
            'description_ro' => 'Descriere detaliată a produsului pentru verificarea sitemapului.',
            'price' => 100,
            'currency' => 'MDL',
            'stock_quantity' => 1,
            'stock_status' => 'in_stock',
            'status' => 'published',
            'approval_status' => 'approved',
            'is_active' => true,
            'needs_review' => false,
            'needs_category_review' => false,
            'needs_translation_review' => false,
            'needs_price_review' => false,
        ];
    }
}
