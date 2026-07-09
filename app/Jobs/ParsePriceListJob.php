<?php

namespace App\Jobs;

use App\Models\ProductParserBatch;
use App\Services\ProductPriceListImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ParsePriceListJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public function __construct(public int $batchId)
    {
    }

    public function handle(ProductPriceListImportService $importer): void
    {
        $batch = ProductParserBatch::find($this->batchId);

        if (! $batch || $batch->status === 'cancelled') {
            return;
        }

        if ($batch->import_mode === 'dry_run') {
            $importer->dryRun($batch);

            return;
        }

        $importer->import($batch);
    }
}
