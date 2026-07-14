<?php

namespace Tests\Feature;

use App\Services\ProductSearchService;
use App\Services\ProductSources\ProductSourceRegistry;
use App\Services\TrisToolsEnrichmentService;
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

    public function test_exact_official_result_does_not_call_fallback(): void
    {
        config()->set('product_parser.tristools_fallback_enabled', true);
        config()->set('product_parser.tristools.enabled', true);
        $fallback = Mockery::mock(TrisToolsEnrichmentService::class);
        $fallback->shouldNotReceive('enrich');
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

    public function test_forced_fallback_is_always_marked_for_review(): void
    {
        config()->set('product_parser.tristools_fallback_enabled', true);
        $fallback = Mockery::mock(TrisToolsEnrichmentService::class);
        $fallback->shouldReceive('enrich')->once()->andReturn([
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

        $this->assertTrue($result['fallback_source_used']);
        $this->assertTrue($result['needs_source_review']);
        $this->assertSame('fallback_reference', $result['content_source_type']);
    }

    public function test_fallback_is_called_when_no_official_adapter_supports_brand(): void
    {
        config()->set('product_parser.tristools_fallback_enabled', true);
        $fallback = Mockery::mock(TrisToolsEnrichmentService::class);
        $fallback->shouldReceive('enrich')->once()->andReturn([
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
        $this->assertTrue($result['fallback_source_used']);
        $this->assertTrue($result['needs_source_review']);
    }
}
