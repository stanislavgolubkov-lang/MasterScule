<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductCategoryDetector;
use App\Services\ProductParserContentBuilder;
use App\Services\ProductPriceListImportService;
use App\Services\ProductSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class ProductParserQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_specific_product_signals_override_broad_price_group(): void
    {
        $pneumatic = Category::firstOrCreate(
            ['slug' => 'scule-pneumatice'],
            [
                'name' => 'Пневматический инструмент',
                'name_ro' => 'Instrument pneumatic',
                'is_active' => true,
            ],
        );

        foreach ([
            'furtunuri-cuple-accesorii' => ['Шланги, муфты и аксессуары', 'Furtunuri, cuple si accesorii'],
            'polizoare-si-slefuitoare-pneumatice' => ['Пневматические шлифмашины', 'Polizoare pneumatice'],
        ] as $slug => [$nameRu, $nameRo]) {
            Category::firstOrCreate(
                ['slug' => $slug],
                [
                    'parent_id' => $pneumatic->id,
                    'name' => $nameRu,
                    'name_ro' => $nameRo,
                    'is_active' => true,
                ],
            );
        }

        $detector = app(ProductCategoryDetector::class);
        $coupler = $detector->detect('SG-912', 'Смазочная муфта, быстросъёмная', 'M7 / Mighty Seven', 'Авторемонтный Пневмоинструмент');
        $grinder = $detector->detect('QT-102', 'Пневматическая шлифовальная машинка Турбинка', 'M7 / Mighty Seven', 'Авторемонтный Пневмоинструмент');

        $this->assertSame('furtunuri-cuple-accesorii', $coupler['category_slug']);
        $this->assertSame('polizoare-si-slefuitoare-pneumatice', $grinder['category_slug']);
        $this->assertFalse($coupler['needs_review']);
        $this->assertFalse($grinder['needs_review']);
    }

    public function test_content_builder_creates_both_languages_without_cyrillic_in_ro(): void
    {
        $content = app(ProductParserContentBuilder::class)->build(
            'SG-912',
            'Смазочная муфта, быстросъёмная',
            'M7 / Mighty Seven',
            'Авторемонтный Пневмоинструмент',
            [
                'category_slug' => 'furtunuri-cuple-accesorii',
                'category_name_ru' => 'Шланги, муфты и аксессуары',
                'category_name_ro' => 'Furtunuri, cuple si accesorii',
            ],
        );

        $this->assertNotEmpty($content['name_ru']);
        $this->assertNotEmpty($content['name_ro']);
        $this->assertNotEmpty($content['description_ru']);
        $this->assertNotEmpty($content['description_ro']);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', $content['name_ro'].' '.$content['description_ro']);
    }

    public function test_existing_product_index_keeps_parser_draft_ownership_fields(): void
    {
        $brand = Brand::firstOrCreate(
            ['slug' => 'm7'],
            ['name' => 'M7', 'is_active' => true],
        );
        $category = Category::firstOrCreate(
            ['slug' => 'parser-test-category'],
            ['name' => 'Parser test category', 'is_active' => true],
        );
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Parser draft',
            'slug' => 'parser-draft-sg-912',
            'sku' => 'SG-912',
            'price' => 1,
            'currency' => 'MDL',
            'stock_quantity' => 0,
            'stock_status' => 'out_of_stock',
            'status' => 'draft',
            'source_import_batch_id' => 77,
        ]);

        $method = new ReflectionMethod(ProductPriceListImportService::class, 'existingProductsIndex');
        $indexedProducts = $method->invoke(app(ProductPriceListImportService::class));
        $indexed = collect($indexedProducts)->first(fn (Product $candidate) => $candidate->id === $product->id);

        $this->assertNotNull($indexed);
        $this->assertSame('draft', $indexed->status);
        $this->assertSame(77, $indexed->source_import_batch_id);
    }

    public function test_m7_search_stops_after_official_api_when_exact_sku_is_missing(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://www.mighty-seven.com/api_v1/getprodut_list_search' => Http::response([
                'code' => '200',
                'data' => '<a href="/product/1"><img src="/upload/product/wrong.png"><h3>Coupler</h3><p>SG-912L</p></a>',
            ]),
        ]);

        $result = app(ProductSearchService::class)->searchForParser('SG-912', 'M7 / Mighty Seven', preferLocal: false);

        $this->assertFalse($result['found']);
        $this->assertSame([], $result['images']);
        Http::assertSentCount(1);
    }

    public function test_real_utf8_price_list_terms_are_categorized(): void
    {
        foreach ([
            'consumabile-pentru-scule-pneumatice' => 'Расходники для пневмоинструмента',
            'furtunuri-cuple-accesorii' => 'Шланги, муфты и аксессуары',
        ] as $slug => $name) {
            Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true],
            );
        }

        $detector = app(ProductCategoryDetector::class);
        $stones = $detector->detect(
            'QB-9211A',
            'Набор точильных камней, 5 предметов',
            'M7 / Mighty Seven',
            'Авторемонтный Пневмоинструмент',
        );
        $coupler = $detector->detect(
            'SY-210F',
            'Быстроразъём Europe, внутренняя резьба',
            'M7 / Mighty Seven',
            'Авторемонтный Пневмоинструмент',
            'Шланги и Разъёмы',
        );

        $this->assertSame('consumabile-pentru-scule-pneumatice', $stones['category_slug']);
        $this->assertSame('furtunuri-cuple-accesorii', $coupler['category_slug']);
        $this->assertFalse($stones['needs_review']);
        $this->assertFalse($coupler['needs_review']);
    }
}
