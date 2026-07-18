<?php

namespace App\Services\ProductSources\Adapters;

use App\Services\ProductSources\ProductSourceSearchResult;
use App\Services\ProductSources\ProductSourceProductData;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;

class GysOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['GYS'];
    }

    protected function searchUrls(string $sku): array
    {
        $encoded = rawurlencode($sku);

        return [
            'https://maximum.md/ru/search?query='.$encoded,
            'https://www.clickoutil.com/recherche?controller=search&s='.$encoded,
            'https://www.groupe-mlv-france.fr/recherche?controller=search&s='.$encoded,
            'https://www.gys.com.ua/?s='.$encoded,
            'https://gys-ukraine.com/?s='.$encoded,
            'https://www.gysusa.com/SearchResults.asp?Search='.$encoded,
            'https://www.gysweldingusa.com/?s='.$encoded,
        ];
    }

    public function searchBySku(string $sku, string $brand, ?string $name = null): ProductSourceSearchResult
    {
        $result = parent::searchBySku($sku, $brand, $name);

        if (! $result->found) {
            return $result;
        }

        return new ProductSourceSearchResult(
            found: true,
            sku: $result->sku,
            brand: $result->brand,
            url: $result->url,
            domain: $result->domain,
            title: $result->title,
            exactSku: true,
            sourceType: 'official_distributor',
            priority: 110,
            payload: $result->payload,
        );
    }

    protected function request(): PendingRequest
    {
        return parent::request()->replaceHeaders([
            'User-Agent' => 'Mozilla/5.0',
            'Accept' => '*/*',
        ]);
    }

    public function extractImages(ProductSourceProductData $data): array
    {
        return collect(parent::extractImages($data))
            ->reject(fn (string $url) => Str::contains(Str::lower($url), [
                'star-fidelity',
                'fidelity',
                'payment',
                'badge',
                'trust',
                '/static/',
                'chevron',
                'mobile-top',
                'share_social',
                'messenger',
            ]))
            ->take(4)
            ->values()
            ->all();
    }

    public function fetchProductPage(ProductSourceSearchResult $result): ProductSourceProductData
    {
        $data = parent::fetchProductPage($result);
        $host = Str::lower((string) $result->domain);

        if (Str::endsWith($host, 'maximum.md')
            && (! $this->hasExactSkuToken(trim((string) $data->title.' '.(string) $data->description), $result->sku)
                || preg_match('/(?<![A-Z0-9])GYS(?![A-Z0-9])/iu', (string) $data->title) !== 1)) {
            return new ProductSourceProductData(
                ProductSourceSearchResult::notFound($result->sku, $result->brand),
            );
        }

        return $data;
    }

    protected function candidateMatchesSku(string $candidate, string $text, string $sku): bool
    {
        if (! $this->hasExactSkuToken($candidate.' '.$text, $sku)) {
            return false;
        }

        $host = Str::lower((string) parse_url($candidate, PHP_URL_HOST));

        return ! Str::endsWith($host, 'maximum.md')
            || preg_match('/(?<![A-Z0-9])GYS(?![A-Z0-9])/iu', $text) === 1;
    }

    private function hasExactSkuToken(string $haystack, string $sku): bool
    {
        $sku = Str::upper(Str::ascii(trim($sku)));
        $parts = array_values(array_filter(preg_split('/[^A-Z0-9]+/', $sku) ?: []));
        if ($parts === []) {
            return false;
        }

        $pattern = implode('[\\s\\/_.-]*', array_map(fn (string $part) => preg_quote($part, '/'), $parts));
        $haystack = Str::upper(Str::ascii(html_entity_decode(strip_tags($haystack), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return preg_match('/(?<![A-Z0-9])'.$pattern.'(?![A-Z0-9])/', $haystack) === 1;
    }

    protected function isCandidateProductUrl(string $url): bool
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
        $path = Str::lower((string) parse_url($url, PHP_URL_PATH));
        $query = Str::lower((string) parse_url($url, PHP_URL_QUERY));

        if (Str::endsWith($host, 'clickoutil.com')) {
            return Str::endsWith($path, '.html') && Str::contains($path, 'product');
        }

        if (Str::endsWith($host, 'maximum.md')) {
            return preg_match('~^/(?:ru|ro)/\d+/?$~', $path) === 1;
        }

        if (Str::endsWith($host, 'groupe-mlv-france.fr')) {
            return Str::endsWith($path, '.html') && ! Str::contains($path, '/recherche');
        }

        if (Str::endsWith($host, 'gysusa.com')) {
            return Str::contains($path, ['productdetails.asp', '/product/'])
                || Str::contains($query, ['productcode=', 'product_code=']);
        }

        if (Str::endsWith($host, ['gys.com.ua', 'gys-ukraine.com', 'gysweldingusa.com'])) {
            return Str::contains($path, ['/product/', '/produkt/', '/shop/', '/catalog/'])
                || preg_match('/\.(?:html?|php)$/i', $path) === 1;
        }

        return false;
    }
}
