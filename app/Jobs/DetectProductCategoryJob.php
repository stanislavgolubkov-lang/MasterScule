<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DetectProductCategoryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $itemId)
    {
    }

    public function handle(): void
    {
        // Category detection is performed during price-list parsing and can be retried from the item page.
    }
}
