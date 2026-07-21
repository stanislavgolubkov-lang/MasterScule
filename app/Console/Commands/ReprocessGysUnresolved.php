<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPriceListRowJob;
use App\Models\ProductParserBatch;
use Illuminate\Console\Command;

class ReprocessGysUnresolved extends Command
{
    protected $signature = 'parser:reprocess-gys-unresolved {batch}';

    protected $description = 'Recheck unresolved GYS rows through TrisTool, reviewed manufacturer sources, and the approved GYS brand fallback';

    public function handle(): int
    {
        $batch = ProductParserBatch::find((int) $this->argument('batch'));
        if (! $batch) {
            $this->error('Parser batch was not found.');

            return self::FAILURE;
        }

        $options = $batch->options_json ?: [];
        $options['source_mode'] = 'auto';
        $options['approve_gys_ordered_recovery'] = true;
        $batch->forceFill([
            'options_json' => $options,
            'status' => 'processing',
            'finished_at' => null,
        ])->save();

        $items = $batch->items()
            ->where('brand', 'GYS')
            ->whereNull('created_product_id')
            ->whereIn('status', ['needs_manual_review', 'rejected'])
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            $this->info('No unresolved GYS rows found. Existing queued rows keep the approved recovery policy.');

            return self::SUCCESS;
        }

        foreach ($items as $item) {
            $item->forceFill([
                'status' => 'tristool_queued',
                'processing_stage' => 'tristool_queued',
                'external_attempts' => 0,
                'error_message' => null,
            ])->save();
            ProcessPriceListRowJob::dispatch($item->id, 'parser-tristool');
        }

        $batch->addLog('Unresolved GYS rows queued for ordered source recovery', [
            'queued' => $items->count(),
            'priority' => ['tristool.md', 'official_gys', 'gys_brand_fallback'],
        ]);

        $this->info("Queued {$items->count()} unresolved GYS rows.");

        return self::SUCCESS;
    }
}
