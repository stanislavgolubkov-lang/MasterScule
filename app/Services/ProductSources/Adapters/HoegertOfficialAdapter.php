<?php

namespace App\Services\ProductSources\Adapters;

use App\Services\ProductSources\ProductSourceProductData;
use App\Services\ProductSources\ProductSourceSearchResult;
use Illuminate\Support\Str;
use Throwable;

class HoegertOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['HOEGERT'];
    }

    protected function searchUrls(string $sku): array
    {
        return [
            'https://en.hoegert.com/?s='.rawurlencode($sku).'&post_type=product',
            'https://hoegert.com/?s='.rawurlencode($sku).'&post_type=product',
            'https://shop.hoegert.com/search?q='.rawurlencode($sku),
        ];
    }

    public function searchBySku(string $sku, string $brand, ?string $name = null): ProductSourceSearchResult
    {
        $sku = trim($sku);
        $needle = $this->normalizeSku($sku);

        foreach (['https://en.hoegert.com', 'https://ru.hoegert.com', 'https://hoegert.com'] as $baseUrl) {
            try {
                $response = $this->request()
                    ->get($baseUrl.'/wp-json/wp/v2/search', [
                        'search' => $sku,
                        'per_page' => 6,
                        'subtype' => 'product',
                    ]);
            } catch (Throwable) {
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            foreach ($response->json() ?: [] as $row) {
                $url = (string) ($row['url'] ?? '');
                $title = trim(strip_tags((string) ($row['title'] ?? '')));
                $haystack = $this->normalizeSku($url.' '.$title);
                $apiProductUrl = (string) ($row['_links']['self'][0]['href'] ?? '');
                $apiProduct = null;

                if (($row['subtype'] ?? null) !== 'product'
                    || $needle === ''
                    || (! Str::contains($haystack, $needle) && ! $this->apiProductContainsSku($apiProductUrl, $needle, $apiProduct))) {
                    continue;
                }

                $domain = (string) parse_url($url, PHP_URL_HOST);
                if (! $this->registry->isOfficialDomain($domain, $brand)) {
                    continue;
                }

                return new ProductSourceSearchResult(
                    true,
                    $sku,
                    $brand,
                    $url,
                    $domain,
                    $title,
                    true,
                    priority: 100,
                    payload: [
                        'api_product_url' => $apiProductUrl ?: null,
                        'api_product' => $apiProduct,
                        'api_search_base' => $baseUrl,
                    ],
                );
            }
        }

        return parent::searchBySku($sku, $brand, $name);
    }

    public function fetchProductPage(ProductSourceSearchResult $result): ProductSourceProductData
    {
        $page = parent::fetchProductPage($result);
        $apiUrl = (string) ($result->payload['api_product_url'] ?? '');
        $json = $result->payload['api_product'] ?? null;

        if ($apiUrl === '' && ! is_array($json)) {
            return $page;
        }

        $json = is_array($json) ? $json : $this->fetchApiProduct($apiUrl);
        if (! is_array($json)) {
            return $page;
        }
        $contentHtml = (string) data_get($json, 'content.rendered', '');
        $excerptHtml = (string) data_get($json, 'excerpt.rendered', '');
        $title = $this->cleanWpText((string) data_get($json, 'title.rendered', '')) ?: $page->title;
        $description = $this->descriptionFromWp($excerptHtml) ?: $this->descriptionFromWp($contentHtml) ?: $page->description;
        $specifications = $this->specificationsFromWp($contentHtml) ?: $page->specifications;

        return new ProductSourceProductData(
            search: $result,
            html: $page->html,
            title: $title,
            description: $description,
            images: $page->images,
            specifications: $specifications,
            breadcrumb: $page->breadcrumb,
            raw: array_merge($result->payload, ['api_product' => $json]),
        );
    }

    private function descriptionFromWp(string $html): ?string
    {
        $text = $this->cleanWpText($html);
        if (! $text) {
            return null;
        }

        $text = preg_replace('/^\s*(?:Артикул|Indeks)\s*:\s*[A-Z0-9\-\/]+\s*/iu', '', $text) ?: $text;
        $text = preg_replace('/\s*У вас есть вопросы по продукту\?.*$/iu', '', $text) ?: $text;
        $text = preg_replace('/\s*Masz pytania o produkt\?.*$/iu', '', $text) ?: $text;

        return mb_strlen($text) >= 80 ? trim($text) : null;
    }

    private function apiProductContainsSku(string $apiUrl, string $needle, ?array &$json = null): bool
    {
        $json = $this->fetchApiProduct($apiUrl);

        return is_array($json)
            && $needle !== ''
            && Str::contains($this->normalizeSku(json_encode($json, JSON_UNESCAPED_UNICODE) ?: ''), $needle);
    }

    private function fetchApiProduct(string $apiUrl): ?array
    {
        if ($apiUrl === '') {
            return null;
        }

        try {
            $response = $this->request()->get($apiUrl);
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? ($response->json() ?: []) : null;
    }

    private function specificationsFromWp(string $html): array
    {
        preg_match_all('/<tr[^>]*>\s*<td[^>]*>([\s\S]*?)<\/td>\s*<td[^>]*>([\s\S]*?)<\/td>\s*<\/tr>/iu', $html, $rows, PREG_SET_ORDER);

        $specs = [];
        foreach ($rows as $row) {
            $key = $this->cleanWpText($row[1] ?? '');
            $value = $this->cleanWpText($row[2] ?? '');
            if ($key && $value) {
                $specs[$key] = $value;
            }
        }

        preg_match_all('/(?:•|\*)\s*([^;:\n\r—-]+?)\s*(?:—|-|:)\s*([^;\n\r<]+)/u', $html, $bullets, PREG_SET_ORDER);
        foreach ($bullets as $bullet) {
            $key = $this->cleanWpText($bullet[1] ?? '');
            $value = $this->cleanWpText($bullet[2] ?? '');
            if ($key && $value) {
                $specs[$key] = $value;
            }
        }

        return array_slice($specs, 0, 40, true);
    }

    private function cleanWpText(string $value): ?string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\[\/?[a-z0-9_:-]+(?:\s+[^\]]*)?\]/iu', ' ', $value) ?: $value;
        $value = preg_replace('/<\s*br\s*\/?>/iu', "\n", $value) ?: $value;
        $value = preg_replace('/<\/(?:p|div|h[1-6]|li)>/iu', "\n", $value) ?: $value;
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?: $value;
        $value = trim($value, " \t\n\r\0\x0B ");

        return $value !== '' ? $value : null;
    }
}
