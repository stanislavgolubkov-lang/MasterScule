<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductCategoryDetector;
use App\Services\ProductParserContentBuilder;
use App\Services\ProductPriceListImportService;
use App\Services\ProductSearchService;
use App\Services\TrisToolsEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class ProductParserQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_detector_requires_at_least_ninety_percent_confidence(): void
    {
        $result = app(ProductCategoryDetector::class)->detect(
            '6AD10-3P01',
            'Cable cutter replacement blade',
            'King Tony',
            'VDE',
        );

        $this->assertSame(55, $result['confidence']);
        $this->assertTrue($result['needs_review']);
        $this->assertNull($result['category_id']);
        $this->assertNotNull($result['detected_category_id']);
    }

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

    public function test_content_builder_removes_tristool_domain_from_official_titles(): void
    {
        $content = app(ProductParserContentBuilder::class)->build(
            'JTC-1338',
            'JTC JTC-1338',
            'JTC',
            null,
            ['category_slug' => ''],
        );

        $content = app(ProductParserContentBuilder::class)->mergeOfficialContent(
            $content,
            'TrisTool.md - Головка под ключ для стоек (MB W220)',
            null,
            'JTC-1338',
            'JTC',
        );

        $this->assertSame('Головка под ключ для стоек (MB W220)', $content['name_ru']);
        $this->assertStringNotContainsStringIgnoringCase('tristool.md', $content['name_ru']);
    }

    public function test_product_removes_tristool_from_names_when_saved(): void
    {
        $brand = Brand::create([
            'name' => 'Title cleanup brand',
            'slug' => 'title-cleanup-brand',
            'is_active' => true,
        ]);
        $category = Category::create([
            'name' => 'Title cleanup category',
            'name_ro' => 'Categorie pentru curatarea titlului',
            'slug' => 'title-cleanup-category',
            'is_active' => true,
        ]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'TrisTool.md - Фонарь диодный с магнитом',
            'name_ru' => 'TrisTool.md - Фонарь диодный с магнитом',
            'name_ro' => 'TrisTool - Lampa LED cu magnet',
            'slug' => 'test-tristool-title-cleanup',
            'sku' => 'TITLE-CLEANUP-1',
            'price' => 100,
            'currency' => 'MDL',
            'stock_quantity' => 1,
            'stock_status' => 'in_stock',
            'meta_title' => 'TrisTool.md - Фонарь диодный с магнитом | MasterScule.md',
        ]);

        $this->assertSame('Фонарь диодный с магнитом', $product->name);
        $this->assertSame('Фонарь диодный с магнитом', $product->name_ru);
        $this->assertSame('Lampa LED cu magnet', $product->name_ro);
        $this->assertSame('Фонарь диодный с магнитом | MasterScule.md', $product->meta_title);
    }

    public function test_content_builder_keeps_both_languages_when_official_content_is_partial(): void
    {
        $content = app(ProductParserContentBuilder::class)->build(
            'JTC-1338',
            'JTC JTC-1338',
            'JTC',
            null,
            ['category_slug' => 'extractoare-si-prese'],
        );

        $content = app(ProductParserContentBuilder::class)->mergeOfficialContent(
            $content,
            'Extractor auto JTC-1338',
            'Produs profesional pentru atelier si service auto.',
            'JTC-1338',
            'JTC',
        );

        $this->assertNotEmpty($content['description_ru']);
        $this->assertNotEmpty($content['description_ro']);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', $content['description_ro']);
        $this->assertTrue($content['generated_content']);
        $this->assertTrue($content['needs_content_review']);
    }

    public function test_repair_product_descriptions_fills_only_missing_catalog_text(): void
    {
        $brand = Brand::firstOrCreate(
            ['slug' => 'jtc'],
            ['name' => 'JTC', 'is_active' => true],
        );
        $category = Category::firstOrCreate(
            ['slug' => 'extractoare-si-prese'],
            ['name' => 'Extractoare si prese', 'name_ro' => 'Extractoare si prese', 'is_active' => true],
        );
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'JTC repair test',
            'slug' => 'jtc-repair-test',
            'sku' => 'JTC-REPAIR-1',
            'price' => 100,
            'currency' => 'MDL',
            'stock_quantity' => 1,
            'stock_status' => 'in_stock',
            'status' => 'draft',
            'main_image' => '/images/products/product-placeholder-toolbox.svg',
        ]);

        $this
            ->artisan('masterscule:repair-product-descriptions', ['--commit' => true])
            ->assertExitCode(0);

        $product->refresh();
        $this->assertNotEmpty($product->description_ru);
        $this->assertNotEmpty($product->description_ro);
        $this->assertNotEmpty($product->short_description_ru);
        $this->assertNotEmpty($product->short_description_ro);
        $this->assertTrue((bool) $product->generated_content);
        $this->assertTrue((bool) $product->needs_content_review);
    }

    public function test_translation_audit_can_clear_a_stale_review_flag_for_valid_content(): void
    {
        $brand = Brand::where('name', 'M7 / Mighty Seven')->first()
            ?: Brand::firstOrCreate(
                ['slug' => 'm7'],
                ['name' => 'M7 / Mighty Seven', 'is_active' => true],
            );
        $category = Category::firstOrCreate(
            ['slug' => 'parser-test-category'],
            ['name' => 'Parser test category', 'is_active' => true],
        );
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Cheie pneumatica M7',
            'name_ru' => 'Пневматический гайковерт M7',
            'name_ro' => 'Cheie pneumatica M7',
            'slug' => 'm7-translation-review-test',
            'sku' => 'M7-TR-1',
            'description' => 'Профессиональный пневматический гайковерт.',
            'description_ru' => 'Профессиональный пневматический гайковерт.',
            'description_ro' => 'Cheie pneumatica profesionala pentru service auto.',
            'price' => 100,
            'currency' => 'MDL',
            'stock_quantity' => 1,
            'stock_status' => 'in_stock',
            'status' => 'published',
            'approval_status' => 'approved',
            'is_active' => true,
            'needs_translation_review' => true,
            'source_import_batch_id' => 999,
            'main_image' => '/images/products/test.png',
        ]);

        $this
            ->artisan('masterscule:parser-audit-translations', ['--clear-valid' => true])
            ->assertExitCode(0);

        $this->assertFalse((bool) $product->fresh()->needs_translation_review);
    }

    public function test_tristool_enrichment_reads_real_description_package_and_gallery(): void
    {
        Http::fake([
            'https://tristool.md/ru/products/586/8874' => Http::response(<<<'HTML'
                <html>
                    <head>
                        <meta property="og:title" content="TrisTool.md - Машинка системы MBX для удаления ржавчины c комплектом насадок M7">
                        <meta name="description" content="Оборудование, инструмент и специнструмент для автосервиса">
                        <meta property="og:image" content="/uploaded_files/QB-0808M.jpg">
                    </head>
                    <body>
                        <ul class="breadcrumbs">
                            <li><a href="ru/category/576">СВАРКА, РИХТОВКА, ПОКРАСКА</a></li>
                            <li><a href="ru/category/586">Инструмент для разборки и рихтовки</a></li>
                        </ul>
                        <p>Артикул: QB-0808M</p>
                        <a rel="fancybox" class="photo" href="uploaded_files/QB-0808M.jpg?1734631197"><img src="uploaded_files/thumbs/QB-0808M.jpg?1734631197"></a>
                        <a rel="fancybox" class="photo" href="uploaded_files/QB-0808-02.jpg?1734631202"><img src="uploaded_files/thumbs/QB-0808-02.jpg?1734631202"></a>
                        <table><tr><td>Скорость вращения</td><td>3600 об/мин</td></tr></table>
                        <div class="container-desc">
                            <strong>Описание:</strong>
                            <ul><li>Настоящее описание товара QB-0808M для удаления ржавчины.</li></ul>
                            <strong>Комплектация:</strong>
                            <ul><li>Машина системы MBX QB-802 - 1 шт.;</li><li>Щетка мягкая QB-9411 - 1шт.;</li></ul>
                        </div>
                    </body>
                </html>
                HTML),
        ]);

        $result = app(TrisToolsEnrichmentService::class)->enrichUrl(
            'https://tristool.md/ru/products/586/8874',
            'QB-0808M',
            'M7',
        );

        $this->assertTrue($result['found']);
        $this->assertStringContainsString('Настоящее описание товара', $result['description']);
        $this->assertStringNotContainsString('Оборудование, инструмент', $result['description']);
        $this->assertSame(['Машина системы MBX QB-802 - 1 шт.;', 'Щетка мягкая QB-9411 - 1шт.;'], $result['package_contents']);
        $this->assertSame(['СВАРКА, РИХТОВКА, ПОКРАСКА', 'Инструмент для разборки и рихтовки'], $result['breadcrumb']);
        $this->assertContains('https://tristool.md/uploaded_files/QB-0808-02.jpg', $result['images']);
        $this->assertSame('3600 об/мин', $result['specs']['Скорость вращения']);
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

    public function test_m7_search_exhausts_automatic_recovery_when_exact_sku_is_missing(): void
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
        $this->assertSame(3, $result['automation_attempts']);
        $this->assertTrue($result['automation_exhausted']);
        Http::assertSentCount(3);
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
