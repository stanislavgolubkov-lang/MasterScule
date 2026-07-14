<?php

namespace App\Services\ProductSources;

use App\Services\ProductParserSettings;
use App\Services\ProductSources\Adapters\HoegertOfficialAdapter;
use App\Services\ProductSources\Adapters\JtcOfficialAdapter;
use App\Services\ProductSources\Adapters\KingTonyOfficialAdapter;
use App\Services\ProductSources\Adapters\MightySevenOfficialAdapter;
use App\Services\ProductSources\Adapters\TongrunOfficialAdapter;
use App\Services\ProductSources\Adapters\TorinOfficialAdapter;
use App\Services\ProductSources\Adapters\TrisToolsFallbackAdapter;

class ProductSourceDiscoveryService
{
    public function __construct(
        private readonly ProductParserSettings $settings,
        private readonly SourceMatchConfidenceCalculator $confidence,
        private readonly KingTonyOfficialAdapter $kingTony,
        private readonly MightySevenOfficialAdapter $mightySeven,
        private readonly JtcOfficialAdapter $jtc,
        private readonly HoegertOfficialAdapter $hoegert,
        private readonly TorinOfficialAdapter $torin,
        private readonly TongrunOfficialAdapter $tongrun,
        private readonly ReviewedCatalogSourceService $reviewedCatalog,
        private readonly TrisToolsFallbackAdapter $fallback,
    ) {}

    public function search(string $sku, ?string $brand, ?string $name = null, bool $forceFallback = false, bool $allowFallback = true): array
    {
        $brand = trim((string) $brand);
        $official = null;

        if (! $forceFallback && $this->settings->get('official_sources_enabled', true)) {
            foreach ($this->officialAdapters() as $adapter) {
                if (! $adapter->supportsBrand($brand)) {
                    continue;
                }
                $search = $adapter->searchBySku($sku, $brand, $name);
                if (! $search->found) {
                    continue;
                }
                $data = $adapter->fetchProductPage($search);
                $score = $this->confidence->calculate($data);
                if ($score >= 70) {
                    $official = $this->result($data, $score, false);
                    break;
                }
            }
        }

        $officialComplete = $official
            && $official['confidence'] >= (int) $this->settings->get('min_official_confidence', 90)
            && $official['images'] !== []
            && filled($official['description']);
        if ($officialComplete || ! $allowFallback) {
            return $official ?: $this->emptyResult();
        }

        if (! $forceFallback && ($catalog = $this->reviewedCatalog->find($sku, $brand, $name))) {
            return $this->mergeReviewedCatalog($official, $catalog);
        }

        if (! $this->fallbackEnabled()) {
            return $official ?: $this->emptyResult();
        }

        $fallbackSearch = $this->fallback->searchBySku($sku, $brand, $name);
        if (! $fallbackSearch->found) {
            return $official ?: $this->emptyResult();
        }
        $fallback = $this->result($this->fallback->fetchProductPage($fallbackSearch), (int) ($fallbackSearch->payload['confidence'] ?? 80), true);
        if (! $official) {
            return $fallback;
        }

        $official['images'] = $official['images'] ?: $fallback['images'];
        $official['description'] = $official['description'] ?: $fallback['description'];
        $official['specs'] = $official['specs'] ?: $fallback['specs'];
        $official['sources'] = array_merge($official['sources'], $fallback['sources']);
        $official['source_urls'] = array_values(array_unique(array_merge($official['source_urls'], $fallback['source_urls'])));
        $official['fallback_source_used'] = true;
        $official['fallback_source_url'] = $fallback['fallback_source_url'];
        $official['fallback_source_domain'] = $fallback['fallback_source_domain'];
        $official['needs_source_review'] = true;

        return $official;
    }

    private function result(ProductSourceProductData $data, int $score, bool $fallback): array
    {
        $source = $data->search;
        $url = $source->url;
        $domain = $source->domain;

        return [
            'found' => true,
            'title' => $data->title ?: $source->title,
            'description' => $data->description,
            'specs' => $data->specifications,
            'images' => array_values(array_unique(array_filter($data->images))),
            'sources' => [[
                'url' => $url,
                'domain' => $domain,
                'title' => $data->title ?: $source->title,
                'snippet' => $fallback ? 'Fallback reference source.' : 'Official manufacturer source.',
                'source_type' => $source->sourceType,
                'confidence_score' => $score,
                'raw_data_json' => ['sku' => $source->sku, 'brand' => $source->brand, 'breadcrumb' => $data->breadcrumb],
            ]],
            'source_urls' => array_values(array_filter([$url, ...$data->images])),
            'confidence' => $score,
            'source_match_confidence' => $score,
            'official_source_url' => $fallback ? null : $url,
            'official_source_domain' => $fallback ? null : $domain,
            'official_source_confidence' => $fallback ? null : $score,
            'fallback_source_url' => $fallback ? $url : null,
            'fallback_source_domain' => $fallback ? $domain : null,
            'fallback_source_used' => $fallback,
            'needs_source_review' => $fallback || $score < (int) $this->settings->get('min_official_confidence', 90),
            'content_source_type' => $source->sourceType,
            'image_source_type' => $source->sourceType,
            'translation_source_type' => 'generated_pending_review',
            'existing_product_id' => null,
            'category_id' => null,
            'warnings' => $fallback ? ['Fallback source used. Manual source review is required.'] : [],
        ];
    }

    private function officialAdapters(): array
    {
        return [$this->kingTony, $this->mightySeven, $this->jtc, $this->hoegert, $this->torin, $this->tongrun];
    }

    private function mergeReviewedCatalog(?array $official, array $catalog): array
    {
        if (! $official) {
            return $catalog;
        }

        $official['images'] = array_values(array_unique(array_merge($official['images'], $catalog['images'])));
        $official['sources'] = array_merge($official['sources'], $catalog['sources']);
        $official['source_urls'] = array_values(array_unique(array_merge($official['source_urls'], $catalog['source_urls'])));
        $official['confidence'] = max((int) $official['confidence'], (int) $catalog['confidence']);
        $official['source_match_confidence'] = $official['confidence'];
        $official['official_source_url'] = $official['official_source_url'] ?: $catalog['official_source_url'];
        $official['official_source_domain'] = $official['official_source_domain'] ?: $catalog['official_source_domain'];
        $official['official_source_confidence'] = max(
            (int) ($official['official_source_confidence'] ?? 0),
            (int) $catalog['official_source_confidence'],
        );
        $official['needs_source_review'] = false;
        $official['image_source_type'] = 'official_manufacturer_catalog';

        return $official;
    }

    private function fallbackEnabled(): bool
    {
        return (bool) $this->settings->get('tristools_fallback_enabled', $this->settings->get('tristools.enabled', false));
    }

    private function emptyResult(): array
    {
        return [
            'found' => false, 'title' => null, 'description' => null, 'specs' => [], 'images' => [],
            'sources' => [], 'source_urls' => [], 'confidence' => 0, 'source_match_confidence' => 0,
            'fallback_source_used' => false, 'needs_source_review' => true, 'warnings' => [],
            'existing_product_id' => null, 'category_id' => null,
        ];
    }
}
