<?php

namespace App\Jobs;

use App\Models\ProductParserItem;
use App\Services\Catalog\ProductPublicationGuard;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ValidateProductDraftQualityJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $itemId) {}

    public function handle(ProductPublicationGuard $guard): void
    {
        $item = ProductParserItem::with(['createdProduct', 'batch'])->find($this->itemId);
        if (! $item?->createdProduct) {
            return;
        }

        $result = $guard->evaluate($item->createdProduct, true);
        $item->batch?->addLog('Draft quality guard completed', [
            'sku' => $item->sku,
            'publishable' => $result['allowed'],
            'blocking_reasons' => $result['error_codes'],
        ]);
    }
}
