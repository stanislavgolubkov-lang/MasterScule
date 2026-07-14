<?php

namespace Tests\Feature;

use App\Models\ProductParserBatch;
use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use App\Services\ProductParserItemPreparationService;
use App\Services\ProductSources\ProductSourceDiscoveryService;
use App\Services\ProductSources\ReviewedCatalogSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

    public function test_exact_fallback_above_threshold_is_approved_after_image_processing(): void
    {
        Storage::fake('public');

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
}
