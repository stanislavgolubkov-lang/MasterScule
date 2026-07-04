<?php

namespace App\Jobs;

use App\Models\ProductParserItem;
use App\Services\ProductDraftService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateProductDraftJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $itemId)
    {
    }

    public function handle(ProductDraftService $drafts): void
    {
        $item = ProductParserItem::with(['imageAssets', 'category', 'batch'])->find($this->itemId);

        if ($item) {
            $drafts->createDraft($item);
        }
    }
}
