<?php

namespace App\Jobs;

use App\Models\ProductParserItem;
use App\Services\ProductParserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FindProductImagesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $itemId)
    {
    }

    public function handle(ProductParserService $parser): void
    {
        $item = ProductParserItem::with('batch')->find($this->itemId);

        if ($item) {
            $parser->parseItem($item, false);
        }
    }
}
