<?php

namespace App\Services\ProductSources\Adapters;

use App\Services\ProductSources\ProductSourceAdapterInterface;
use App\Services\ProductSources\ProductSourceProductData;
use App\Services\ProductSources\ProductSourceSearchResult;
use App\Services\TrisToolsEnrichmentService;

class TrisToolsFallbackAdapter implements ProductSourceAdapterInterface
{
    public function __construct(private readonly TrisToolsEnrichmentService $trisTools) {}

    public function supportsBrand(string $brand): bool
    {
        return true;
    }

    public function searchBySku(string $sku, string $brand, ?string $name = null): ProductSourceSearchResult
    {
        $result = $this->trisTools->enrich($sku, $brand);
        if (! ($result['found'] ?? false)) {
            return ProductSourceSearchResult::notFound($sku, $brand);
        }
        $url = $result['source_urls'][0] ?? null;

        return new ProductSourceSearchResult(true, $sku, $brand, $url, $url ? parse_url($url, PHP_URL_HOST) : 'tristool.md', $result['title'] ?? null, true, 'fallback_reference', 40, $result);
    }

    public function fetchProductPage(ProductSourceSearchResult $result): ProductSourceProductData
    {
        $payload = $result->payload;

        return new ProductSourceProductData($result, title: $payload['title'] ?? null, description: $payload['description'] ?? null, images: $payload['images'] ?? [], specifications: $payload['specs'] ?? [], raw: $payload);
    }

    public function extractImages(ProductSourceProductData $data): array
    {
        return $data->images;
    }

    public function extractDescription(ProductSourceProductData $data): ?string
    {
        return $data->description;
    }

    public function extractSpecifications(ProductSourceProductData $data): array
    {
        return $data->specifications;
    }

    public function extractBreadcrumb(ProductSourceProductData $data): array
    {
        return $data->breadcrumb;
    }
}
