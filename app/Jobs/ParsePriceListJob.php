<?php

namespace App\Jobs;

use App\Models\ProductParserBatch;
use App\Services\ProductPriceListImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ParsePriceListJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(public int $batchId) {}

    public function handle(ProductPriceListImportService $importer): void
    {
        Cache::store(config('product_parser.lock_store', 'file'))
            ->lock('parser-price-list-batch:'.$this->batchId, $this->timeout)
            ->get(function () use ($importer) {
                $batch = ProductParserBatch::find($this->batchId);

                if (! $batch || in_array($batch->status, ['cancelled', 'completed', 'completed_with_errors', 'dry_run_completed', 'failed'], true)) {
                    return;
                }

                $options = $batch->options_json ?: [];
                if ($batch->status === 'processing' && ($options['staging_complete'] ?? false)) {
                    return;
                }

                if ($batch->import_mode === 'dry_run') {
                    $importer->dryRun($batch);

                    return;
                }

                $importer->queueImport($batch);
            });
    }

    public function failed(?Throwable $exception): void
    {
        $batch = ProductParserBatch::find($this->batchId);

        if (! $batch || $batch->status === 'cancelled') {
            return;
        }

        $batch->forceFill([
            'status' => 'failed',
            'finished_at' => now(),
        ])->save();
        $batch->addLog('Price list queue job failed', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
