<?php

namespace Tests\Feature;

use App\Services\ProductSearchService;
use App\Services\ProductSources\Adapters\JtcOfficialAdapter;
use App\Services\ProductSources\Adapters\MightySevenOfficialAdapter;
use App\Services\ProductSources\ProductSourceRegistry;
use App\Services\TrisToolsEnrichmentService;
use App\Models\Category;
use App\Models\ProductParserBatch;
use App\Models\ProductParserItem;
use App\Services\ProductDraftService;
use App\Services\ProductCategoryDetector;
use App\Services\ProductCategoryResolverService;
use App\Services\ProductTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ProductSourcePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_returns_only_official_brand_domains_in_priority_order(): void
    {
        $sources = app(ProductSourceRegistry::class)->forBrand('M7 / Mighty Seven');

        $this->assertSame('mighty-seven.com', $sources[0]['domain']);
        $this->assertSame(100, $sources[0]['priority']);
        $this->assertFalse((bool) ($sources[0]['fallback_only'] ?? false));
    }

    public function test_tristools_is_checked_before_an_exact_official_result(): void
    {
        config()->set('product_parser.tristools_fallback_enabled', true);
        config()->set('product_parser.tristools.enabled', true);
        $fallback = Mockery::mock(TrisToolsEnrichmentService::class);
        $fallback->shouldReceive('enrich')->once()->with('SG-912', 'M7 / Mighty Seven')->andReturn([
            'found' => false,
            'confidence' => 0,
        ]);
        $this->app->instance(TrisToolsEnrichmentService::class, $fallback);

        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'getprodut_list_search')) {
                return Http::response(['data' => '<a href="/product/3597"><img src="/upload/product/sg912.png"><h3>Quick Release Grease Coupler</h3><p>SG-912</p></a>']);
            }

            return Http::response('<html><head><meta name="description" content="Official M7 coupler description"><meta property="og:image" content="https://www.mighty-seven.com/upload/product/sg912.png"></head><body><h1>Quick Release Grease Coupler SG-912</h1><table><tr><th>Model</th><td>SG-912</td></tr></table></body></html>');
        });

        $result = app(ProductSearchService::class)->searchForParser('SG-912', 'M7 / Mighty Seven', preferLocal: false);

        $this->assertTrue($result['found']);
        $this->assertSame('www.mighty-seven.com', $result['official_source_domain']);
        $this->assertFalse($result['fallback_source_used']);
        $this->assertGreaterThanOrEqual(90, $result['source_match_confidence']);
    }

    public function test_fast_tristools_search_does_not_call_an_official_source(): void
    {
        config()->set('product_parser.tristools_fallback_enabled', true);
        $tristools = Mockery::mock(TrisToolsEnrichmentService::class);
        $tristools->shouldReceive('enrich')->once()->with('FAST-404', 'King Tony')->andReturn([
            'found' => false,
            'confidence' => 0,
        ]);
        $this->app->instance(TrisToolsEnrichmentService::class, $tristools);
        Http::preventStrayRequests();

        $result = app(ProductSearchService::class)
            ->searchTrisToolForParser('FAST-404', 'King Tony');

        $this->assertFalse($result['found']);
        $this->assertSame(1, $result['automation_attempts']);
    }

    public function test_tristools_content_and_images_remain_primary_when_official_content_is_also_found(): void
    {
        config()->set('product_parser.tristools_fallback_enabled', true);
        config()->set('product_parser.tristools.enabled', true);
        $fallback = Mockery::mock(TrisToolsEnrichmentService::class);
        $fallback->shouldReceive('enrich')->once()->andReturn([
            'found' => true,
            'title' => 'Fallback product',
            'description' => 'Fallback description',
            'specs' => [],
            'images' => ['https://tristool.md/images/product/full.jpg'],
            'source_urls' => ['https://tristool.md/product/1'],
            'confidence' => 95,
        ]);
        $this->app->instance(TrisToolsEnrichmentService::class, $fallback);

        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'getprodut_list_search')) {
                return Http::response(['data' => '<a href="/product/3597"><img src="/upload/product/sg912.png"><h3>Quick Release Grease Coupler</h3><p>SG-912</p></a>']);
            }

            return Http::response('<html><head><meta property="og:image" content="https://www.mighty-seven.com/upload/product/sg912.png"></head><body><h1>Quick Release Grease Coupler SG-912</h1></body></html>');
        });

        $result = app(ProductSearchService::class)->searchForParser('SG-912', 'M7 / Mighty Seven', preferLocal: false);

        $this->assertTrue($result['found']);
        $this->assertSame('https://tristool.md/images/product/full.jpg', $result['images'][0]);
        $this->assertContains('https://www.mighty-seven.com/upload/product/sg912.png', $result['images']);
        $this->assertFalse($result['fallback_source_used']);
        $this->assertSame('tristools_then_official', $result['image_source_type']);
        $this->assertSame('Fallback description', $result['description']);
        $this->assertSame('tristools_primary', $result['content_source_type']);
        $this->assertSame('www.mighty-seven.com', $result['official_source_domain']);
    }

    public function test_tristools_exact_sku_reads_bilingual_product_card_and_full_image(): void
    {
        config()->set('product_parser.tristools.enabled', true);
        config()->set('product_parser.tristools.rate_limit_ms', 0);

        Http::preventStrayRequests();
        Http::fake([
            'https://tristool.md/ru/search?searchword=6AD10-325' => Http::response(
                '<a class="cl-item" href="/ru/products/673/10108">'
                .'<img src="/uploaded_files/thumbs/6AD10-325_250x250.jpg">'
                .'<h6>Каблерез усиленный с трещоткой 255 мм</h6>'
                .'<span class="article">6AD10-325</span>'
                .'</a>'
            ),
            'https://tristool.md/ru/products/673/10108' => Http::response(
                '<html><head><meta property="og:image" content="/uploaded_files/6AD10-325.jpg"></head><body>'
                .'<div class="breadcrumbs"><a>Инструмент и мебель</a><a>Электромонтажный инструмент</a></div>'
                .'<h1>Каблерез усиленный с трещоткой 255 мм</h1>'
                .'<div class="container-desc"><p>Специальный профиль режущих губок для кабеля.</p></div>'
                .'</body></html>'
            ),
            'https://tristool.md/ro/products/673/10108' => Http::response(
                '<html><head><meta property="og:image" content="/uploaded_files/6AD10-325.jpg"></head><body>'
                .'<div class="breadcrumbs"><a>Instrument și mobilier</a><a>Instrumente pentru electricieni</a></div>'
                .'<h1>Foarfecă cu clichet pentru cablu 255 mm</h1>'
                .'<div class="container-desc"><p>Profil special al fălcilor pentru tăierea cablului.</p></div>'
                .'</body></html>'
            ),
        ]);

        $result = app(TrisToolsEnrichmentService::class)->enrich('6AD10-325', 'King Tony');

        $this->assertTrue($result['found']);
        $this->assertGreaterThanOrEqual(90, $result['confidence']);
        $this->assertSame('Каблерез усиленный с трещоткой 255 мм', $result['title_ru']);
        $this->assertSame('Foarfecă cu clichet pentru cablu 255 mm', $result['title_ro']);
        $this->assertStringContainsString('режущих губок', $result['description_ru']);
        $this->assertStringContainsString('tăierea cablului', $result['description_ro']);
        $this->assertSame('https://tristool.md/uploaded_files/6AD10-325.jpg', $result['images'][0]);
        $this->assertSame([
            'https://tristool.md/ru/products/673/10108',
            'https://tristool.md/ro/products/673/10108',
        ], $result['source_urls']);
    }

    public function test_6ad10_325_maps_to_electrician_cable_tools_with_ninety_percent_confidence(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'clesti-electrician-si-cabluri'],
            [
                'name' => 'Клещи электрика и кабельный инструмент',
                'name_ro' => 'Clești electrician și cabluri',
                'is_active' => true,
            ],
        );

        $result = app(ProductCategoryDetector::class)->detectFromTrisTools(
            '6AD10-325',
            'Каблерез усиленный с трещоткой 255 мм',
            'King Tony',
            ['Инструмент и мебель', 'Электромонтажный инструмент'],
            'Специальный профиль режущих губок для кабеля.',
        );

        $this->assertSame($category->id, $result['category_id']);
        $this->assertSame('clesti-electrician-si-cabluri', $result['category_slug']);
        $this->assertGreaterThanOrEqual(90, $result['confidence']);
        $this->assertFalse($result['needs_review']);
    }

    public function test_russian_text_returned_from_a_ro_url_is_translated_instead_of_trusted(): void
    {
        Http::preventStrayRequests();
        Http::fakeSequence()
            ->push([[['Foarfecă pentru cablu', null, null, null]]])
            ->push([[['Descriere în limba română.', null, null, null]]]);

        $result = app(ProductTranslationService::class)->bilingual([
            'title' => 'Каблерез',
            'description' => 'Описание каблереза.',
            'title_ru' => 'Каблерез',
            'description_ru' => 'Описание каблереза.',
            'title_ro' => 'Каблерез',
            'description_ro' => 'Описание каблереза.',
        ]);

        $this->assertSame('Каблерез', $result['name_ru']);
        $this->assertSame('Foarfecă pentru cablu', $result['name_ro']);
        $this->assertSame('Descriere în limba română.', $result['description_ro']);
        $this->assertTrue($result['complete']);
        $this->assertSame('machine_translation', $result['translation_source_type']);
    }

    public function test_unknown_tristools_category_is_created_and_assigned(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*' => Http::response([[['Calibratoare cuantice', null, null, null]]]),
        ]);

        $batch = ProductParserBatch::create([
            'title' => 'TrisTool category creation',
            'source_type' => 'single',
            'status' => 'running',
        ]);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'QT-9000',
            'brand' => 'Test',
            'status' => 'searching',
        ]);

        $resolved = app(ProductCategoryResolverService::class)->resolveFromSourceResult($item, [
            'found' => true,
            'title' => 'Квантовый калибратор QT-9000',
            'description' => 'Прибор для точной калибровки.',
            'breadcrumb' => ['Новая техника', 'Квантовые калибраторы'],
            'specs' => [],
            'confidence' => 96,
            'source_match_confidence' => 96,
        ]);

        $item->refresh();
        $category = Category::findOrFail($item->category_id);

        $this->assertTrue($resolved);
        $this->assertSame('Квантовые калибраторы', $category->name);
        $this->assertSame('Calibratoare cuantice', $category->name_ro);
        $this->assertFalse((bool) $item->needs_category_review);
        $this->assertGreaterThanOrEqual(90, $item->category_confidence_score);
        $this->assertSame('tristools_category_created', $item->category_detection_method);
    }

    public function test_tristools_enrichment_prefers_full_size_images_over_resized_previews(): void
    {
        config()->set('product_parser.tristools.enabled', true);

        Http::preventStrayRequests();
        Http::fake([
            'https://tristool.md/ru/search?searchword=JTC-1339' => Http::response(
                '<a class="cl-item" href="/ru/product/jtc-1339">'
                .'<img src="/images/products/resized/JTC-1339_250x250.jpg">'
                .'<h6>JTC-1339</h6><span class="article">JTC-1339</span>'
                .'</a>'
            ),
            'https://tristool.md/ru/product/jtc-1339' => Http::response(
                '<html><head><meta property="og:title" content="JTC-1339">'
                .'<meta property="og:image" content="/images/products/resized/JTC-1339_600x600.jpg"></head>'
                .'<body><h1>JTC-1339</h1></body></html>'
            ),
        ]);

        $result = app(TrisToolsEnrichmentService::class)->enrich('JTC-1339', 'JTC');

        $this->assertTrue($result['found']);
        $this->assertSame('https://tristool.md/images/products/JTC-1339.jpg', $result['images'][0]);
        $this->assertNotContains('https://tristool.md/images/products/resized/JTC-1339_600x600.jpg', $result['images']);
    }

    public function test_tristools_builds_product_description_from_title_and_specs_instead_of_site_seo_text(): void
    {
        config()->set('product_parser.tristools.enabled', true);

        Http::preventStrayRequests();
        Http::fake([
            'https://tristool.md/ru/search?searchword=302D10' => Http::response(
                '<a class="cl-item" href="/ru/products/487/6390">'
                .'<img src="/uploaded_files/thumbs/302D.jpg?1598878899">'
                .'<h6>Насадка бита IPR10</h6><span class="article">302D10</span>'
                .'</a>'
            ),
            'https://tristool.md/ru/products/487/6390' => Http::response(
                '<html><head>'
                .'<meta property="og:title" content="TrisTool.md - Насадка бита IPR10">'
                .'<meta property="og:description" content="Оборудование, инструмент и специнструмент для автосервиса, электроинструмент">'
                .'<meta property="og:image" content="/uploaded_files/302D.jpg?1598878899">'
                .'</head><body>'
                .'<table><tr><td>Посадочный квадрат</td><td>3/8&quot;</td></tr>'
                .'<tr><td>Длина</td><td>50 мм</td></tr></table>'
                .'<div class="container-desc"><div class="js-hidden wrap"></div></div>'
                .'</body></html>'
            ),
        ]);

        $result = app(TrisToolsEnrichmentService::class)->enrich('302D10', 'King Tony');

        $this->assertTrue($result['found']);
        $this->assertSame('Насадка бита IPR10', $result['title']);
        $this->assertStringContainsString('Посадочный квадрат: 3/8"', $result['description']);
        $this->assertStringContainsString('Длина: 50 мм', $result['description']);
        $this->assertStringNotContainsString('Оборудование, инструмент', $result['description']);
        $this->assertSame(['https://tristool.md/uploaded_files/302D.jpg'], $result['images']);
    }

    public function test_forced_tristools_lookup_below_ninety_percent_is_marked_for_review(): void
    {
        config()->set('product_parser.tristools_fallback_enabled', true);
        $fallback = Mockery::mock(TrisToolsEnrichmentService::class);
        $fallback->shouldReceive('enrich')->times(3)->andReturn([
            'found' => true,
            'title' => 'Fallback product',
            'description' => 'Fallback description',
            'specs' => [],
            'images' => ['https://tristool.md/image.jpg'],
            'source_urls' => ['https://tristool.md/product/1'],
            'confidence' => 85,
        ]);
        $this->app->instance(TrisToolsEnrichmentService::class, $fallback);

        $result = app(ProductSearchService::class)->searchFallbackForParser('ABC-1', 'Unknown');

        $this->assertFalse($result['fallback_source_used']);
        $this->assertTrue($result['needs_source_review']);
        $this->assertSame('tristools_primary', $result['content_source_type']);
    }

    public function test_tristools_parser_result_carries_description_package_and_breadcrumb_to_draft(): void
    {
        config()->set('product_parser.tristools_fallback_enabled', true);
        config()->set('product_parser.tristools.enabled', true);
        config()->set('product_parser.tristools_image_first', true);
        Category::firstOrCreate(
            ['slug' => 'tinichigerie-si-richtuire'],
            ['name' => 'Tinichigerie si richtuire', 'name_ro' => 'Tinichigerie si richtuire', 'is_active' => true],
        );
        $fallback = Mockery::mock(TrisToolsEnrichmentService::class);
        $fallback->shouldReceive('enrich')->times(3)->andReturn([
            'found' => true,
            'title' => 'TrisTool.md - Машинка системы MBX для удаления ржавчины c комплектом насадок M7',
            'description' => 'Настоящее описание товара QB-0808M для удаления ржавчины.',
            'package_contents' => ['Машина системы MBX QB-802 - 1 шт.', 'Щетка мягкая QB-9411 - 1шт.'],
            'breadcrumb' => ['СВАРКА, РИХТОВКА, ПОКРАСКА', 'Инструмент для разборки и рихтовки'],
            'specs' => ['Скорость вращения' => '3600 об/мин'],
            'images' => [],
            'source_urls' => ['https://tristool.md/ru/products/586/8874'],
            'confidence' => 96,
        ]);
        $this->app->instance(TrisToolsEnrichmentService::class, $fallback);

        $result = app(ProductSearchService::class)->searchFallbackForParser('QB-0808M', 'M7 / Mighty Seven');
        $this->assertSame('Настоящее описание товара QB-0808M для удаления ржавчины.', $result['description']);
        $this->assertSame(['Машина системы MBX QB-802 - 1 шт.', 'Щетка мягкая QB-9411 - 1шт.'], $result['package_contents']);

        $batch = ProductParserBatch::create(['title' => 'TrisTool future parser test', 'source_type' => 'single']);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'QB-0808M',
            'brand' => 'M7 / Mighty Seven',
            'category_id' => Category::where('slug', 'tinichigerie-si-richtuire')->value('id'),
            'status' => 'ready_for_review',
            'confidence_score' => 96,
            'parsed_price' => 3700,
            'parsed_stock' => 1,
            'name_ru' => 'Машинка системы MBX для удаления ржавчины c комплектом насадок M7',
            'name_ro' => 'Masina sistem MBX M7 QB-0808M',
            'description_ru' => $result['description'],
            'description_ro' => 'Masina sistem MBX M7 QB-0808M pentru service auto.',
            'short_description_ru' => 'Машинка системы MBX M7 QB-0808M.',
            'short_description_ro' => 'Masina sistem MBX M7 QB-0808M.',
            'found_specs_json' => ($result['specs'] ?? []) + [
                '_package_contents' => $result['package_contents'],
                '_breadcrumb' => $result['breadcrumb'],
            ],
        ]);

        $product = app(ProductDraftService::class)->createDraft($item);

        $this->assertSame(['Машина системы MBX QB-802 - 1 шт.', 'Щетка мягкая QB-9411 - 1шт.'], $product->package_contents);
        $this->assertArrayHasKey('Скорость вращения', $product->attributes);
        $this->assertArrayNotHasKey('_package_contents', $product->attributes);
        $this->assertSame('tinichigerie-si-richtuire', $product->category->slug);
        $this->assertSame(1, $batch->fresh()->created_drafts);
    }

    public function test_mighty_seven_adapter_accepts_grouped_set_sku(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*getprodut_list_search*' => Http::response(['data' => '<a href="/product/2801"><div class="pic"><img src="/upload/product/qb9211.png"></div><h3>Grinder Stone Set</h3><p>QB-9211A/B[SET]</p></a>']),
        ]);

        $result = app(MightySevenOfficialAdapter::class)->searchBySku('QB-9211A', 'M7 / Mighty Seven');

        $this->assertTrue($result->found);
        $this->assertSame('https://www.mighty-seven.com/product/2801', $result->url);
        $this->assertSame('https://www.mighty-seven.com/upload/product/qb9211.png', $result->payload['api_image']);
    }

    public function test_mighty_seven_adapter_accepts_packaging_suffix(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*getprodut_list_search*' => Http::response(['data' => '<a href="/product/3001"><div class="pic"><img src="/upload/product/db1850.png"></div><h3>18V Battery</h3><p>DB-1850P</p></a>']),
        ]);

        $result = app(MightySevenOfficialAdapter::class)->searchBySku('DB-1850', 'M7 / Mighty Seven');

        $this->assertTrue($result->found);
        $this->assertSame('https://www.mighty-seven.com/product/3001', $result->url);
    }

    public function test_jtc_adapter_does_not_treat_search_page_and_logo_as_product_media(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://eng.jtc.com.tw/product/index.php?keywords=JTC-1339&mode=search' => Http::response('<html><body>No exact product link</body></html>'),
            'https://www.jtcautotools.com/search?q=JTC-1339' => Http::response(
                '<html><body>'
                .'<a href="/?q=JTC-1339&options%5Bprefix%5D=last&sort_by=relevance">JTC-1339</a>'
                .'<img src="//jtcautotools.com/cdn/shop/files/2024-05-22_170521.png?v=1731917284">'
                .'</body></html>'
            ),
        ]);

        $result = app(JtcOfficialAdapter::class)->searchBySku('JTC-1339', 'JTC');

        $this->assertFalse($result->found);
    }

    public function test_jtc_adapter_keeps_sku_image_and_rejects_brand_logo(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://eng.jtc.com.tw/product/index.php?keywords=JTC-1339&mode=search' => Http::response(
                '<html><body><a href="/product/?mode=data&id=1339"><span>JTC-1339</span></a></body></html>'
            ),
            'https://eng.jtc.com.tw/product/?mode=data&id=1339' => Http::response(
                '<html><head><meta name="description" content="Official JTC product"></head><body>'
                .'<h1>JTC-1339 Spring strut socket</h1>'
                .'<img src="/images/brand-jtc.png">'
                .'<img src="/upload/product/JTC-1339.jpg">'
                .'</body></html>'
            ),
        ]);

        $adapter = app(JtcOfficialAdapter::class);
        $search = $adapter->searchBySku('JTC-1339', 'JTC');
        $data = $adapter->fetchProductPage($search);

        $this->assertTrue($search->found);
        $this->assertSame(['https://eng.jtc.com.tw/upload/product/JTC-1339.jpg'], $data->images);
    }

    public function test_hoegert_official_search_uses_product_page_before_direct_image(): void
    {
        config()->set('product_parser.tristools_fallback_enabled', true);
        config()->set('product_parser.tristools.enabled', true);
        $fallback = Mockery::mock(TrisToolsEnrichmentService::class);
        $fallback->shouldReceive('enrich')->once()->with('HT1A764', 'Hoegert')->andReturn([
            'found' => false,
            'confidence' => 0,
        ]);
        $this->app->instance(TrisToolsEnrichmentService::class, $fallback);

        Http::preventStrayRequests();
        Http::fake([
            'https://ru.hoegert.com/wp-json/wp/v2/search?search=HT1A764&per_page=6&subtype=product' => Http::response([[
                'title' => 'HOEGERT Фонарь светодиодный',
                'url' => 'https://ru.hoegert.com/produkt/hoegert-fonar/',
                'subtype' => 'product',
                '_links' => ['self' => [['href' => 'https://ru.hoegert.com/wp-json/wp/v2/product/123']]],
            ]]),
            'https://ru.hoegert.com/produkt/hoegert-fonar/' => Http::response(
                '<html><head><meta property="og:image" content="https://ru.hoegert.com/wp-content/uploads/2021/09/HT1A764_pack.png"></head><body>HT1A764</body></html>'
            ),
            'https://ru.hoegert.com/wp-json/wp/v2/product/123' => Http::response([
                'title' => ['rendered' => 'HOEGERT Фонарь светодиодный'],
                'excerpt' => ['rendered' => '<p>Артикул: HT1A764 Легкий светодиодный фонарь для мастерской с прочным корпусом и несколькими режимами работы.</p>'],
                'content' => ['rendered' => '<table><tr><td>Световой поток [lm]</td><td>3100</td></tr></table>'],
            ]),
        ]);

        $result = app(ProductSearchService::class)->searchForParser('HT1A764', 'Hoegert', preferLocal: false);

        $this->assertTrue($result['found']);
        $this->assertSame('https://ru.hoegert.com/wp-content/uploads/2021/09/HT1A764_pack.png', $result['images'][0]);
        $this->assertSame('https://ru.hoegert.com/produkt/hoegert-fonar/', $result['official_source_url']);
        $this->assertStringContainsString('светодиодный фонарь', $result['description']);
    }

    public function test_fallback_is_called_when_no_official_adapter_supports_brand(): void
    {
        config()->set('product_parser.tristools_fallback_enabled', true);
        $fallback = Mockery::mock(TrisToolsEnrichmentService::class);
        $fallback->shouldReceive('enrich')->times(3)->andReturn([
            'found' => true,
            'title' => 'Fallback product',
            'description' => 'Fallback description',
            'specs' => [],
            'images' => ['https://tristool.md/image.jpg'],
            'source_urls' => ['https://tristool.md/product/1'],
            'confidence' => 80,
        ]);
        $this->app->instance(TrisToolsEnrichmentService::class, $fallback);

        $result = app(ProductSearchService::class)->searchForParser('ABC-1', 'Unsupported Brand', preferLocal: false);

        $this->assertTrue($result['found']);
        $this->assertFalse($result['fallback_source_used']);
        $this->assertTrue($result['needs_source_review']);
        $this->assertSame('tristools_primary', $result['content_source_type']);
    }

    public function test_automatic_recovery_retries_transient_source_failures_before_manual_review(): void
    {
        config()->set('product_parser.automation_recovery_attempts', 3);
        config()->set('product_parser.automation_recovery_delay_ms', 0);
        config()->set('product_parser.official_sources_enabled', false);
        $tristools = Mockery::mock(TrisToolsEnrichmentService::class);
        $tristools->shouldReceive('enrich')->times(3)->andReturn(
            ['found' => false, 'confidence' => 0],
            ['found' => false, 'confidence' => 0],
            [
                'found' => true,
                'title' => 'Recovered product',
                'description' => 'Recovered product description.',
                'title_ru' => 'Восстановленный товар',
                'title_ro' => 'Produs recuperat',
                'description_ru' => 'Описание восстановленного товара.',
                'description_ro' => 'Descrierea produsului recuperat.',
                'breadcrumb' => ['Tools'],
                'specs' => [],
                'images' => ['https://tristool.md/uploaded_files/REC-1.jpg'],
                'source_urls' => ['https://tristool.md/ru/product/REC-1'],
                'confidence' => 98,
            ],
        );
        $this->app->instance(TrisToolsEnrichmentService::class, $tristools);

        $result = app(ProductSearchService::class)->searchForParser('REC-1', 'Unknown', preferLocal: false);

        $this->assertTrue($result['found']);
        $this->assertSame(3, $result['automation_attempts']);
        $this->assertFalse($result['automation_exhausted']);
        $this->assertFalse($result['needs_source_review']);
    }
}
