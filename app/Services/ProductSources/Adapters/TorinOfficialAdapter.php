<?php

namespace App\Services\ProductSources\Adapters;

class TorinOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['TORIN'];
    }

    protected function searchUrls(string $sku): array
    {
        return [
            'https://torinjacks.com/search?q='.rawurlencode($sku),
            'https://torin-usa.com/search?q='.rawurlencode($sku),
        ];
    }
}
