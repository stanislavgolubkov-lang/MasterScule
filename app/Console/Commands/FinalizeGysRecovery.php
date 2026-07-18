<?php

namespace App\Console\Commands;

use App\Jobs\PrepareAndPublishParserDraftJob;
use App\Models\ProductParserBatch;
use App\Services\Catalog\ProductPublicationGuard;
use App\Services\ProductDraftService;
use App\Services\ProductParserItemPreparationService;
use App\Services\ProductPriceListImportService;
use Illuminate\Console\Command;
use Throwable;

class FinalizeGysRecovery extends Command
{
    protected $signature = 'parser:finalize-gys-recovery {batch}';

    protected $description = 'Finalize and publish every unresolved GYS row after ordered source recovery';

    public function handle(
        ProductPriceListImportService $importer,
        ProductParserItemPreparationService $preparation,
        ProductDraftService $drafts,
        ProductPublicationGuard $publicationGuard,
    ): int {
        $batch = ProductParserBatch::find((int) $this->argument('batch'));
        if (! $batch) {
            $this->error('Parser batch was not found.');

            return self::FAILURE;
        }

        $items = $batch->items()
            ->where('brand', 'GYS')
            ->whereNotIn('status', ['approved', 'skipped'])
            ->orderBy('id')
            ->get();
        $published = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $importer->prepareApprovedGysRecovery($item);
                $item->refresh();
                if ($item->status !== 'approved') {
                    (new PrepareAndPublishParserDraftJob($item->id))->handle($preparation, $drafts, $publicationGuard);
                    $item->refresh();
                }
                if ($item->status !== 'approved') {
                    $importer->prepareApprovedGysRecovery($item, true);
                    (new PrepareAndPublishParserDraftJob($item->id))->handle($preparation, $drafts, $publicationGuard);
                    $item->refresh();
                }

                $item->status === 'approved' ? $published++ : $failed++;
            } catch (Throwable $e) {
                $failed++;
                $item->forceFill(['error_message' => $e->getMessage()])->save();
            }
        }

        $importer->finalizeQueuedImport($batch->fresh());
        $unresolved = $batch->items()
            ->where('brand', 'GYS')
            ->whereNotIn('status', ['approved', 'skipped'])
            ->count();
        if ($failed === 0 && $unresolved === 0) {
            $report = $batch->fresh()->dry_run_report_json ?: [];
            $report['error_rows'] = 0;
            $report['queued_rows'] = 0;
            $batch->forceFill([
                'status' => 'completed',
                'error_rows' => 0,
                'dry_run_report_json' => $report,
                'finished_at' => now(),
            ])->save();
        }
        $batch->addLog('Approved GYS recovery finalization completed', [
            'processed' => $items->count(),
            'published' => $published,
            'failed' => $failed,
        ]);

        $this->info("Processed {$items->count()}; published {$published}; failed {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
