<?php

namespace App\Jobs;

use App\Models\ProductParserItem;
use App\Services\ProductImageProcessorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessProductImagesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $itemId)
    {
    }

    public function handle(ProductImageProcessorService $processor): void
    {
        $item = ProductParserItem::with(['imageAssets', 'batch'])->find($this->itemId);

        if ($item) {
            $processor->processSelected($item);
        }
    }
}
