<?php

namespace App\Services\ProductSources\Adapters;

use App\Services\ProductSources\ProductSourceProductData;
use App\Services\ProductSources\ProductSourceSearchResult;
use Illuminate\Support\Str;

class JtcOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['JTC'];
    }

    protected function searchUrls(string $sku): array
    {
        return [
            'https://eng.jtc.com.tw/product/index.php?keywords='.rawurlencode($sku).'&mode=search',
            'https://www.jtcautotools.com/search?q='.rawurlencode($sku),
        ];
    }

    public function searchBySku(string $sku, string $brand, ?string $name = null): ProductSourceSearchResult
    {
        $sku = trim($sku);
        $needle = $this->normalizeSku($sku);

        foreach ($this->searchUrls($sku) as $url) {
            $html = $this->get($url, $brand);
            if (! $html) {
                continue;
            }

            preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>([\s\S]*?)<\/a>/iu', $html, $links, PREG_SET_ORDER);
            foreach ($links as $link) {
                $candidate = html_entity_decode((string) ($link[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = trim(strip_tags((string) ($link[2] ?? '')));
                $absolute = $this->absoluteUrl($url, $candidate);
                $domain = (string) parse_url($absolute, PHP_URL_HOST);

                if ($needle === ''
                    || $this->isListingUrl($absolute)
                    || ! $this->registry->isOfficialDomain($domain, $brand)
                    || ! Str::contains($this->normalizeSku($candidate.' '.$text), $needle)) {
                    continue;
                }

                return new ProductSourceSearchResult(true, $sku, $brand, $absolute, $domain, $text, true, priority: 100);
            }
        }

        return ProductSourceSearchResult::notFound($sku, $brand);
    }

    public function extractImages(ProductSourceProductData $data): array
    {
        $needle = $this->normalizeSku($data->search->sku);
        $skuCore = preg_replace('/^JTC/', '', $needle) ?: $needle;

        return collect(parent::extractImages($data))
            ->filter(function (string $url) use ($needle, $skuCore) {
                $lower = Str::lower($url);
                $normalized = $this->normalizeSku($url);

                if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
                    return false;
                }

                if (Str::contains($lower, [
                    'logo', 'brand', 'favicon', 'icon', 'banner', 'collection', 'category',
                    'avatar', 'social', 'facebook', 'instagram', 'youtube', 'placeholder',
                    'no-image', 'no_image', 'no-pic', 'no_pic',
                ])) {
                    return false;
                }

                return $needle !== '' && (
                    Str::contains($normalized, $needle)
                    || (strlen($skuCore) >= 3 && Str::contains($normalized, $skuCore))
                );
            })
            ->unique()
            ->take(4)
            ->values()
            ->all();
    }

    private function isListingUrl(string $url): bool
    {
        $path = Str::lower((string) parse_url($url, PHP_URL_PATH));
        $query = Str::lower((string) parse_url($url, PHP_URL_QUERY));

        return in_array($path, ['', '/', '/search', '/product/index.php'], true)
            || Str::contains($path, ['/search/', '/collections/', '/collection/'])
            || Str::contains($query, ['mode=search', 'q=', 'keywords=']);
    }
}
