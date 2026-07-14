<?php

namespace App\Services\ProductSources\Adapters;

use App\Services\ProductSources\ProductSourceProductData;
use App\Services\ProductSources\ProductSourceSearchResult;
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
        preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>[\s\S]*?<img\b[^>]*src=["\']([^"\']+)["\'][^>]*>[\s\S]*?<h3\b[^>]*>([\s\S]*?)<\/h3>[\s\S]*?<p\b[^>]*>([\s\S]*?)<\/p>[\s\S]*?<\/a>/iu', $html, $matches, PREG_SET_ORDER);
        $needle = $this->normalizeSku($sku);
        foreach ($matches as $match) {
            if ($this->normalizeSku(strip_tags($match[4] ?? '')) !== $needle) {
                continue;
            }
            $url = $this->absoluteUrl('https://www.mighty-seven.com', $match[1]);

            return new ProductSourceSearchResult(true, $sku, $brand, $url, 'www.mighty-seven.com', trim(strip_tags($match[3])), true, priority: 100, payload: [
                'api_image' => $this->absoluteUrl('https://www.mighty-seven.com', $match[2]),
            ]);
        }

        return ProductSourceSearchResult::notFound($sku, $brand);
    }

    public function extractImages(ProductSourceProductData $data): array
    {
        return array_values(array_unique(array_filter([
            $data->search->payload['api_image'] ?? null,
            ...parent::extractImages($data),
        ])));
    }
}
