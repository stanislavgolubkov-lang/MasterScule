<?php

namespace App\Services\ProductSources\Adapters;

use App\Services\ProductSources\ProductSourceProductData;
use App\Services\ProductSources\ProductSourceSearchResult;
use Illuminate\Support\Str;
use Throwable;

class MightySevenOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['M7'];
    }

    protected function searchUrls(string $sku): array
    {
        return ['https://www.mighty-seven.com/search_page?key='.rawurlencode($sku)];
    }

    public function searchBySku(string $sku, string $brand, ?string $name = null): ProductSourceSearchResult
    {
        try {
            $response = $this->request()->asForm()->post('https://www.mighty-seven.com/api_v1/getprodut_list_search', [
                'key' => $sku, 'type1' => '', 'type2' => '', 'type3' => '', 'type4' => '',
            ]);
        } catch (Throwable) {
            return ProductSourceSearchResult::notFound($sku, $brand);
        }

        $payload = $response->json();
        $html = is_array($payload) ? (string) ($payload['data'] ?? '') : '';
        preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>([\s\S]*?)<\/a>/iu', $html, $matches, PREG_SET_ORDER);
        $needle = $this->normalizeSku($sku);
        foreach ($matches as $match) {
            $cardHtml = (string) ($match[2] ?? '');
            preg_match('/<h3\b[^>]*>([\s\S]*?)<\/h3>/iu', $cardHtml, $titleMatch);
            preg_match('/<p\b[^>]*>([\s\S]*?)<\/p>/iu', $cardHtml, $skuMatch);
            preg_match('/<div\b[^>]*class=["\'][^"\']*\bpic\b[^"\']*["\'][^>]*>[\s\S]*?<img\b[^>]*src=["\']([^"\']+)["\']/iu', $cardHtml, $imageMatch);
            if (! isset($imageMatch[1])) {
                preg_match('/<img\b[^>]*src=["\']([^"\']+)["\']/iu', $cardHtml, $imageMatch);
            }

            $candidateSku = preg_replace('/\s*\[[^\]]+\]\s*$/u', '', strip_tags($skuMatch[1] ?? ''));
            if (! $this->skuMatches((string) $candidateSku, $needle)) {
                continue;
            }
            $url = $this->absoluteUrl('https://www.mighty-seven.com', $match[1]);

            return new ProductSourceSearchResult(true, $sku, $brand, $url, 'www.mighty-seven.com', trim(strip_tags($titleMatch[1] ?? '')), true, priority: 100, payload: [
                'api_image' => $this->absoluteUrl('https://www.mighty-seven.com', $imageMatch[1] ?? ''),
            ]);
        }

        return ProductSourceSearchResult::notFound($sku, $brand);
    }

    private function skuMatches(string $candidateSku, string $needle): bool
    {
        $candidate = $this->normalizeSku($candidateSku);
        if ($candidate === $needle || $candidate === $needle.'P') {
            return true;
        }

        $parts = preg_split('/\//', Str::upper($candidateSku)) ?: [];
        if (count($parts) < 2) {
            return false;
        }

        $firstPart = trim((string) array_shift($parts));
        $first = $this->normalizeSku($firstPart);
        if ($first === $needle) {
            return true;
        }

        $prefix = preg_replace('/[A-Z]$/', '', $firstPart) ?: '';

        return collect($parts)->contains(
            fn (string $part) => $this->normalizeSku($prefix.$part) === $needle
        );
    }

    public function extractImages(ProductSourceProductData $data): array
    {
        return collect([
            $data->search->payload['api_image'] ?? null,
            ...parent::extractImages($data),
        ])->filter(function (?string $url) {
            $url = Str::lower((string) $url);

            return $url !== ''
                && Str::contains($url, '/upload/product/')
                && ! Str::contains($url, ['lightning.svg', '/productcategory/', 'logo', 'icon'])
                && ! Str::endsWith($url, '.svg');
        })->unique()->values()->all();
    }
}
