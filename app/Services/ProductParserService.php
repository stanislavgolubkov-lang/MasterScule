<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ProductParserItem;
use App\Models\ProductParserSource;
use Illuminate\Support\Str;
use Throwable;

class ProductParserService
{
    public function __construct(
        private ProductSearchService $search,
        private ProductImageCollectorService $imageCollector,
        private ProductImageProcessorService $imageProcessor,
    ) {
    }

    public function parseItem(ProductParserItem $item, bool $processImages = false): void
    {
        $batch = $item->batch;
        $batch?->forceFill([
            'status' => 'running',
            'started_at' => $batch->started_at ?: now(),
        ])->save();

        $item->forceFill(['status' => 'searching', 'error_message' => null])->save();
        $batch?->addLog('Started SKU search', ['sku' => $item->sku, 'brand' => $item->brand]);

        try {
            $options = $batch?->options_json ?: [];
            $result = $this->search->search($item->sku, $item->brand, $options['language'] ?? 'auto');
            ProductParserSource::where('parser_item_id', $item->id)->delete();

            foreach ($result['sources'] as $source) {
                ProductParserSource::create([
                    'parser_item_id' => $item->id,
                    'url' => $source['url'],
                    'domain' => $source['domain'] ?? parse_url($source['url'], PHP_URL_HOST),
                    'title' => $source['title'] ?? null,
                    'snippet' => $source['snippet'] ?? null,
                    'source_type' => $source['source_type'] ?? 'generic',
                    'confidence_score' => $source['confidence_score'] ?? null,
                    'raw_data_json' => $source['raw_data_json'] ?? null,
                ]);
            }

            $categoryId = $item->category_id ?: ($result['category_id'] ?? null) ?: $this->inferCategoryId((string) ($result['title'] ?? ''), $item->brand);
            $images = $result['images'] ?? [];
            $this->imageCollector->collect($item, $images);

            $item->forceFill([
                'category_id' => $categoryId,
                'status' => ($result['found'] ?? false) ? 'ready_for_review' : 'not_found',
                'confidence_score' => $result['confidence'] ?? 0,
                'found_title' => $result['title'] ?? null,
                'found_description' => $result['description'] ?? null,
                'found_specs_json' => $result['specs'] ?? [],
                'found_images_json' => $images,
                'selected_images_json' => collect($images)->take((int) ($options['image_limit'] ?? 4))->values()->all(),
                'source_urls_json' => $result['source_urls'] ?? [],
                'existing_product_id' => $result['existing_product_id'] ?? null,
                'error_message' => collect($result['warnings'] ?? [])->implode(' '),
            ])->save();

            $batch?->addLog(($result['found'] ?? false) ? 'SKU ready for review' : 'SKU not found', [
                'sku' => $item->sku,
                'confidence' => $item->confidence_score,
                'images' => count($images),
            ]);

            if ($processImages && ($result['found'] ?? false) && $images) {
                $this->imageProcessor->processSelected($item->fresh(['imageAssets', 'batch']));
            }
        } catch (Throwable $e) {
            $item->forceFill([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ])->save();
            $batch?->addLog('SKU parser failed', ['sku' => $item->sku, 'error' => $e->getMessage()]);
        }

        $this->refreshBatchStatus($item->batch);
    }

    public function refreshBatchStatus($batch): void
    {
        if (! $batch) {
            return;
        }

        $statuses = $batch->items()->pluck('status');

        if ($statuses->contains(fn ($status) => in_array($status, ['queued', 'searching', 'processing_images'], true))) {
            $batch->forceFill(['status' => 'running'])->save();

            return;
        }

        $failed = $statuses->contains(fn ($status) => in_array($status, ['failed', 'not_found', 'rejected'], true));
        $ready = $statuses->contains(fn ($status) => in_array($status, ['ready_for_review', 'approved'], true));

        $batch->forceFill([
            'status' => $failed && $ready ? 'partial' : ($failed ? 'failed' : 'completed'),
            'finished_at' => now(),
        ])->save();
    }

    private function inferCategoryId(string $title, ?string $brand): ?int
    {
        $lower = Str::lower($title.' '.$brand);
        $slug = match (true) {
            Str::contains($lower, ['pneumatic', 'impact', 'm7', 'aer comprimat']) => 'scule-pneumatice',
            Str::contains($lower, ['dinamometric', 'torque']) => 'chei-dinamometrice',
            Str::contains($lower, ['cric', 'ridicare', 'lift']) => 'cricuri-si-ridicare',
            Str::contains($lower, ['carucior', 'dulap', 'organizare']) => 'dulapuri-si-organizare',
            Str::contains($lower, ['compresor', 'compressor']) => 'compresoare',
            Str::contains($lower, ['tubular', 'clichet', 'socket', 'ratchet']) => 'tubulare-si-clichete',
            Str::contains($lower, ['set', 'trusa', 'kit']) => 'seturi-de-scule',
            default => 'instrument-manual',
        };

        return Category::where('slug', $slug)->value('id') ?: Category::orderBy('sort_order')->value('id');
    }
}
