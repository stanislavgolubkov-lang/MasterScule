<?php

use App\Models\Product;

if (! function_exists('money')) {
    function money(float|int|string|null $amount, ?string $currency = null): string
    {
        $value = (float) ($amount ?? 0);
        if (! $currency) {
            try {
                $currency = config('store.currency', 'MDL');
            } catch (Throwable) {
                $currency = 'MDL';
            }
        }

        return number_format($value, 2, ',', ' ').' '.$currency;
    }
}

if (! function_exists('discountPercent')) {
    function discountPercent(float|int|string|null $oldPrice, float|int|string|null $newPrice): ?string
    {
        $old = (float) ($oldPrice ?? 0);
        $new = (float) ($newPrice ?? 0);

        if ($old <= 0 || $new <= 0 || $new >= $old) {
            return null;
        }

        return '-'.max(1, (int) round((($old - $new) / $old) * 100)).'%';
    }
}

if (! function_exists('productGallery')) {
    /**
     * @return array<int, string>
     */
    function productGallery(Product $product): array
    {
        $gallery = $product->gallery ?: [];
        array_unshift($gallery, $product->main_image);

        return array_values(array_unique(array_filter($gallery)));
    }
}
