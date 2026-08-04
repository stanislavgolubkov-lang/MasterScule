<?php

namespace App\Services\ProductSources\Adapters;

use App\Services\ProductSources\ProductSourceProductData;
use App\Services\ProductSources\ProductSourceSearchResult;

class TelwinOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['TELWIN'];
    }

    protected function searchUrls(string $sku): array
    {
        return [];
    }

    public function searchBySku(string $sku, string $brand, ?string $name = null): ProductSourceSearchResult
    {
        $sku = trim($sku);
        if (preg_match('/^\d{6}$/', $sku) !== 1) {
            return ProductSourceSearchResult::notFound($sku, $brand);
        }

        $url = 'https://www.telwin.com/intl/en/generate-pdf/'.rawurlencode($sku);
        if ($this->get($url, $brand) === null) {
            return ProductSourceSearchResult::notFound($sku, $brand);
        }

        return new ProductSourceSearchResult(
            found: true,
            sku: $sku,
            brand: $brand,
            url: $url,
            domain: 'www.telwin.com',
            title: 'TELWIN '.$sku,
            exactSku: true,
            priority: 100,
            payload: ['official_product_sheet' => $url],
        );
    }

    public function fetchProductPage(ProductSourceSearchResult $result): ProductSourceProductData
    {
        return new ProductSourceProductData(
            search: $result,
            title: $result->title,
            raw: $result->payload,
        );
    }
}
