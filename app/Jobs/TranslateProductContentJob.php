<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TranslateProductContentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $itemId)
    {
    }

    public function handle(): void
    {
        // RU/RO content is prepared deterministically by ProductParserContentBuilder during parsing.
    }
}
