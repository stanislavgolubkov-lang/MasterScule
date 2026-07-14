<?php

namespace App\Services\ProductSources;

class ProductSourceSearchResult
{
    public function __construct(
        public readonly bool $found,
        public readonly string $sku,
        public readonly string $brand,
        public readonly ?string $url = null,
        public readonly ?string $domain = null,
        public readonly ?string $title = null,
        public readonly bool $exactSku = false,
        public readonly string $sourceType = 'official_manufacturer',
        public readonly int $priority = 0,
        public readonly array $payload = [],
    ) {}

    public static function notFound(string $sku, string $brand): self
    {
        return new self(false, $sku, $brand);
    }
}
