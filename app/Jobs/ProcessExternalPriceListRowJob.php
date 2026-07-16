<?php

namespace App\Jobs;

use App\Models\ProductParserBatch;
use App\Models\ProductParserItem;
use App\Services\ProductPriceListImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessExternalPriceListRowJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 3;

    public array $backoff = [60, 180];

    public function __construct(public int $itemId, string $queue = 'parser-slow')
    {
        $this->onQueue($queue);
    }

    public function handle(ProductPriceListImportService $importer): void
    {
        $item = ProductParserItem::with('batch')->find($this->itemId);

        if (! $item || ! $item->batch) {
            return;
        }

        if ($item->batch->status === 'cancelled') {
            $item->forceFill(['status' => 'rejected', 'processing_stage' => 'rejected'])->save();

            return;
        }

        if (! in_array($item->status, ['external_check_queued', 'external_searching'], true)) {
            $importer->finalizeQueuedImport($item->batch);

            return;
        }

        $batchId = $item->batch_id;
        $item->forceFill([
            'status' => 'external_searching',
            'processing_stage' => 'external_searching',
        ])->save();
        $importer->processExternalQueuedItem($item);

        $item = ProductParserItem::with('createdProduct')->find($this->itemId);
        if ($item
            && $item->processing_stage === 'external_ready'
            && $item->createdProduct?->status === 'draft') {
            $item->forceFill([
                'status' => 'image_publish_queued',
                'processing_stage' => 'image_publish_queued',
                'error_message' => null,
            ])->save();
            PrepareAndPublishParserDraftJob::dispatch($item->id);
        }

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
            'processing_stage' => 'external_failed',
            'external_checked_at' => now(),
            'error_message' => trim(($item->error_message ? $item->error_message.' ' : '').($exception?->getMessage() ?: 'External source processing failed.')),
        ])->save();
        $item->batch->addLog('External price list row failed', [
            'row' => $item->row_number,
            'sku' => $item->sku,
            'error' => $exception?->getMessage(),
        ]);

        if ($batch = ProductParserBatch::find($item->batch_id)) {
            app(ProductPriceListImportService::class)->finalizeQueuedImport($batch);
        }
    }
}
