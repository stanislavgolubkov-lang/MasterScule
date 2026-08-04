<?php

namespace App\Support;

class ProductSkuNormalizer
{
    /**
     * Keep every Unicode letter and number in supplier SKUs.
     *
     * Several catalogues (notably UHL-MASH) use Cyrillic model letters.
     * Stripping everything outside ASCII makes unrelated models such as
     * ШО-400/1 and ШОМ-400/1 collapse to the same identity.
     */
    public static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        return preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?: '';
    }
}
