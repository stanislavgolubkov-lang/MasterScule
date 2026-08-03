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
        Category::firstOrCreate(
            ['slug' => 'cutite-lame-rezerve'],
            [
                'name' => 'Ножи, лезвия и запасные части',
                'name_ro' => 'Cuțite, lame și piese de schimb',
                'is_active' => true,
            ],
        );

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

    public function test_content_builder_uses_curated_content_for_gys_082809(): void
    {
        $content = app(ProductParserContentBuilder::class)->ensureComplete([
            'name_ru' => 'Средства защиты GYS 082809 GYSMATIC 9/13 G',
            'description_ru' => 'Купить по лучшей цене в онлайн-магазине maximum.md.',
            'description_ro' => 'Cumpara la cel mai bun pret in magazinul online maxim.md.',
        ], '082809', 'Маска сварщика LCD GYSMATIC 9/13 G', 'GYS');

        $this->assertSame(
            'Автоматическая сварочная маска GYS 082809 GYSMATIC AUTO PRO TRUE COLOR',
            $content['name_ru'],
        );
        $this->assertStringContainsString('100 × 93 мм', $content['description_ru']);
        $this->assertStringNotContainsStringIgnoringCase('maximum.md', implode(' ', $content));
        $this->assertFalse($content['needs_content_review']);
        $this->assertSame('curated_sku', $content['translation_source_type']);
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

    public function test_verified_characteristics_are_localized_without_cyrillic_in_romanian(): void
    {
        $product = new Product([
            'attributes' => [
                'Тип' => 'Набор ударных адаптеров',
                'Механизм' => 'Быстросъёмный',
                'Угол поворота' => '360°',
                'Материал' => 'Термообработанная легированная сталь',
            ],
        ]);

        app()->setLocale('ro');

        $this->assertSame([
            'Tip' => 'Set de adaptoare de impact',
            'Mecanism' => 'Cu eliberare rapidă',
            'Unghi de rotație' => '360°',
            'Material' => 'Oțel aliat tratat termic',
        ], $product->display_attributes);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode($product->display_attributes, JSON_UNESCAPED_UNICODE));
    }

    public function test_gys_body_repair_characteristics_are_fully_localized_in_romanian(): void
    {
        $product = new Product([
            'attributes' => [
                'Тип' => 'Скрученные приварные кольца',
                'Исполнение' => 'Скрученное',
                'Количество предметов' => '50',
                'Применение' => 'Вытягивание углов и рёбер кузовных панелей',
            ],
        ]);

        app()->setLocale('ro');

        $this->assertSame([
            'Tip' => 'Inele sudabile răsucite',
            'Execuție' => 'Răsucit',
            'Număr de piese' => '50',
            'Utilizare' => 'Tragerea colțurilor și nervurilor panourilor de caroserie',
        ], $product->display_attributes);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode($product->display_attributes, JSON_UNESCAPED_UNICODE));
    }

    public function test_gys_bodyshop_measurement_characteristics_are_fully_localized_in_romanian(): void
    {
        $product = new Product([
            'attributes' => [
                'Тип' => 'Толщиномер лакокрасочного покрытия',
                'Диапазон измерения' => '0–1.80 mm',
                'Разрешение' => '0.01 mm',
                'Точность' => '±0.03 mm',
                'Материал' => 'Сталь / алюминий',
                'Источник питания' => '2 × AAA 1.5 V',
                'Габаритные размеры' => '62 × 30.5 × 105 mm',
            ],
        ]);

        app()->setLocale('ro');

        $this->assertSame([
            'Tip' => 'Aparat pentru măsurarea grosimii vopselei',
            'Interval de măsurare' => '0–1.80 mm',
            'Rezoluție' => '0.01 mm',
            'Precizie' => '±0.03 mm',
            'Material' => 'Oțel / aluminiu',
            'Sursă de alimentare' => '2 × AAA 1.5 V',
            'Dimensiuni' => '62 × 30.5 × 105 mm',
        ], $product->display_attributes);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode($product->display_attributes, JSON_UNESCAPED_UNICODE));
    }

    public function test_king_tony_impact_set_characteristics_are_fully_localized_in_romanian(): void
    {
        $product = new Product([
            'attributes' => [
                'Тип' => 'Набор ударных шестигранных головок',
                'Количество предметов' => '11',
                'Привод' => '3/4 inch',
                'Материал' => 'Хром-молибденовая сталь',
                'Покрытие' => 'Чёрное фосфатное',
                'Состав набора' => '609616M / 609622M / H10–H32',
                'Размер кейса' => '270 × 100 × 49 mm',
            ],
        ]);

        app()->setLocale('ro');

        $this->assertSame([
            'Tip' => 'Set de capete de impact hexagonale',
            'Număr de piese' => '11',
            'Antrenare' => '3/4 inch',
            'Material' => 'Oțel crom-molibden',
            'Acoperire' => 'Fosfatare neagră',
            'Componența setului' => '609616M / 609622M / H10–H32',
            'Dimensiunea cutiei' => '270 × 100 × 49 mm',
        ], $product->display_attributes);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode($product->display_attributes, JSON_UNESCAPED_UNICODE));
    }

    public function test_king_tony_bit_and_adapter_set_characteristics_are_fully_localized_in_romanian(): void
    {
        $bitSet = new Product([
            'attributes' => [
                'Тип' => 'Набор силовых бит',
                'Размеры битов' => 'H2 / H2.5 / H3 / H4 / H5 / H6',
                'Хвостовик' => '1/4 inch HEX',
                'Длина шейки' => '9,5 mm',
                'Материал' => 'Сталь S2',
                'Покрытие' => 'Фосфатирование с антикоррозионным маслом',
                'Магнитный хвостовик' => 'Да',
            ],
        ]);
        $adapterSet = new Product([
            'attributes' => [
                'Тип' => 'Набор ручных адаптеров',
                'Диапазон приводов' => '1/4 / 3/8 / 1/2 / 3/4 inch',
                'Фиксация' => 'Шариковый фиксатор',
                'Применение' => 'Для ручного инструмента',
            ],
        ]);

        app()->setLocale('ro');

        $this->assertSame([
            'Tip' => 'Set de biți de putere',
            'Dimensiunile biților' => 'H2 / H2.5 / H3 / H4 / H5 / H6',
            'Tijă' => '1/4 inch HEX',
            'Lungimea gâtului' => '9,5 mm',
            'Material' => 'Oțel S2',
            'Acoperire' => 'Fosfatare cu ulei anticoroziv',
            'Tijă magnetică' => 'Da',
        ], $bitSet->display_attributes);
        $this->assertSame([
            'Tip' => 'Set de adaptoare manuale',
            'Gama antrenărilor' => '1/4 / 3/8 / 1/2 / 3/4 inch',
            'Fixare' => 'Fixare cu bilă',
            'Utilizare' => 'Pentru scule manuale',
        ], $adapterSet->display_attributes);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode([
            $bitSet->display_attributes,
            $adapterSet->display_attributes,
        ], JSON_UNESCAPED_UNICODE));
    }

    public function test_gys_safety_characteristics_are_fully_localized_in_romanian(): void
    {
        $helmet = new Product([
            'attributes' => [
                'Тип' => 'Автоматическая сварочная маска',
                'Оптический класс' => '1/1/1/1',
                'Светлое состояние' => 'DIN 3',
                'Степень затемнения' => 'DIN 5–9 / 9–13',
                'Время срабатывания' => '0.08 ms',
                'Время возврата в светлое состояние' => '0.15–0.8 s',
                'Размер смотрового окна' => '100 × 93 mm',
                'Количество датчиков' => '4',
                'Источник питания' => 'Солнечная батарея + 2 × CR2032',
                'Режим шлифования' => 'Да',
            ],
        ]);
        $hood = new Product([
            'attributes' => [
                'Тип' => 'Защитный капюшон сварщика',
                'Материал' => 'Огнестойкий хлопок',
                'Плотность материала' => '305 g/m²',
                'Размер' => 'XL',
                'Стандарт' => 'EN ISO 11611:2015, class 1',
                'Применение' => 'Защита головы, ушей и шеи при сварке',
            ],
        ]);

        app()->setLocale('ro');

        $this->assertSame([
            'Tip' => 'Mască automată de sudură',
            'Clasă optică' => '1/1/1/1',
            'Nuanță deschisă' => 'DIN 3',
            'Grad de întunecare' => 'DIN 5–9 / 9–13',
            'Timp de reacție' => '0.08 ms',
            'Timp de revenire la starea luminoasă' => '0.15–0.8 s',
            'Dimensiunea câmpului vizual' => '100 × 93 mm',
            'Număr de senzori' => '4',
            'Sursă de alimentare' => 'Celulă solară + 2 × CR2032',
            'Mod de șlefuire' => 'Da',
        ], $helmet->display_attributes);
        $this->assertSame([
            'Tip' => 'Cagulă de protecție pentru sudor',
            'Material' => 'Bumbac ignifug',
            'Densitatea materialului' => '305 g/m²',
            'Dimensiune' => 'XL',
            'Standard' => 'EN ISO 11611:2015, class 1',
            'Utilizare' => 'Protejarea capului, urechilor și gâtului la sudare',
        ], $hood->display_attributes);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode([
            $helmet->display_attributes,
            $hood->display_attributes,
        ], JSON_UNESCAPED_UNICODE));
    }

    public function test_gys_charger_and_booster_characteristics_are_fully_localized_in_romanian(): void
    {
        $charger = new Product([
            'attributes' => [
                'Тип' => 'Автоматическое зарядное устройство',
                'Напряжение зарядки' => '6 / 12 / 24 V',
                'Зарядный ток' => '9 A (6/12 V) / 6 A (24 V)',
                'Диапазон ёмкости аккумулятора' => '18–220 Ah (6/12 V) / 15–125 Ah (24 V)',
                'Поддерживаемые аккумуляторы' => 'Свинцово-кислотные: жидкостные / GEL / AGM / VRLA',
                'Количество ступеней зарядки' => '8',
            ],
        ]);
        $booster = new Product([
            'attributes' => [
                'Тип' => 'Литиевое пусковое устройство и внешний аккумулятор',
                'Напряжение' => '12 V',
                'Тип внутреннего аккумулятора' => 'LiCoO2',
                'Ёмкость аккумулятора' => '6 Ah',
                'Энергия аккумулятора' => '92.5 Wh',
                'Пусковой ток' => '900 A',
                'Ток прокрутки' => '1400 A',
                'Пиковый ток' => '1700 A',
                'Время полной зарядки' => '1 h 15 min (67 W)',
                'Выходы питания' => 'USB-A / USB-C PD 60 W / 15 V DC, 10 A',
            ],
        ]);

        app()->setLocale('ro');

        $this->assertSame([
            'Tip' => 'Încărcător automat',
            'Tensiune de încărcare' => '6 / 12 / 24 V',
            'Curent de încărcare' => '9 A (6/12 V) / 6 A (24 V)',
            'Intervalul capacității bateriei' => '18–220 Ah (6/12 V) / 15–125 Ah (24 V)',
            'Baterii compatibile' => 'Plumb-acid: lichide / GEL / AGM / VRLA',
            'Număr de etape de încărcare' => '8',
        ], $charger->display_attributes);
        $this->assertSame([
            'Tip' => 'Booster cu litiu și baterie externă',
            'Tensiune' => '12 V',
            'Tipul bateriei interne' => 'LiCoO2',
            'Capacitatea acumulatorului' => '6 Ah',
            'Energia bateriei' => '92.5 Wh',
            'Curent de pornire' => '900 A',
            'Curent de antrenare' => '1400 A',
            'Curent de vârf' => '1700 A',
            'Timp de încărcare completă' => '1 h 15 min (67 W)',
            'Ieșiri de alimentare' => 'USB-A / USB-C PD 60 W / 15 V DC, 10 A',
        ], $booster->display_attributes);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode([
            $charger->display_attributes,
            $booster->display_attributes,
        ], JSON_UNESCAPED_UNICODE));
    }

    public function test_gys_battery_tester_characteristics_are_fully_localized_in_romanian(): void
    {
        $tester = new Product([
            'attributes' => [
                'Тип' => 'Профессиональный тестер аккумуляторов с принтером',
                'Напряжение аккумулятора' => '12 V',
                'Диапазон ёмкости аккумулятора' => '30–220 Ah',
                'Диапазон измерения напряжения' => '6–30 V',
                'Проверяемые системы' => 'Аккумулятор / стартер / генератор',
                'Поддерживаемые аккумуляторы' => 'VRLA / GEL / AGM / EFB / жидкостные',
                'Стандарты пускового тока' => 'EN / DIN / SAE / JIS / IEC / CA-MCA',
                'Встроенный принтер' => 'Термопринтер без чернил',
                'Время анализа' => '1 s',
            ],
        ]);
        $thermometer = new Product([
            'attributes' => [
                'Тип' => 'Инфракрасный термометр',
                'Температурный диапазон' => '−50…+380 °C',
                'Точность' => '±1.5 °C или ±1.5%',
                'Разрешение' => '0.1 °C',
                'Время отклика' => '500 ms',
                'Спектральный диапазон' => '8–14 µm',
                'Коэффициент излучения' => '0.95',
                'Оптическое разрешение' => '12:1',
            ],
        ]);

        app()->setLocale('ro');

        $this->assertSame([
            'Tip' => 'Tester profesional pentru baterii cu imprimantă',
            'Tensiunea acumulatorului' => '12 V',
            'Intervalul capacității bateriei' => '30–220 Ah',
            'Interval de măsurare a tensiunii' => '6–30 V',
            'Sisteme verificate' => 'Baterie / demaror / alternator',
            'Baterii compatibile' => 'VRLA / GEL / AGM / EFB / lichide',
            'Standarde pentru curentul de pornire' => 'EN / DIN / SAE / JIS / IEC / CA-MCA',
            'Imprimantă integrată' => 'Imprimantă termică fără cerneală',
            'Timp de analiză' => '1 s',
        ], $tester->display_attributes);
        $this->assertSame([
            'Tip' => 'Termometru cu infraroșu',
            'Interval de temperatură' => '−50…+380 °C',
            'Precizie' => '±1,5 °C sau ±1,5%',
            'Rezoluție' => '0.1 °C',
            'Timp de răspuns' => '500 ms',
            'Interval spectral' => '8–14 µm',
            'Emisivitate' => '0.95',
            'Rezoluție optică' => '12:1',
        ], $thermometer->display_attributes);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode([
            $tester->display_attributes,
            $thermometer->display_attributes,
        ], JSON_UNESCAPED_UNICODE));
    }

    public function test_gys_induction_characteristics_are_fully_localized_in_romanian(): void
    {
        $machine = new Product([
            'attributes' => [
                'Тип' => 'Индукционный нагреватель',
                'Мощность' => '3700 W',
                'Потребляемый ток' => '16 A',
                'Частота индукции' => '20–50 kHz',
                'Шаг регулировки мощности' => '250 W',
                'Система охлаждения' => 'Жидкостная',
                'Объём бака' => '7 l',
                'Длина кабеля индуктора' => '3 m',
                'Глубина нагрева' => 'до 6 mm',
            ],
        ]);
        $accessory = new Product([
            'attributes' => [
                'Тип' => 'Петлевой индуктор',
                'Модель индуктора' => 'S180/D55',
                'Диаметр петли' => '55 mm',
                'Назначение' => 'Нагрев цилиндрических деталей',
            ],
        ]);

        app()->setLocale('ro');

        $this->assertSame([
            'Tip' => 'Încălzitor prin inducție',
            'Putere' => '3700 W',
            'Curent absorbit' => '16 A',
            'Frecvență de inducție' => '20–50 kHz',
            'Treapta de reglare a puterii' => '250 W',
            'Sistem de răcire' => 'Cu lichid',
            'Volumul rezervorului' => '7 l',
            'Lungimea cablului inductorului' => '3 m',
            'Adâncime de încălzire' => 'până la 6 mm',
        ], $machine->display_attributes);
        $this->assertSame([
            'Tip' => 'Inductor tip buclă',
            'Modelul inductorului' => 'S180/D55',
            'Diametrul buclei' => '55 mm',
            'Destinație' => 'Încălzirea pieselor cilindrice',
        ], $accessory->display_attributes);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode([
            $machine->display_attributes,
            $accessory->display_attributes,
        ], JSON_UNESCAPED_UNICODE));
    }

    public function test_gys_plasma_consumable_characteristics_are_fully_localized_in_romanian(): void
    {
        $tip = new Product([
            'attributes' => [
                'Тип' => 'Наконечник плазменной горелки',
                'Артикул производителя' => '040212',
                'Единица продажи' => '1 шт.',
                'Заводская упаковка' => '10 шт.',
                'Совместимость' => 'TPT25 / MT35K / TPT40',
                'Диаметр отверстия' => '0.8 mm',
                'Назначение' => 'Формирование плазменной струи',
            ],
        ]);
        $nozzle = new Product([
            'attributes' => [
                'Тип' => 'Сопло плазменной горелки',
                'Единица продажи' => '1 шт.',
                'Заводская упаковка' => '4 шт.',
                'Назначение' => 'Наружный элемент плазменной горелки',
            ],
        ]);

        app()->setLocale('ro');

        $this->assertSame([
            'Tip' => 'Duză de tăiere pentru pistolet de plasmă',
            'Codul producătorului' => '040212',
            'Unitate de vânzare' => '1 buc.',
            'Ambalajul producătorului' => '10 buc.',
            'Compatibilitate' => 'TPT25 / MT35K / TPT40',
            'Diametrul orificiului' => '0.8 mm',
            'Destinație' => 'Formarea jetului de plasmă',
        ], $tip->display_attributes);
        $this->assertSame([
            'Tip' => 'Duză exterioară pentru pistolet de plasmă',
            'Unitate de vânzare' => '1 buc.',
            'Ambalajul producătorului' => '4 buc.',
            'Destinație' => 'Element exterior al pistoletului de plasmă',
        ], $nozzle->display_attributes);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode([
            $tip->display_attributes,
            $nozzle->display_attributes,
        ], JSON_UNESCAPED_UNICODE));
    }

    public function test_gys_charging_accessory_characteristics_are_fully_localized_in_romanian(): void
    {
        $adapter = new Product([
            'attributes' => [
                'Тип' => 'Адаптер для автомобильного прикуривателя',
                'Артикул производителя' => '053519',
                'Совместимость' => 'GYSFLASH 4A / GYSFLASH 7A / GYSTECH 3800',
                'Напряжение' => '12 V',
                'Предохранитель' => '10 A',
                'Назначение' => 'Подключение зарядного устройства через автомобильную розетку',
            ],
        ]);
        $battery = new Product([
            'attributes' => [
                'Тип' => 'Внутренний аккумулятор для пускового устройства',
                'Артикул производителя' => '53139',
                'Тип внутреннего аккумулятора' => 'Герметичный свинцово-кислотный',
                'Напряжение аккумулятора' => '12 V',
                'Ёмкость аккумулятора' => '18 Ah',
                'Совместимость' => 'GYSPACK AUTO / GYSPACK 400 / GYSPACK AIR',
            ],
        ]);

        app()->setLocale('ro');

        $this->assertSame([
            'Tip' => 'Adaptor pentru priza auto',
            'Codul producătorului' => '053519',
            'Compatibilitate' => 'GYSFLASH 4A / GYSFLASH 7A / GYSTECH 3800',
            'Tensiune' => '12 V',
            'Siguranță' => '10 A',
            'Destinație' => 'Conectarea încărcătorului prin priza auto',
        ], $adapter->display_attributes);
        $this->assertSame([
            'Tip' => 'Baterie internă pentru booster',
            'Codul producătorului' => '53139',
            'Tipul bateriei interne' => 'Plumb-acid etanș',
            'Tensiunea acumulatorului' => '12 V',
            'Capacitatea acumulatorului' => '18 Ah',
            'Compatibilitate' => 'GYSPACK AUTO / GYSPACK 400 / GYSPACK AIR',
        ], $battery->display_attributes);
        $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode([
            $adapter->display_attributes,
            $battery->display_attributes,
        ], JSON_UNESCAPED_UNICODE));
    }
}
