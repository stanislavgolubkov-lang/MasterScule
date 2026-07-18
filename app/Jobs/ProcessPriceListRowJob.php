<?php

namespace App\Jobs;

use App\Models\ProductParserBatch;
use App\Models\ProductParserItem;
use App\Services\ProductPriceListImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessPriceListRowJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(public int $itemId, string $queue = 'parser-fast')
    {
        $this->onQueue($queue);
    }

    public function handle(ProductPriceListImportService $importer): void
    {
        $item = ProductParserItem::with('batch')->find($this->itemId);

        if (! $item) {
            return;
        }

        if (! $item->batch) {
            return;
        }

        if ($item->batch->status === 'cancelled') {
            $item->forceFill(['status' => 'rejected'])->save();

            return;
        }

        if (! in_array($item->status, ['queued', 'searching', 'tristool_queued', 'tristool_searching'], true)) {
            $importer->finalizeQueuedImport($item->batch);

            return;
        }

        $batchId = $item->batch_id;
        $item->forceFill([
            'status' => 'tristool_searching',
            'processing_stage' => 'tristool_searching',
        ])->save();
        $importer->processFastQueuedItem($item);

        if ($batch = ProductParserBatch::find($batchId)) {
            $importer->finalizeQueuedImport($batch);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $item = ProductParserItem::with('batch')->find($this->itemId);

        if (! $item || ! $item->batch || $item->batch->status === 'cancelled') {
            return;
        }

        $item->forceFill([
            'status' => 'failed',
            'processing_stage' => 'tristool_failed',
            'tristool_checked_at' => now(),
            'error_message' => trim(($item->error_message ? $item->error_message.' ' : '').($exception?->getMessage() ?: 'Row processing failed.')),
        ])->save();
        $item->batch->addLog('Queued price list row failed', [
            'row' => $item->row_number,
            'sku' => $item->sku,
            'error' => $exception?->getMessage(),
        ]);

        if ($batch = ProductParserBatch::find($item->batch_id)) {
            app(ProductPriceListImportService::class)->finalizeQueuedImport($batch);
        }
    }
}
