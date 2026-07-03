<?php

namespace App\Jobs;

use App\Models\ProductParserItem;
use App\Services\ProductDraftService;
use App\Services\ProductParserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ParseSingleSkuJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $itemId,
        public bool $processImages = false,
        public bool $createDraft = false,
    ) {
    }

    public function handle(ProductParserService $parser, ProductDraftService $drafts): void
    {
        $item = ProductParserItem::with('batch')->find($this->itemId);

        if (! $item) {
            return;
        }

        $parser->parseItem($item, $this->processImages);

        $item->refresh();

        if ($this->createDraft && $item->status === 'ready_for_review' && ! $item->existing_product_id) {
            $drafts->createDraft($item);
            $parser->refreshBatchStatus($item->batch);
        }
    }
}
