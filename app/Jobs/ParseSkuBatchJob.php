<?php

namespace App\Jobs;

use App\Models\ProductParserBatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ParseSkuBatchJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $batchId)
    {
    }

    public function handle(): void
    {
        $batch = ProductParserBatch::with('items')->find($this->batchId);

        if (! $batch || $batch->status === 'cancelled') {
            return;
        }

        $batch->forceFill([
            'status' => 'running',
            'started_at' => $batch->started_at ?: now(),
        ])->save();
        $batch->addLog('Batch queue started', ['sku_count' => $batch->items->count()]);

        $mode = $batch->options_json['mode'] ?? 'find_only';
        $processImages = in_array($mode, ['find_prepare_photos', 'create_drafts'], true);
        $createDraft = $mode === 'create_drafts';

        foreach ($batch->items as $item) {
            ParseSingleSkuJob::dispatch($item->id, $processImages, $createDraft);
        }
    }
}
