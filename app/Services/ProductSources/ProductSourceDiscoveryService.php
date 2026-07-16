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
        $attempts = max(1, min(5, (int) $this->settings->get('automation_recovery_attempts', 3)));
        $delayMs = max(0, min(2000, (int) $this->settings->get('automation_recovery_delay_ms', 250)));
        $best = null;
        $usedAttempts = 0;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $usedAttempts = $attempt;
            $current = $this->searchOnce($sku, $brand, $name, $forceFallback, $allowFallback);
            $best = $this->mergeRecoveryAttempt($best, $current);

            if ($this->automaticallyComplete($best)) {
                break;
            }

            if ($attempt < $attempts && $delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        $best ??= $this->emptyResult();
        $best['automation_attempts'] = $usedAttempts;
        $best['automation_exhausted'] = ! $this->automaticallyComplete($best);

        return $best;
    }

    public function searchTrisTool(string $sku, ?string $brand, ?string $name = null): array
    {
        $result = $this->searchOnce($sku, $brand, $name, true, true);
        $result['automation_attempts'] = 1;
        $result['automation_exhausted'] = ! $this->automaticallyComplete($result);

        return $result;
    }

    private function searchOnce(string $sku, ?string $brand, ?string $name, bool $forceFallback, bool $allowFallback): array
    {
        $brand = $this->knownBrand($brand, $name);

        if ($forceFallback) {
            if (! $allowFallback || ! $this->fallbackEnabled()) {
                return $this->emptyResult();
            }

            $tristools = $this->fallbackResult($sku, $brand, $name);

            return $tristools
                ? $this->mergeTrisToolsPrimary($tristools, null)
                : $this->emptyResult();
        }

        if (! $allowFallback) {
            return $this->officialResult($sku, $brand, $name) ?: $this->emptyResult();
        }

        // TrisTool is the required first stage. It supplies the local product
        // card, full-size images, RU/RO content and category breadcrumb.
        $tristools = $this->fallbackEnabled()
            ? $this->fallbackResult($sku, $brand, $name)
            : null;

        // Only after TrisTool has been checked do we consult manufacturer and
        // reviewed catalog sources for extra images, descriptions and specs.
        $official = $this->officialResult($sku, $brand, $name);
        $additional = $official;

        if ($catalog = $this->reviewedCatalog->find($sku, $brand, $name)) {
            $additional = $this->mergeReviewedCatalog($official, $catalog);
        }

        if ($tristools) {
            return $this->mergeTrisToolsPrimary($tristools, $additional);
        }

        return $additional ?: $this->emptyResult();
    }

    private function mergeRecoveryAttempt(?array $best, array $current): array
    {
        if (! $best) {
            return $current;
        }

        if (! ($best['found'] ?? false)) {
            return ($current['found'] ?? false) ? $current : $best;
        }

        if (! ($current['found'] ?? false)) {
            return $best;
        }

        $bestIsTris = ($best['content_source_type'] ?? null) === 'tristools_primary';
        $currentIsTris = ($current['content_source_type'] ?? null) === 'tristools_primary';
        $primary = $currentIsTris && ! $bestIsTris
            ? $current
            : ($bestIsTris && ! $currentIsTris
                ? $best
                : ($this->recoveryScore($current) > $this->recoveryScore($best) ? $current : $best));
        $secondary = $primary === $current ? $best : $current;

        foreach ([
            'title',
            'description',
            'title_ru',
            'title_ro',
            'description_ru',
            'description_ro',
            'official_source_url',
            'official_source_domain',
            'official_source_confidence',
        ] as $key) {
            if (! filled($primary[$key] ?? null) && filled($secondary[$key] ?? null)) {
                $primary[$key] = $secondary[$key];
            }
        }

        foreach (['breadcrumb', 'breadcrumb_ro', 'official_breadcrumb', 'package_contents'] as $key) {
            if (empty($primary[$key]) && ! empty($secondary[$key])) {
                $primary[$key] = $secondary[$key];
            }
        }

        $primary['images'] = array_values(array_unique(array_filter(array_merge(
            $primary['images'] ?? [],
            $secondary['images'] ?? [],
        ))));
        $primary['sources'] = collect(array_merge(
            $primary['sources'] ?? [],
            $secondary['sources'] ?? [],
        ))->unique(fn (array $source) => $source['url'] ?? serialize($source))->values()->all();
        $primary['source_urls'] = array_values(array_unique(array_filter(array_merge(
            $primary['source_urls'] ?? [],
            $secondary['source_urls'] ?? [],
        ))));
        $primary['specs'] = array_replace($secondary['specs'] ?? [], $primary['specs'] ?? []);
        $primary['content_variants'] = collect(array_merge(
            $primary['content_variants'] ?? [],
            $secondary['content_variants'] ?? [],
        ))->unique(fn (array $variant) => ($variant['source'] ?? '').'|'.($variant['title'] ?? '').'|'.($variant['description'] ?? ''))->values()->all();
        $primary['confidence'] = max((int) ($primary['confidence'] ?? 0), (int) ($secondary['confidence'] ?? 0));
        $primary['source_match_confidence'] = max(
            (int) ($primary['source_match_confidence'] ?? 0),
            (int) ($secondary['source_match_confidence'] ?? 0),
        );
        $primary['needs_source_review'] = (bool) ($primary['needs_source_review'] ?? true)
            && (bool) ($secondary['needs_source_review'] ?? true);
        $primary['warnings'] = array_values(array_unique(array_merge(
            $primary['warnings'] ?? [],
            $secondary['warnings'] ?? [],
        )));

        return $primary;
    }

    private function automaticallyComplete(array $result): bool
    {
        return (bool) ($result['found'] ?? false)
            && (int) ($result['source_match_confidence'] ?? $result['confidence'] ?? 0) >= 90
            && ! (bool) ($result['needs_source_review'] ?? true)
            && filled($result['title'] ?? null)
            && filled($result['description'] ?? $result['description_ru'] ?? $result['description_ro'] ?? null)
            && ! empty($result['images']);
    }

    private function recoveryScore(array $result): int
    {
        return ((int) ($result['source_match_confidence'] ?? $result['confidence'] ?? 0) * 10)
            + (filled($result['description'] ?? null) ? 100 : 0)
            + (filled($result['title'] ?? null) ? 50 : 0)
            + min(4, count($result['images'] ?? [])) * 25
            + (($result['content_source_type'] ?? null) === 'tristools_primary' ? 200 : 0);
    }

    private function knownBrand(?string $brand, ?string $name): string
    {
        $brand = trim((string) $brand);
        if ($brand !== '') {
            return $brand;
        }

        $text = mb_strtolower((string) $name, 'UTF-8');

        return match (true) {
            str_contains($text, 'king tony') => 'King Tony',
            str_contains($text, 'mighty seven'), preg_match('/(^|\W)m7(\W|$)/iu', $text) === 1 => 'M7 / Mighty Seven',
            str_contains($text, 'jtc') => 'JTC',
            str_contains($text, 'hoegert'), str_contains($text, 'högert'), str_contains($text, 'hogert') => 'Hoegert',
            str_contains($text, 'tongrun') => 'Tongrun',
            str_contains($text, 'torin'), str_contains($text, 'big red') => 'Torin',
            default => '',
        };
    }

    private function officialResult(string $sku, string $brand, ?string $name): ?array
    {
        $official = null;

        if ($this->settings->get('official_sources_enabled', true)) {
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
            'package_contents' => $data->packageContents,
            'breadcrumb' => $data->breadcrumb,
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
            'title_ru' => $data->raw['title_ru'] ?? null,
            'title_ro' => $data->raw['title_ro'] ?? null,
            'description_ru' => $data->raw['description_ru'] ?? null,
            'description_ro' => $data->raw['description_ro'] ?? null,
            'breadcrumb_ro' => $data->raw['breadcrumb_ro'] ?? [],
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

    private function fallbackResult(string $sku, string $brand, ?string $name = null): ?array
    {
        $fallbackSearch = $this->fallback->searchBySku($sku, $brand, $name);
        if (! $fallbackSearch->found) {
            return null;
        }

        return $this->result(
            $this->fallback->fetchProductPage($fallbackSearch),
            (int) ($fallbackSearch->payload['confidence'] ?? 80),
            true,
        );
    }

    private function fillMissingOfficialData(array $official, array $fallback): array
    {
        $usedForTitle = ! filled($official['title'] ?? null) && filled($fallback['title'] ?? null);
        $usedForDescription = ! filled($official['description'] ?? null) && filled($fallback['description'] ?? null);
        $usedForImages = empty($official['images']) && ! empty($fallback['images']);
        $usedForSpecs = empty($official['specs']) && ! empty($fallback['specs']);
        $usedForPackage = empty($official['package_contents']) && ! empty($fallback['package_contents']);
        $usedForBreadcrumb = empty($official['breadcrumb']) && ! empty($fallback['breadcrumb']);
        $fallbackUsed = $usedForTitle || $usedForDescription || $usedForImages || $usedForSpecs || $usedForPackage || $usedForBreadcrumb;

        $official['title'] = $official['title'] ?: ($fallback['title'] ?? null);
        $official['description'] = $official['description'] ?: ($fallback['description'] ?? null);
        $official['images'] = $official['images'] ?: ($fallback['images'] ?? []);
        $official['specs'] = $official['specs'] ?: ($fallback['specs'] ?? []);
        $official['package_contents'] = $official['package_contents'] ?: ($fallback['package_contents'] ?? []);
        $official['breadcrumb'] = $official['breadcrumb'] ?: ($fallback['breadcrumb'] ?? []);
        $official['sources'] = array_merge($official['sources'], $fallback['sources']);
        $official['source_urls'] = array_values(array_unique(array_merge($official['source_urls'], $fallback['source_urls'])));
        $official['fallback_source_used'] = $fallbackUsed;

        if (! $fallbackUsed) {
            return $official;
        }

        $official['fallback_source_url'] = $fallback['fallback_source_url'];
        $official['fallback_source_domain'] = $fallback['fallback_source_domain'];
        $official['needs_source_review'] = true;
        $official['warnings'] = array_values(array_unique(array_merge(
            $official['warnings'] ?? [],
            ['Fallback source filled data missing from the official source. Manual review is required.'],
        )));

        if ($usedForImages) {
            $official['image_source_type'] = 'fallback_reference';
        }
        if ($usedForTitle || $usedForDescription || $usedForSpecs || $usedForPackage || $usedForBreadcrumb) {
            $official['content_source_type'] = 'fallback_reference';
        }

        return $official;
    }

    private function mergeTrisToolsPrimary(array $tristools, ?array $additional): array
    {
        if (! $additional) {
            $tristools['fallback_source_used'] = false;
            $tristools['fallback_source_url'] = null;
            $tristools['fallback_source_domain'] = null;
            $tristools['needs_source_review'] = (int) ($tristools['confidence'] ?? 0) < 90;
            $tristools['content_source_type'] = 'tristools_primary';
            $tristools['image_source_type'] = 'tristools_primary';
            $tristools['translation_source_type'] = filled($tristools['description_ro'] ?? null)
                ? 'source_bilingual'
                : 'translation_pending';

            return $tristools;
        }

        $tristools['official_breadcrumb'] = $additional['breadcrumb'] ?? [];
        $tristools['content_variants'] = array_values(array_filter([
            [
                'source' => 'tristools',
                'title' => $tristools['title'] ?? null,
                'description' => $tristools['description'] ?? null,
                'title_ru' => $tristools['title_ru'] ?? null,
                'title_ro' => $tristools['title_ro'] ?? null,
                'description_ru' => $tristools['description_ru'] ?? null,
                'description_ro' => $tristools['description_ro'] ?? null,
            ],
            [
                'source' => 'official',
                'title' => $additional['title'] ?? null,
                'description' => $additional['description'] ?? null,
            ],
        ], fn (array $variant) => filled($variant['title'] ?? null) || filled($variant['description'] ?? null)));

        $tristools['title'] = $tristools['title'] ?: ($additional['title'] ?? null);
        $tristools['description'] = $tristools['description'] ?: ($additional['description'] ?? null);
        $tristools['images'] = array_values(array_unique(array_filter(array_merge(
            $tristools['images'] ?? [],
            $additional['images'] ?? [],
        ))));
        $tristools['specs'] = array_replace($additional['specs'] ?? [], $tristools['specs'] ?? []);
        $tristools['package_contents'] = ($tristools['package_contents'] ?? [])
            ?: ($additional['package_contents'] ?? []);
        $tristools['breadcrumb'] = ($tristools['breadcrumb'] ?? [])
            ?: ($additional['breadcrumb'] ?? []);
        $tristools['sources'] = array_merge($tristools['sources'] ?? [], $additional['sources'] ?? []);
        $tristools['source_urls'] = array_values(array_unique(array_filter(array_merge(
            $tristools['source_urls'] ?? [],
            $additional['source_urls'] ?? [],
            $tristools['images'],
        ))));
        $tristools['confidence'] = max((int) ($tristools['confidence'] ?? 0), (int) ($additional['confidence'] ?? 0));
        $tristools['source_match_confidence'] = $tristools['confidence'];
        $tristools['official_source_url'] = $additional['official_source_url'] ?? null;
        $tristools['official_source_domain'] = $additional['official_source_domain'] ?? null;
        $tristools['official_source_confidence'] = $additional['official_source_confidence'] ?? null;
        $tristools['fallback_source_url'] = null;
        $tristools['fallback_source_domain'] = null;
        $tristools['fallback_source_used'] = false;
        $tristools['needs_source_review'] = (int) ($tristools['confidence'] ?? 0) < 90;
        $tristools['content_source_type'] = filled($tristools['description_ru'] ?? $tristools['description'] ?? null)
            ? 'tristools_primary'
            : ($additional['content_source_type'] ?? null);
        $tristools['image_source_type'] = ! empty($tristools['images'])
            ? 'tristools_then_official'
            : ($additional['image_source_type'] ?? null);
        $tristools['translation_source_type'] = filled($tristools['description_ro'] ?? null)
            ? 'source_bilingual'
            : 'translation_pending';
        $tristools['warnings'] = array_values(array_unique(array_merge(
            $tristools['warnings'] ?? [],
            $additional['warnings'] ?? [],
        )));

        return $tristools;
    }

    private function fallbackEnabled(): bool
    {
        return (bool) $this->settings->get('tristools_fallback_enabled', $this->settings->get('tristools.enabled', false));
    }

    private function emptyResult(): array
    {
        return [
            'found' => false, 'title' => null, 'description' => null, 'specs' => [], 'package_contents' => [], 'breadcrumb' => [], 'images' => [],
            'sources' => [], 'source_urls' => [], 'confidence' => 0, 'source_match_confidence' => 0,
            'fallback_source_used' => false, 'needs_source_review' => true, 'warnings' => [],
            'existing_product_id' => null, 'category_id' => null,
        ];
    }
}
