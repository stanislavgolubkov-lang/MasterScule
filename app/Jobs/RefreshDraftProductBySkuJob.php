<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductParserItem;
use App\Services\ProductDraftService;
use App\Services\ProductParserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RefreshDraftProductBySkuJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 3;

    public array $backoff = [60, 180];

    public function __construct(public int $productId, public int $itemId)
    {
        $this->onQueue('parser-slow');
    }

    public function handle(ProductParserService $parser, ProductDraftService $drafts): void
    {
        $product = Product::with(['brand', 'category'])->find($this->productId);
        $item = ProductParserItem::with('batch')->find($this->itemId);

        if (! $product || $product->status !== 'draft' || ! $item) {
            return;
        }

        $parser->parseItem($item, processImages: true);
        $item = ProductParserItem::with(['imageAssets', 'category', 'batch'])->find($this->itemId);

        if (! $item || $item->status === 'failed') {
            return;
        }

        $item->forceFill([
            'created_product_id' => $product->id,
            'existing_product_id' => null,
            'approval_status' => 'pending_review',
        ])->save();

        $drafts->refreshDraftFromSearch($item->fresh(['imageAssets', 'category', 'batch']), $product->fresh());
        $item->batch?->addLog('Repeat SKU search applied to product draft', [
            'sku' => $product->sku,
            'product_id' => $product->id,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $item = ProductParserItem::with('batch')->find($this->itemId);
        if (! $item) {
            return;
        }

        $item->forceFill([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?: 'Repeat SKU search failed.',
        ])->save();
        $item->batch?->addLog('Repeat SKU search failed', [
            'sku' => $item->sku,
            'product_id' => $this->productId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
