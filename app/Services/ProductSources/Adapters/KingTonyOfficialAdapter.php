<?php

namespace App\Services\ProductSources\Adapters;

class KingTonyOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['KING_TONY'];
    }

    protected function searchUrls(string $sku): array
    {
        return ['https://www.kingtony.com/products_search.php?keywords='.rawurlencode($sku)];
    }
}
