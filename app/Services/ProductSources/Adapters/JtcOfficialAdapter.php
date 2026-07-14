<?php

namespace App\Services\ProductSources\Adapters;

class JtcOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['JTC'];
    }

    protected function searchUrls(string $sku): array
    {
        return [
            'https://eng.jtc.com.tw/product/index.php?keywords='.rawurlencode($sku).'&mode=search',
            'https://www.jtcautotools.com/search?q='.rawurlencode($sku),
        ];
    }
}
