<?php

namespace App\Jobs;

use App\Models\ProductParserItem;
use App\Services\Catalog\ProductPublicationGuard;
use App\Services\ProductDraftService;
use App\Services\ProductParserItemPreparationService;
use App\Services\ProductPriceListImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PrepareAndPublishParserDraftJob implements ShouldQueue
{
    use Queueable;

    private const APPROVAL_FLAGS = [
        'needs_translation_review',
        'needs_content_review',
        'needs_price_review',
        'needs_stock_review',
    ];

    public int $timeout = 240;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(public int $itemId)
    {
        $this->onQueue('parser-images');
    }

    public function handle(
        ProductParserItemPreparationService $preparation,
        ProductDraftService $drafts,
        ProductPublicationGuard $publicationGuard,
    ): void {
        $item = ProductParserItem::with(['createdProduct', 'imageAssets', 'category', 'batch'])->find($this->itemId);
        if (! $item || ! $item->createdProduct || $item->createdProduct->status !== 'draft') {
            return;
        }

        if (! $preparation->prepare($item, true)) {
            $item->refresh()->load('batch');

            if ((int) $item->external_attempts === 0) {
                $item->forceFill([
                    'status' => 'external_check_queued',
                    'processing_stage' => 'external_queued',
                    'error_message' => 'TrisTool image is not publishable. External image recovery queued.',
                ])->save();
                ProcessExternalPriceListRowJob::dispatch($item->id, 'parser-image-recovery');
                $item->batch?->addLog('Parser draft queued for external image recovery', [
                    'sku' => $item->sku,
                ]);

                return;
            }

            $item->forceFill([
                'status' => 'needs_manual_review',
                'processing_stage' => 'external_manual',
                'needs_image_review' => true,
                'error_message' => 'TrisTool and external sources did not provide a publishable image.',
            ])->save();
            $item->batch?->addLog('Parser draft exhausted automatic image recovery', [
                'sku' => $item->sku,
            ]);
            app(ProductPriceListImportService::class)->finalizeQueuedImport($item->batch);

            return;
        }

        $item->refresh()->load(['createdProduct', 'imageAssets', 'category', 'batch']);
        $product = $drafts->refreshParserDraft($item, $item->createdProduct);
        $result = $publicationGuard->publish($product->fresh(), true, self::APPROVAL_FLAGS);

        if (! $result['allowed']) {
            $item->forceFill([
                'status' => 'draft_created',
                'processing_stage' => 'publication_blocked',
                'error_message' => implode(' ', $result['errors']),
            ])->save();
            $item->batch?->addLog('Prepared parser draft remains blocked from publication', [
                'sku' => $item->sku,
                'product_id' => $product->id,
                'errors' => $result['error_codes'],
            ]);
            app(ProductPriceListImportService::class)->finalizeQueuedImport($item->batch);

            return;
        }

        $item->forceFill([
            'status' => 'approved',
            'processing_stage' => 'published',
            'approval_status' => 'approved',
            'needs_translation_review' => false,
            'needs_content_review' => false,
            'needs_price_review' => false,
            'needs_stock_review' => false,
            'error_message' => null,
        ])->save();
        $item->batch?->addLog('Parser draft image prepared and product published', [
            'sku' => $item->sku,
            'product_id' => $product->id,
        ]);
        app(ProductPriceListImportService::class)->finalizeQueuedImport($item->batch);
    }

    public function failed(?Throwable $exception): void
    {
        $item = ProductParserItem::with('batch')->find($this->itemId);
        if (! $item) {
            return;
        }

        $item->forceFill([
            'status' => 'draft_created',
            'processing_stage' => 'image_processing_failed',
            'needs_image_review' => true,
            'error_message' => $exception?->getMessage() ?: 'Parser image preparation failed.',
        ])->save();
        $item->batch?->addLog('Parser draft image preparation failed', [
            'sku' => $item->sku,
            'error' => $exception?->getMessage(),
        ]);
        app(ProductPriceListImportService::class)->finalizeQueuedImport($item->batch);
    }
}
