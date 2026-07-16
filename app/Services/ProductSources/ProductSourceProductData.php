<?php

namespace App\Services\ProductSources;

class ProductSourceProductData
{
    public function __construct(
        public readonly ProductSourceSearchResult $search,
        public readonly ?string $html = null,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly array $images = [],
        public readonly array $specifications = [],
        public readonly array $breadcrumb = [],
        public readonly array $packageContents = [],
        public readonly array $raw = [],
    ) {}
}
