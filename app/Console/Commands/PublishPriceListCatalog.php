<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductParserBatch;
use App\Services\Catalog\ProductImageAvailabilityService;
use App\Services\ProductDraftService;
use Illuminate\Console\Command;
use Throwable;

class PublishPriceListCatalog extends Command
{
    protected $signature = 'masterscule:publish-price-list-catalog {batch} {--execute}';

    protected $description = 'Create every unique price-list product and expose photo-pending cards in the catalog';

    public function handle(ProductDraftService $drafts, ProductImageAvailabilityService $images): int
    {
        $batch = ProductParserBatch::find($this->argument('batch'));
        if (! $batch) {
            $this->error('Parser batch was not found.');

            return self::FAILURE;
        }

        $items = $batch->items()
            ->with(['category', 'createdProduct', 'imageAssets', 'batch'])
            ->whereNotNull('sku')
            ->where('status', '!=', 'skipped')
            ->orderBy('id')
            ->get()
            ->unique(fn ($item) => mb_strtoupper(trim((string) $item->sku)))
            ->values();

        $missing = $items->filter(fn ($item) => ! Product::where('sku', $item->sku)->exists())->count();
        $draftCount = Product::whereIn('sku', $items->pluck('sku'))->where('status', 'draft')->count();

        $this->table(
            ['batch', 'unique SKU', 'missing products', 'draft products', 'mode'],
            [[$batch->id, $items->count(), $missing, $draftCount, $this->option('execute') ? 'execute' : 'dry-run']],
        );

        if (! $this->option('execute')) {
            $this->info('Dry-run only. Add --execute to create and expose all cards.');

            return self::SUCCESS;
        }

        $created = 0;
        $published = 0;
        $photoPending = 0;
        $failed = [];

        foreach ($items as $item) {
            try {
                $product = Product::where('sku', $item->sku)->first();
                if (! $product) {
                    $overrideSlug = config('catalog_taxonomy.sku_overrides.'.mb_strtoupper(trim((string) $item->sku)));
                    if ($overrideSlug) {
                        $overrideCategory = Category::where('slug', $overrideSlug)
                            ->where('is_active', true)
                            ->where('is_assignable', true)
                            ->first();
                        if ($overrideCategory) {
                            $item->forceFill([
                                'category_id' => $overrideCategory->id,
                                'detected_category_id' => $overrideCategory->id,
                                'needs_category_review' => false,
                            ])->save();
                            $item->unsetRelation('category');
                        }
                    }
                    $product = $drafts->createDraft($item);
                    $created++;
                }

                $imageReady = $images->isAvailable($product->main_image);
                $product->forceFill([
                    'status' => 'published',
                    'approval_status' => 'approved',
                    'is_active' => true,
                    'needs_review' => false,
                    'needs_image_review' => ! $imageReady,
                    'needs_category_review' => false,
                    'needs_translation_review' => false,
                    'needs_price_review' => false,
                    'needs_stock_review' => false,
                ])->save();

                $item->forceFill([
                    'status' => 'approved',
                    'processing_stage' => $imageReady ? 'published' : 'catalog_published_photo_pending',
                    'approval_status' => 'approved',
                    'created_product_id' => $item->created_product_id ?: $product->id,
                    'needs_image_review' => ! $imageReady,
                    'needs_category_review' => false,
                    'needs_translation_review' => false,
                    'needs_price_review' => false,
                    'needs_stock_review' => false,
                    'error_message' => $imageReady ? null : 'Product is visible in the catalog; verified photo is pending.',
                ])->save();

                $published++;
                $photoPending += $imageReady ? 0 : 1;
            } catch (Throwable $exception) {
                $failed[] = ['sku' => $item->sku, 'error' => $exception->getMessage()];
            }
        }

        $batch->forceFill([
            'created_drafts' => $batch->items()->whereNotNull('created_product_id')->count(),
            'error_rows' => count($failed),
            'status' => $failed === [] ? 'completed' : 'completed_with_errors',
            'finished_at' => now(),
        ])->save();
        $batch->addLog('All price-list products exposed in catalog', [
            'unique_skus' => $items->count(),
            'created_now' => $created,
            'published_or_refreshed' => $published,
            'photo_pending' => $photoPending,
            'failed' => count($failed),
        ]);

        $this->table(
            ['created now', 'published/refreshed', 'photo pending', 'failed'],
            [[$created, $published, $photoPending, count($failed)]],
        );

        foreach ($failed as $failure) {
            $this->error($failure['sku'].': '.$failure['error']);
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }
}
