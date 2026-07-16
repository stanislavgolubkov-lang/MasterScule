<?php

namespace Tests\Feature;

use App\Models\ProductParserBatch;
use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use App\Services\ProductImageCollectorService;
use App\Services\ProductParserItemPreparationService;
use App\Services\ProductParserService;
use App\Services\ProductSearchService;
use App\Services\ProductSources\Adapters\KingTonyOfficialAdapter;
use App\Services\ProductSources\ProductSourceDiscoveryService;
use App\Services\ProductSources\ReviewedCatalogSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProductParserBasePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewed_catalog_requires_an_exact_sku_and_matching_brand(): void
    {
        $catalog = app(ReviewedCatalogSourceService::class);

        $result = $catalog->find('QB-9434W', 'M7 / Mighty Seven', 'Test product');

        $this->assertNotNull($result);
        $this->assertSame(['/images/parser-catalog/m7/qb-9434w.png'], $result['images']);
        $this->assertSame(95, $result['official_source_confidence']);
        $this->assertFalse($result['needs_source_review']);
        $this->assertNull($catalog->find('QB-9434W', 'King Tony'));
        $this->assertNull($catalog->find('QB-0000', 'M7'));
    }

    public function test_preparation_uses_the_next_candidate_when_the_first_image_is_broken(): void
    {
        Storage::fake('public');

        $batch = ProductParserBatch::create([
            'title' => 'Parser test',
            'source_type' => 'single',
            'status' => 'running',
        ]);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'QB-9434W',
            'brand' => 'M7',
            'status' => 'ready_for_review',
            'official_source_url' => 'https://www.mighty-seven.com/product/qb-9434w',
            'official_source_domain' => 'mighty-seven.com',
            'official_source_confidence' => 95,
            'source_match_confidence' => 95,
            'needs_source_review' => true,
            'needs_image_review' => true,
        ]);

        $broken = ProductParserImageAsset::create([
            'parser_item_id' => $item->id,
            'source_url' => '/images/parser-catalog/m7/missing.png',
            'source_domain' => 'mighty-seven.com',
            'status' => 'found',
            'is_selected' => true,
            'is_main' => true,
            'needs_review' => true,
        ]);
        $valid = ProductParserImageAsset::create([
            'parser_item_id' => $item->id,
            'source_url' => '/images/parser-catalog/m7/qb-9434w.png',
            'source_domain' => 'mighty-seven.com',
            'status' => 'found',
            'is_selected' => false,
            'is_main' => false,
            'needs_review' => true,
        ]);

        $prepared = app(ProductParserItemPreparationService::class)->prepare($item, true);

        $this->assertTrue($prepared);
        $this->assertSame('failed', $broken->fresh()->status);
        $this->assertFalse($broken->fresh()->is_selected);
        $this->assertSame('processed', $valid->fresh()->status);
        $this->assertTrue($valid->fresh()->is_selected);
        $this->assertTrue($valid->fresh()->is_main);
        $this->assertFalse($valid->fresh()->needs_review);
        $this->assertFalse($item->fresh()->needs_image_review);
        $this->assertFalse($item->fresh()->needs_source_review);
        $this->assertNotNull($item->fresh()->image_reviewed_at);
        $this->assertCount(1, $item->fresh()->processed_images_json);
    }

    public function test_source_discovery_uses_reviewed_catalog_before_external_fallback(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $result = app(ProductSourceDiscoveryService::class)
            ->search('QB-9434W', 'M7 / Mighty Seven', 'Test product');

        $this->assertTrue($result['found']);
        $this->assertFalse($result['fallback_source_used']);
        $this->assertSame('official_manufacturer_catalog', $result['image_source_type']);
        $this->assertSame(['/images/parser-catalog/m7/qb-9434w.png'], $result['images']);
    }

    public function test_collector_rejects_jtc_logo_candidates_without_sku(): void
    {
        $batch = ProductParserBatch::create([
            'title' => 'JTC logo guard',
            'source_type' => 'single',
            'status' => 'running',
        ]);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'JTC-1339',
            'brand' => 'JTC',
            'status' => 'ready_for_review',
        ]);

        app(ProductImageCollectorService::class)->collect($item, [
            'https://jtcautotools.com/cdn/shop/files/2024-05-22_170521.png?v=1731917284',
            'https://jtcautotools.com/cdn/shop/files/JTC-1339.jpg?v=1731917284',
        ]);

        $this->assertSame(
            ['https://jtcautotools.com/cdn/shop/files/JTC-1339.jpg?v=1731917284'],
            $item->imageAssets()->pluck('source_url')->all(),
        );
    }

    public function test_collector_rejects_king_tony_brand_and_messenger_images(): void
    {
        $batch = ProductParserBatch::create([
            'title' => 'King Tony image guard',
            'source_type' => 'single',
            'status' => 'running',
        ]);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => '302D10',
            'brand' => 'King Tony',
            'status' => 'ready_for_review',
        ]);

        app(ProductImageCollectorService::class)->collect($item, [
            'https://www.kingtony.com/tw/img/KINGTONY.png',
            'https://www.kingtony.com/images/fb-messenger.png',
            'https://www.kingtony.com/upload/products/302D.png',
        ]);

        $this->assertSame(
            ['https://www.kingtony.com/upload/products/302D.png'],
            $item->imageAssets()->pluck('source_url')->all(),
        );
    }

    public function test_king_tony_search_ignores_language_links_and_matches_product_family(): void
    {
        Http::fake([
            'https://www.kingtony.com/products_search.php*' => Http::response(<<<'HTML'
                <a href="https://www.kingtony.com/tw/index.php?url=%2Fproducts_search.php%3Fkeywords%3D302D10">Language</a>
                <a href="https://www.kingtony.com/product/Torx-Plus-Bit-Socket-302D">
                    <h3>302D <span>3/8 DR. Torx Plus Bit Socket</span></h3>
                </a>
                HTML),
            'https://www.kingtony.com/product/Torx-Plus-Bit-Socket-302D' => Http::response(<<<'HTML'
                <meta property="og:title" content="3/8 DR. Torx Plus Bit Socket">
                <img src="https://www.kingtony.com/tw/img/KINGTONY.png">
                <img src="https://www.kingtony.com/upload/products/302D.png">
                HTML),
        ]);

        $adapter = app(KingTonyOfficialAdapter::class);
        $search = $adapter->searchBySku('302D10', 'King Tony');
        $data = $adapter->fetchProductPage($search);

        $this->assertTrue($search->found);
        $this->assertSame(
            'https://www.kingtony.com/product/Torx-Plus-Bit-Socket-302D',
            $search->url,
        );
        $this->assertContains('https://www.kingtony.com/upload/products/302D.png', $data->images);
    }

    public function test_preparation_without_images_stays_reviewable_instead_of_failed(): void
    {
        $batch = ProductParserBatch::create([
            'title' => 'No image test',
            'source_type' => 'single',
            'status' => 'running',
        ]);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'NO-IMAGE-1',
            'brand' => 'King Tony',
            'status' => 'searching',
            'needs_category_review' => false,
            'needs_image_review' => true,
            'error_message' => __('ui.parser_images_failed'),
        ]);

        $prepared = app(ProductParserItemPreparationService::class)->prepare($item, true);

        $this->assertFalse($prepared);
        $this->assertSame('ready_for_review', $item->fresh()->status);
        $this->assertTrue($item->fresh()->needs_image_review);
        $this->assertNull($item->fresh()->error_message);
    }

    public function test_exact_fallback_above_threshold_is_approved_after_image_processing(): void
    {
        Storage::fake('public');
        config()->set('product_parser.auto_approve_exact_fallback', true);

        $batch = ProductParserBatch::create([
            'title' => 'Fallback test',
            'source_type' => 'single',
            'status' => 'running',
        ]);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'QB-9434W',
            'brand' => 'M7',
            'status' => 'ready_for_review',
            'fallback_source_url' => 'https://tristool.md/product/qb-9434w',
            'fallback_source_domain' => 'tristool.md',
            'fallback_source_used' => true,
            'source_match_confidence' => 84,
            'needs_source_review' => true,
            'needs_image_review' => true,
        ]);
        $asset = ProductParserImageAsset::create([
            'parser_item_id' => $item->id,
            'source_url' => '/images/parser-catalog/m7/qb-9434w.png',
            'source_domain' => 'tristool.md',
            'status' => 'found',
            'is_selected' => true,
            'is_main' => true,
            'needs_review' => true,
        ]);

        $prepared = app(ProductParserItemPreparationService::class)->prepare($item, true);

        $this->assertTrue($prepared);
        $this->assertFalse($item->fresh()->needs_source_review);
        $this->assertFalse($item->fresh()->needs_image_review);
        $this->assertNotNull($item->fresh()->source_reviewed_at);
        $this->assertFalse($asset->fresh()->needs_review);
    }

    public function test_manual_review_is_used_only_after_automatic_recovery_is_exhausted(): void
    {
        $batch = ProductParserBatch::create([
            'title' => 'Exhausted automation',
            'source_type' => 'single',
            'status' => 'pending',
        ]);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'NO-SOURCE-999',
            'brand' => 'Unknown',
            'status' => 'queued',
        ]);
        $search = Mockery::mock(ProductSearchService::class);
        $search->shouldReceive('searchForParser')->once()->andReturn([
            'found' => false,
            'title' => null,
            'description' => null,
            'specs' => [],
            'images' => [],
            'sources' => [],
            'source_urls' => [],
            'confidence' => 0,
            'source_match_confidence' => 0,
            'needs_source_review' => true,
            'warnings' => [],
            'automation_attempts' => 3,
            'automation_exhausted' => true,
        ]);
        $this->app->instance(ProductSearchService::class, $search);

        app(ProductParserService::class)->parseItem($item);

        $item->refresh();
        $this->assertSame('needs_manual_review', $item->status);
        $this->assertTrue((bool) $item->needs_source_review);
        $this->assertTrue((bool) $item->needs_image_review);
        $this->assertSame(3, $item->found_specs_json['_automation_attempts']);
        $this->assertTrue($item->found_specs_json['_automation_exhausted']);
        $this->assertStringContainsString('recovery attempts were exhausted', $item->error_message);
    }
}
