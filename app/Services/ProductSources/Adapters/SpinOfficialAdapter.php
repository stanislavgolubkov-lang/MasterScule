<?php

namespace App\Services\ProductSources\Adapters;

use Illuminate\Support\Str;

class SpinOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['SPIN'];
    }

    protected function searchUrls(string $sku): array
    {
        return ['https://www.spinsrl.it/?s='.rawurlencode($sku)];
    }

    protected function isCandidateProductUrl(string $url): bool
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
        $path = Str::lower((string) parse_url($url, PHP_URL_PATH));

        return Str::endsWith($host, 'spinsrl.it') && Str::startsWith($path, '/prodotto/');
    }
}
