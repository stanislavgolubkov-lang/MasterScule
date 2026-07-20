<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductParserBatch;
use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use App\Services\Catalog\ProductImageAvailabilityService;
use App\Services\Catalog\ProductPublicationGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductPublicationGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        UploadedFile::fake()->image('valid.png', 40, 40)->storeAs('products', 'valid.png', 'public');
    }

    public function test_product_without_image_cannot_be_published(): void
    {
        $product = $this->validProduct(['main_image' => null]);

        $this->assertGuardBlocked($product, 'invalid_image_missing');
    }

    public function test_product_with_missing_image_file_cannot_be_published(): void
    {
        $product = $this->validProduct(['main_image' => '/storage/products/missing.png']);

        $this->assertGuardBlocked($product, 'invalid_image_file_missing');
    }

    public function test_product_with_placeholder_image_cannot_be_published(): void
    {
        $product = $this->validProduct(['main_image' => '/images/products/product-placeholder.svg']);

        $this->assertGuardBlocked($product, 'invalid_image_placeholder');
    }

    public function test_product_with_generic_gys_brand_image_cannot_be_published(): void
    {
        $product = $this->validProduct(['main_image' => '/images/products/gys-product.svg']);

        $this->assertGuardBlocked($product, 'invalid_image_placeholder');
    }

    public function test_processed_fallback_directory_is_not_treated_as_placeholder(): void
    {
        UploadedFile::fake()->image('fallback.webp', 40, 40)
            ->storeAs('products/fallback/test-brand/test-sku', 'main.webp', 'public');

        $result = app(ProductImageAvailabilityService::class)
            ->inspect('/storage/products/fallback/test-brand/test-sku/main.webp');

        $this->assertTrue($result['available']);
        $this->assertSame('available', $result['code']);
    }

    public function test_product_without_category_cannot_be_published(): void
    {
        $product = $this->validProduct();
        $product->category_id = null;
        $product->unsetRelation('category');

        $this->assertGuardBlocked($product, 'missing_category');
    }

    public function test_product_with_cyrillic_in_ro_text_cannot_be_published(): void
    {
        $product = $this->validProduct(['description_ro' => 'Описание in limba romana']);

        $this->assertGuardBlocked($product, 'ro_contains_cyrillic');
    }

    public function test_product_with_ukrainian_text_cannot_be_published_as_russian(): void
    {
        $product = $this->validProduct([
            'name' => 'Домкрат підкатний Torin',
            'name_ru' => 'Домкрат підкатний Torin',
            'description' => 'Професійний гідравлічний домкрат для автосервісу.',
            'description_ru' => 'Професійний гідравлічний домкрат для автосервісу.',
        ]);

        $this->assertGuardBlocked($product, 'language_ukrainian_not_supported');
    }

    public function test_common_russian_catalog_words_do_not_trigger_ukrainian_guard(): void
    {
        $product = $this->validProduct([
            'description' => 'Проверьте технические характеристики инструмента перед применением.',
            'description_ru' => 'Проверьте технические характеристики инструмента перед применением.',
        ]);

        $result = app(ProductPublicationGuard::class)->evaluate($product, true);

        $this->assertTrue($result['allowed']);
        $this->assertNotContains('language_ukrainian_not_supported', $result['error_codes']);
    }

    public function test_product_with_image_review_flag_cannot_be_published(): void
    {
        $product = $this->validProduct(['needs_image_review' => true]);

        $this->assertGuardBlocked($product, 'needs_image_review');
    }

    public function test_product_with_translation_review_flag_cannot_be_published(): void
    {
        $product = $this->validProduct(['needs_translation_review' => true]);

        $this->assertGuardBlocked($product, 'needs_translation_review');
    }

    public function test_explicit_publication_can_approve_content_translation_price_and_stock_flags(): void
    {
        $product = $this->validProduct([
            'needs_content_review' => true,
            'needs_translation_review' => true,
            'needs_price_review' => true,
            'needs_stock_review' => true,
        ]);

        $result = app(ProductPublicationGuard::class)->publish($product, true, [
            'needs_content_review',
            'needs_translation_review',
            'needs_price_review',
            'needs_stock_review',
        ]);

        $this->assertTrue($result['allowed']);
        $this->assertSame('published', $product->fresh()->status);
        $this->assertFalse((bool) $product->fresh()->needs_content_review);
        $this->assertFalse((bool) $product->fresh()->needs_translation_review);
        $this->assertFalse((bool) $product->fresh()->needs_price_review);
        $this->assertFalse((bool) $product->fresh()->needs_stock_review);
    }

    public function test_fallback_parser_product_without_source_approval_cannot_be_published(): void
    {
        $product = $this->validProduct([
            'source_import_batch_id' => 77,
            'source_url' => 'https://tristool.md/product/test',
            'source_domain' => 'tristool.md',
            'fallback_source_used' => true,
            'parser_confidence' => 95,
            'source_reviewed_at' => null,
        ]);

        $this->assertGuardBlocked($product, 'fallback_not_approved');
    }

    public function test_parser_search_page_cannot_be_used_as_a_product_source(): void
    {
        $product = $this->validProduct([
            'source_import_batch_id' => 77,
            'source_url' => 'https://torinjacks.com/search?q=TRW05001',
            'source_domain' => 'torinjacks.com',
            'parser_confidence' => 100,
            'source_reviewed_at' => now(),
        ]);

        $this->assertGuardBlocked($product, 'image_search_page_source');
    }

    public function test_logo_cannot_be_used_as_a_parser_product_image(): void
    {
        $batch = ProductParserBatch::create(['title' => 'Logo guard test']);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'LOGO-GUARD-1',
            'brand' => 'Torin BIG RED',
        ]);
        ProductParserImageAsset::create([
            'parser_item_id' => $item->id,
            'source_url' => 'https://torinjacks.com/cdn/shop/files/tce300x300.png',
            'source_domain' => 'torinjacks.com',
            'processed_path' => '/storage/products/valid.png',
            'preview_path' => '/storage/products/valid.png',
            'thumb_path' => '/storage/products/valid.png',
            'status' => 'processed',
            'is_selected' => true,
            'is_main' => true,
            'has_watermark' => true,
        ]);
        $product = $this->validProduct([
            'source_import_batch_id' => $batch->id,
            'source_parser_item_id' => $item->id,
            'source_url' => 'https://torinjacks.com/products/example',
            'source_domain' => 'torinjacks.com',
            'parser_confidence' => 100,
            'source_reviewed_at' => now(),
        ]);

        $this->assertGuardBlocked($product, 'image_asset_non_product');
    }

    public function test_valid_product_can_be_published(): void
    {
        $product = $this->validProduct();
        $result = app(ProductPublicationGuard::class)->publish($product, true);

        $this->assertTrue($result['allowed']);
        $this->assertSame('published', $product->fresh()->status);
        $this->assertSame('approved', $product->fresh()->approval_status);
        $this->assertTrue((bool) $product->fresh()->is_active);
    }

    public function test_publish_command_is_dry_run_by_default(): void
    {
        $product = $this->validProduct();

        $this->artisan('masterscule:publish-parser-drafts')->assertSuccessful();

        $this->assertSame('draft', $product->fresh()->status);
        $this->assertFalse((bool) $product->fresh()->is_active);
    }

    public function test_new_product_defaults_to_blocked_draft(): void
    {
        $brand = Brand::create([
            'name' => 'Default Test Brand',
            'slug' => 'default-test-brand',
            'is_active' => true,
        ]);
        $category = Category::create([
            'name' => 'Default Test Category',
            'slug' => 'default-test-category',
            'is_active' => true,
        ]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Default Draft Product',
            'slug' => 'default-draft-product',
            'sku' => 'DEFAULT-DRAFT-SKU',
            'price' => 100,
            'currency' => 'MDL',
        ])->fresh();

        $this->assertSame('draft', $product->status);
        $this->assertSame('pending_review', $product->approval_status);
        $this->assertFalse((bool) $product->is_active);
        $this->assertTrue((bool) $product->needs_review);
        $this->assertTrue((bool) $product->needs_image_review);
        $this->assertTrue((bool) $product->needs_category_review);
        $this->assertTrue((bool) $product->needs_translation_review);
        $this->assertTrue((bool) $product->needs_price_review);
    }

    private function validProduct(array $overrides = []): Product
    {
        $brand = Brand::create([
            'name' => 'Test Brand '.uniqid(),
            'slug' => 'test-brand-'.uniqid(),
            'is_active' => true,
        ]);
        $category = Category::create([
            'name' => 'Тестовая категория',
            'name_ro' => 'Categorie test',
            'slug' => 'category-'.uniqid(),
            'is_active' => true,
        ]);

        return Product::create(array_merge([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Тестовый инструмент',
            'name_ru' => 'Профессиональный инструмент',
            'name_ro' => 'Instrument profesional',
            'slug' => 'product-'.uniqid(),
            'sku' => 'SKU-'.uniqid(),
            'short_description' => 'Надёжный инструмент для мастерской.',
            'short_description_ru' => 'Надёжный инструмент для мастерской.',
            'short_description_ro' => 'Instrument fiabil pentru atelier.',
            'description' => 'Подробное описание профессионального инструмента.',
            'description_ru' => 'Подробное описание профессионального инструмента.',
            'description_ro' => 'Descriere detaliata a instrumentului profesional.',
            'price' => 100,
            'currency' => 'MDL',
            'stock_quantity' => 3,
            'stock_status' => 'in_stock',
            'status' => 'draft',
            'approval_status' => 'pending_review',
            'main_image' => '/storage/products/valid.png',
            'gallery' => ['/storage/products/valid.png'],
            'is_active' => false,
            'needs_review' => false,
            'needs_image_review' => false,
            'needs_category_review' => false,
            'needs_translation_review' => false,
            'needs_price_review' => false,
            'needs_stock_review' => false,
        ], $overrides));
    }

    private function assertGuardBlocked(Product $product, string $code): void
    {
        $result = app(ProductPublicationGuard::class)->evaluate($product, true);

        $this->assertFalse($result['allowed']);
        $this->assertContains($code, $result['error_codes']);
    }
}
