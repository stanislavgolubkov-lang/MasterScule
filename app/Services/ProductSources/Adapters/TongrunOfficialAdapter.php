<?php

namespace App\Services\ProductSources\Adapters;

class TongrunOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['TONGRUN'];
    }

    protected function searchUrls(string $sku): array
    {
        return ['https://en.tongrunjacks.com/search?keyword='.rawurlencode($sku)];
    }
}
