<?php

namespace App\Services\ProductSources\Adapters;

use Illuminate\Support\Str;

class UhlMashOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['UHL_MASH'];
    }

    protected function searchUrls(string $sku): array
    {
        return [
            'https://uhl-mash.com.ua/search/?search='.rawurlencode($sku),
        ];
    }

    protected function isCandidateProductUrl(string $url): bool
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
        $path = Str::lower((string) parse_url($url, PHP_URL_PATH));

        return Str::endsWith($host, 'uhl-mash.com.ua')
            && Str::startsWith($path, '/products/')
            && Str::endsWith($path, '.php');
    }

    protected function candidateMatchesSku(string $candidate, string $text, string $sku): bool
    {
        // Short all-numeric supplier codes are ambiguous on the large official
        // catalogue. They still use the reviewed price-list/logo fallback.
        $needle = $this->normalizeSku($sku);
        if (mb_strlen($needle) < 4 || (ctype_digit($needle) && mb_strlen($needle) < 6)) {
            return false;
        }

        return parent::candidateMatchesSku($candidate, $text, $sku);
    }
}
