<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPriceListRowJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $itemId)
    {
    }

    public function handle(): void
    {
        // Row processing is orchestrated by ParsePriceListJob so group context from the price file is preserved.
    }
}
