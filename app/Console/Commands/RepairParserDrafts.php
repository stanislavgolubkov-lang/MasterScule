<?php

namespace App\Console\Commands;

use App\Models\ProductParserBatch;
use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use App\Services\Catalog\ProductPublicationGuard;
use App\Services\ProductDraftService;
use App\Services\ProductImageProcessorService;
use App\Services\ProductParserService;
use App\Services\ProductParserSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RepairParserDrafts extends Command
{
    protected $signature = 'masterscule:repair-parser-drafts
        {--batch=}
        {--publish}
        {--catalog-images= : Reviewed image directory relative to the public storage disk}
        {--catalog-source= : Public source URL for the reviewed catalogue}
        {--approve-catalog-images : Attach reviewed local catalogue images to blocked drafts}';

    protected $description = 'Retry sources and images for blocked parser drafts, then optionally publish valid products';

    private const APPROVAL_FLAGS = [
        'needs_translation_review',
        'needs_content_review',
        'needs_price_review',
        'needs_stock_review',
    ];

    public function handle(
        ProductParserService $parser,
        ProductDraftService $drafts,
        ProductImageProcessorService $images,
        ProductPublicationGuard $guard,
        ProductParserSettings $settings,
    ): int {
        $batch = $this->batch();
        if (! $batch) {
            $this->error('Parser batch was not found.');

            return self::FAILURE;
        }

        $settings->update([
            'image_size' => 1200,
            'preview_size' => 600,
            'thumb_size' => 300,
            'max_images_per_product' => 4,
            'min_official_confidence' => 90,
            'min_fallback_confidence' => 80,
        ]);

        $items = $batch->items()
            ->whereNotNull('created_product_id')
            ->whereHas('createdProduct', fn ($query) => $query->where('status', 'draft'))
            ->with(['createdProduct', 'imageAssets', 'category', 'batch'])
            ->orderBy('id')
            ->get();

        $published = 0;
        $repaired = 0;
        $blocked = 0;
        $reasons = [];
        $assetsReconciled = $this->reconcilePublishedAssets($batch, $images);
        $bar = $this->output->createProgressBar($items->count());

        foreach ($items as $item) {
            try {
                $this->attachReviewedCatalogImage($item);
                $item->refresh()->load(['createdProduct', 'imageAssets', 'category', 'batch']);
                $hasReusableSourceAsset = (filled($item->official_source_url) || filled($item->fallback_source_url))
                    && $item->imageAssets->contains(fn ($asset) => ! str_ends_with(strtolower((string) parse_url($asset->source_url, PHP_URL_PATH)), '.svg'));
                if (! $hasReusableSourceAsset) {
                    $parser->parseItem($item, processImages: false);
                    $item->refresh()->load(['createdProduct', 'imageAssets', 'category', 'batch']);
                }
                $this->selectPrimaryImage($item);
                if ($item->imageAssets()->where('is_selected', true)->where('status', '!=', 'processed')->exists()) {
                    $images->processSelected($item->fresh(['imageAssets', 'batch']));
                }
                $item->refresh()->load(['createdProduct', 'imageAssets', 'category', 'batch']);
                $this->keepOnlyUsableSelectedImages($item);
                $item->refresh()->load('imageAssets');

                $imageReady = $item->imageAssets
                    ->where('is_selected', true)
                    ->where('status', 'processed')
                    ->contains(fn ($asset) => $asset->is_main && filled($asset->processed_path) && filled($asset->thumb_path));
                $sourceReady = filled($item->official_source_url)
                    || ($item->fallback_source_used
                        && (int) $item->source_match_confidence >= (int) $settings->get('min_fallback_confidence', 80));

                $item->forceFill([
                    'needs_image_review' => ! $imageReady,
                    'needs_source_review' => ! $sourceReady,
                    'image_reviewed_at' => $imageReady ? now() : null,
                    'source_reviewed_at' => $sourceReady ? now() : null,
                ])->save();

                if ($imageReady) {
                    $item->imageAssets()->where('is_selected', true)->update(['needs_review' => false]);
                }

                $product = $drafts->refreshParserDraft($item->fresh(['imageAssets', 'category', 'batch']), $item->createdProduct);
                $repaired++;
                $result = $this->option('publish')
                    ? $guard->publish($product->fresh(), true, self::APPROVAL_FLAGS)
                    : $guard->evaluate($product->fresh(), true, self::APPROVAL_FLAGS);

                if ($result['allowed']) {
                    if ($this->option('publish')) {
                        $item->forceFill([
                            'status' => 'approved',
                            'approval_status' => 'approved',
                            'needs_translation_review' => false,
                            'needs_content_review' => false,
                            'needs_price_review' => false,
                            'needs_stock_review' => false,
                        ])->save();
                    }
                    $published++;
                } else {
                    $blocked++;
                    foreach ($result['error_codes'] as $code) {
                        $reasons[$code] = ($reasons[$code] ?? 0) + 1;
                    }
                }
            } catch (Throwable $exception) {
                $blocked++;
                $reasons['repair_exception'] = ($reasons['repair_exception'] ?? 0) + 1;
                $batch->addLog('Parser draft repair failed', [
                    'sku' => $item->sku,
                    'error' => $exception->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        arsort($reasons);
        $this->table(['Metric', 'Count'], [
            ['drafts_checked', $items->count()],
            ['drafts_refreshed', $repaired],
            ['published_assets_reconciled', $assetsReconciled],
            [$this->option('publish') ? 'published' : 'publishable', $published],
            ['blocked', $blocked],
        ]);
        if ($reasons !== []) {
            $this->table(['Reason', 'Count'], collect($reasons)->map(fn ($count, $reason) => [$reason, $count])->values()->all());
        }

        $batch->addLog('Parser draft repair completed', [
            'checked' => $items->count(),
            'refreshed' => $repaired,
            'published_assets_reconciled' => $assetsReconciled,
            'published' => $published,
            'blocked' => $blocked,
            'blocked_reasons' => $reasons,
        ]);

        return self::SUCCESS;
    }

    private function reconcilePublishedAssets(ProductParserBatch $batch, ProductImageProcessorService $images): int
    {
        $reconciled = 0;
        $items = $batch->items()
            ->whereNotNull('created_product_id')
            ->whereHas('createdProduct', fn ($query) => $query->where('status', 'published'))
            ->with(['createdProduct', 'imageAssets'])
            ->get();

        foreach ($items as $item) {
            if ($item->imageAssets->contains(fn ($asset) => $asset->is_selected && $asset->status === 'processed')) {
                continue;
            }

            $mainImage = $item->createdProduct?->main_image;
            $asset = $item->imageAssets->first(
                fn ($candidate) => $candidate->status === 'processed' && $candidate->processed_path === $mainImage
            ) ?: $item->imageAssets->first(
                fn ($candidate) => $candidate->status === 'processed'
                    && filled($candidate->processed_path)
                    && filled($candidate->preview_path)
                    && filled($candidate->thumb_path)
            );

            if (! $asset) {
                $source = $item->imageAssets
                    ->first(fn ($candidate) => ! str_ends_with(
                        strtolower((string) parse_url($candidate->source_url, PHP_URL_PATH)),
                        '.svg'
                    ));
                if (! $source) {
                    continue;
                }

                $item->imageAssets()->update(['is_selected' => false, 'is_main' => false]);
                $source->refresh();
                $source->forceFill([
                    'is_selected' => true,
                    'is_main' => true,
                    'status' => 'found',
                    'error_message' => null,
                ])->save();
                $images->processSelected($item->fresh(['imageAssets', 'batch']));
                $item->refresh()->load(['createdProduct', 'imageAssets']);
                $asset = $item->imageAssets->first(
                    fn ($candidate) => $candidate->is_selected && $candidate->status === 'processed'
                );
                if (! $asset) {
                    continue;
                }
            }

            $item->imageAssets()->update(['is_selected' => false, 'is_main' => false]);
            $asset->refresh();
            $asset->forceFill([
                'is_selected' => true,
                'is_main' => true,
                'needs_review' => false,
            ])->save();
            $reconciled++;
        }

        return $reconciled;
    }

    private function attachReviewedCatalogImage(ProductParserItem $item): void
    {
        if (! $this->option('approve-catalog-images')) {
            return;
        }

        $directory = trim((string) $this->option('catalog-images'), '/\\');
        $sourceUrl = trim((string) $this->option('catalog-source'));
        if ($directory === '' || ! filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Reviewed catalogue images require a directory and a valid source URL.');
        }

        $filename = Str::lower($item->sku).'.png';
        $relativePath = $directory.'/'.$filename;
        if (! Storage::disk('public')->exists($relativePath)) {
            return;
        }

        $absolutePath = Storage::disk('public')->path($relativePath);
        $size = @getimagesize($absolutePath);
        if (! is_array($size) || $size[0] < 220 || $size[1] < 220) {
            throw new \RuntimeException("Reviewed catalogue image for {$item->sku} is invalid or too small.");
        }

        $publicPath = Storage::url($relativePath);
        $sourceDomain = Str::lower((string) parse_url($sourceUrl, PHP_URL_HOST));

        $item->imageAssets()->where('is_selected', true)->update([
            'is_selected' => false,
            'is_main' => false,
        ]);

        ProductParserImageAsset::updateOrCreate(
            ['parser_item_id' => $item->id, 'source_url' => $publicPath],
            [
                'source_domain' => $sourceDomain,
                'status' => 'found',
                'is_selected' => true,
                'is_main' => true,
                'needs_review' => false,
                'error_message' => null,
            ],
        );

        $item->forceFill([
            'official_source_url' => $sourceUrl,
            'official_source_domain' => $sourceDomain,
            'official_source_confidence' => 95,
            'fallback_source_url' => null,
            'fallback_source_domain' => null,
            'fallback_source_used' => false,
            'source_match_confidence' => 95,
            'source_urls_json' => array_values(array_unique(array_filter([
                ...($item->source_urls_json ?: []),
                $sourceUrl,
            ]))),
            'found_images_json' => [$publicPath],
            'selected_images_json' => [$publicPath],
            'content_source_type' => 'official_manufacturer_catalog',
            'image_source_type' => 'official_manufacturer_catalog',
            'needs_source_review' => false,
            'needs_image_review' => false,
            'source_reviewed_at' => now(),
        ])->save();
    }

    private function batch(): ?ProductParserBatch
    {
        $batchId = (int) $this->option('batch');

        return $batchId > 0
            ? ProductParserBatch::find($batchId)
            : ProductParserBatch::latest('id')->first();
    }

    private function keepOnlyUsableSelectedImages(ProductParserItem $item): void
    {
        $item->imageAssets()
            ->where('is_selected', true)
            ->where('status', '!=', 'processed')
            ->update(['is_selected' => false, 'is_main' => false, 'needs_review' => true]);

        $selected = $item->imageAssets()
            ->where('is_selected', true)
            ->where('status', 'processed')
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get();

        if ($selected->isNotEmpty() && ! $selected->contains(fn ($asset) => $asset->is_main && filled($asset->thumb_path))) {
            $main = $selected->first(fn ($asset) => filled($asset->thumb_path));
            if ($main) {
                $item->imageAssets()->update(['is_main' => false]);
                $main->forceFill(['is_main' => true])->save();
            }
        }

        $item->forceFill([
            'processed_images_json' => $item->imageAssets()
                ->where('is_selected', true)
                ->where('status', 'processed')
                ->orderByDesc('is_main')
                ->orderBy('id')
                ->pluck('processed_path')
                ->filter()
                ->values()
                ->all(),
        ])->save();
    }

    private function selectPrimaryImage(ProductParserItem $item): void
    {
        $primary = $item->imageAssets
            ->reject(fn ($asset) => str_ends_with(strtolower((string) parse_url($asset->source_url, PHP_URL_PATH)), '.svg'))
            ->sortByDesc('is_main')
            ->first();

        $item->imageAssets()->update(['is_selected' => false, 'is_main' => false]);
        if ($primary) {
            $primary->refresh();
            $primary->forceFill(array_filter([
                'is_selected' => true,
                'is_main' => true,
                'status' => $primary->status === 'processed' ? 'processed' : 'found',
                'error_message' => $primary->status === 'processed' ? $primary->error_message : null,
            ], fn ($value) => $value !== null))->save();
        }
    }
}
