<?php

namespace App\Jobs;

use App\Models\ProductParserBatch;
use App\Services\ProductPriceListImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class ParsePriceListJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public function __construct(public int $batchId) {}

    public function handle(ProductPriceListImportService $importer): void
    {
        Cache::lock('parser-price-list-batch:'.$this->batchId, $this->timeout)
            ->get(function () use ($importer) {
                $batch = ProductParserBatch::find($this->batchId);

                if (! $batch || $batch->status !== 'pending') {
                    return;
                }

                if ($batch->import_mode === 'dry_run') {
                    $importer->dryRun($batch);

                    return;
                }

                $importer->import($batch);
            });
    }
}
