<?php

namespace App\Services\ProductSources\Adapters;

use App\Services\ProductSources\ProductSourceProductData;
use App\Services\ProductSources\ProductSourceSearchResult;
use Illuminate\Support\Str;

class TorinOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['TORIN'];
    }

    protected function searchUrls(string $sku): array
    {
        return [
            'https://torin.ua/ua/site_search?search_term='.rawurlencode($sku),
            'https://torinjacks.com/search?q='.rawurlencode($sku),
            'https://torin-usa.com/search?q='.rawurlencode($sku),
        ];
    }

    public function searchBySku(string $sku, string $brand, ?string $name = null): ProductSourceSearchResult
    {
        $result = parent::searchBySku($sku, $brand, $name);

        if (! $result->found || ! Str::endsWith(Str::lower((string) $result->domain), 'torin.ua')) {
            return $result;
        }

        return new ProductSourceSearchResult(
            found: true,
            sku: $result->sku,
            brand: $result->brand,
            url: $result->url,
            domain: $result->domain,
            title: $result->title,
            exactSku: $result->exactSku,
            sourceType: 'official_distributor',
            priority: 110,
            payload: $result->payload,
        );
    }

    public function extractImages(ProductSourceProductData $data): array
    {
        $domain = Str::lower((string) parse_url((string) $data->search->url, PHP_URL_HOST));
        if (! Str::endsWith($domain, 'torin.ua')) {
            return parent::extractImages($data);
        }

        $sku = $this->normalizeSku($data->search->sku);
        preg_match_all('/<img\b[^>]*>/iu', (string) $data->html, $tags);

        return collect($tags[0] ?? [])
            ->filter(function (string $tag) use ($sku) {
                if (! preg_match('/\balt=["\']([^"\']*)["\']/iu', $tag, $match)) {
                    return false;
                }

                return $sku !== '' && Str::contains($this->normalizeSku(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')), $sku);
            })
            ->map(function (string $tag) {
                if (! preg_match('/\b(?:data-original|data-large_image|data-src|src)=["\']([^"\']+)["\']/iu', $tag, $match)) {
                    return null;
                }

                $url = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $url = $this->absoluteUrl('https://torin.ua/', $url);

                return preg_replace('/_w\d+_h\d+_/i', '_', $url) ?: $url;
            })
            ->filter(fn (?string $url) => $url
                && parse_url($url, PHP_URL_SCHEME) === 'https'
                && Str::lower((string) parse_url($url, PHP_URL_HOST)) === 'images.prom.ua'
                && preg_match('/\.(?:jpe?g|png|webp)(?:\?[^#]*)?$/i', $url))
            ->unique()
            ->take(4)
            ->values()
            ->all();
    }

    protected function isCandidateProductUrl(string $url): bool
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
        $path = Str::lower((string) parse_url($url, PHP_URL_PATH));

        if (Str::endsWith($host, 'torin.ua')) {
            return preg_match('#/(?:ua/)?p\d+-[^/]+\.html$#i', $path) === 1;
        }

        if (Str::endsWith($host, ['torinjacks.com', 'torin-usa.com'])) {
            return Str::startsWith($path, '/products/');
        }

        return false;
    }
}
