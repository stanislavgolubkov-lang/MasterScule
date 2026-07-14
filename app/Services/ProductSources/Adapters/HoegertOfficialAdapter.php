<?php

namespace App\Services\ProductSources\Adapters;

class HoegertOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['HOEGERT'];
    }

    protected function searchUrls(string $sku): array
    {
        return ['https://hoegert.com/?s='.rawurlencode($sku).'&post_type=product'];
    }
}
