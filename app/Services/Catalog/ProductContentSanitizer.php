<?php

namespace App\Services\Catalog;

use Illuminate\Support\Str;

class ProductContentSanitizer
{
    public function sanitize(?string $value): string
    {
        $value = trim((string) preg_replace(
            '/\s+/u',
            ' ',
            html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ));

        return $this->containsMarketplacePromotion($value) ? '' : $value;
    }

    public function containsMarketplacePromotion(?string $value): bool
    {
        $value = Str::lower(Str::ascii(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ($value === '') {
            return false;
        }

        return Str::contains($value, [
            'maximum.md',
            'maxim.md',
            '+373(22)54-54-54',
            '+37322545454',
        ]) || (
            Str::contains($value, ['kupit po luchshei tsene', 'cumpara la cel mai bun pret'])
            && Str::contains($value, ['internet-magazin', 'magazinul online'])
        );
    }
}
