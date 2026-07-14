<?php

namespace App\Services\ProductSources;

interface ProductSourceAdapterInterface
{
    public function supportsBrand(string $brand): bool;

    public function searchBySku(string $sku, string $brand, ?string $name = null): ProductSourceSearchResult;

    public function fetchProductPage(ProductSourceSearchResult $result): ProductSourceProductData;

    public function extractImages(ProductSourceProductData $data): array;

    public function extractDescription(ProductSourceProductData $data): ?string;

    public function extractSpecifications(ProductSourceProductData $data): array;

    public function extractBreadcrumb(ProductSourceProductData $data): array;
}
