<?php

namespace App\Services\ProductSources\Adapters;

use App\Services\ProductSources\ProductSourceSearchResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;

class KingTonyOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['KING_TONY'];
    }

    protected function searchUrls(string $sku): array
    {
        return ['https://www.kingtony.com/products_search.php?keywords='.rawurlencode($sku)];
    }

    protected function request(): PendingRequest
    {
        return parent::request()
            ->timeout(30)
            ->retry(2, 500);
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
                $candidate = $this->absoluteUrl(
                    $url,
                    html_entity_decode((string) ($link[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                );
                $text = trim(strip_tags((string) ($link[2] ?? '')));

                if (! $this->isProductPageUrl($candidate) || ! $this->matchesSkuOrFamily($candidate.' '.$text, $needle)) {
                    continue;
                }

                return new ProductSourceSearchResult(
                    true,
                    $sku,
                    $brand,
                    $candidate,
                    (string) parse_url($candidate, PHP_URL_HOST),
                    $text,
                    true,
                    priority: 100,
                );
            }
        }

        return ProductSourceSearchResult::notFound($sku, $brand);
    }

    private function isProductPageUrl(string $url): bool
    {
        $path = Str::lower((string) parse_url($url, PHP_URL_PATH));

        return Str::contains($path, ['/product/', '/productlist/', '/product_detail.php']);
    }

    private function matchesSkuOrFamily(string $value, string $needle): bool
    {
        if ($needle === '') {
            return false;
        }

        preg_match_all('/[A-Z0-9][A-Z0-9-]{2,}/iu', Str::upper(Str::ascii($value)), $matches);

        return collect($matches[0] ?? [])
            ->map(fn (string $token) => $this->normalizeSku($token))
            ->filter(fn (string $token) => strlen($token) >= 4)
            ->contains(fn (string $token) => $token === $needle || str_starts_with($needle, $token));
    }
}
