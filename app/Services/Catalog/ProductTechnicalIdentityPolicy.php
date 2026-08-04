<?php

namespace App\Services\Catalog;

use App\Models\Product;

class ProductTechnicalIdentityPolicy
{
    public function allowsCyrillicSku(Product $product): bool
    {
        return $this->isUhlMash($product);
    }

    public function romanianLanguageSample(Product $product, string $value): string
    {
        if (! $this->isUhlMash($product)) {
            return $value;
        }

        $tokens = array_values(array_unique(array_filter([
            trim((string) $product->sku),
            trim((string) $product->brand?->name),
            "\u{0423}\u{0425}\u{041B}-\u{041C}\u{0410}\u{0428}",
            "\u{0423}\u{0425}\u{041B} \u{041C}\u{0410}\u{0428}",
        ])));

        return trim(str_replace($tokens, ' ', $value));
    }

    private function isUhlMash(Product $product): bool
    {
        return $product->brand?->slug === 'uhl-mash';
    }
}
