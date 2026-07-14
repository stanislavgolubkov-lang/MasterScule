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
        private ProductParserContentBuilder $contentBuilder,
    ) {}

    public function parseItem(ProductParserItem $item, bool $processImages = false, bool $officialOnly = false, bool $forceFallback = false): void
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
            $result = match (true) {
                $forceFallback => $this->search->searchFallbackForParser($item->sku, $item->brand),
                $officialOnly => $this->search->searchOfficialForParser($item->sku, $item->brand),
                default => $this->search->searchForParser(
                    $item->sku,
                    $item->brand,
                    $options['language'] ?? 'auto',
                    preferLocal: ! filled($item->created_product_id),
                ),
            };
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
            $category = $categoryId ? Category::find($categoryId) : null;
            $content = $this->contentBuilder->build(
                $item->sku,
                (string) ($item->raw_name ?: $result['title'] ?: $item->sku),
                $item->brand,
                null,
                [
                    'category_slug' => $category?->slug,
                    'category_name_ru' => $category?->name,
                    'category_name_ro' => $category?->name_ro,
                ],
            );
            $content = $this->contentBuilder->mergeOfficialContent(
                $content,
                $result['title'] ?? null,
                $result['description'] ?? null,
                $item->sku,
                $item->brand,
            );
            $images = $result['images'] ?? [];
            $this->imageCollector->collect($item, $images);

            $batch?->addLog('Source discovery completed', [
                'sku' => $item->sku,
                'official_url' => $result['official_source_url'] ?? null,
                'fallback_url' => $result['fallback_source_url'] ?? null,
                'confidence' => $result['source_match_confidence'] ?? ($result['confidence'] ?? 0),
                'fallback_used' => (bool) ($result['fallback_source_used'] ?? false),
                'images_found' => count($images),
                'content_source' => $result['content_source_type'] ?? null,
            ]);

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
                'official_source_url' => $result['official_source_url'] ?? null,
                'official_source_domain' => $result['official_source_domain'] ?? null,
                'official_source_confidence' => $result['official_source_confidence'] ?? null,
                'fallback_source_url' => $result['fallback_source_url'] ?? null,
                'fallback_source_domain' => $result['fallback_source_domain'] ?? null,
                'fallback_source_used' => (bool) ($result['fallback_source_used'] ?? false),
                'source_match_confidence' => $result['source_match_confidence'] ?? ($result['confidence'] ?? 0),
                'needs_source_review' => (bool) ($result['needs_source_review'] ?? true),
                'needs_content_review' => ! filled($result['description'] ?? null),
                'generated_content' => ! filled($result['description'] ?? null),
                'content_source_type' => $result['content_source_type'] ?? null,
                'image_source_type' => $result['image_source_type'] ?? null,
                'translation_source_type' => $result['translation_source_type'] ?? 'generated_pending_review',
                'name_ru' => $content['name_ru'],
                'name_ro' => $content['name_ro'],
                'short_description_ru' => $content['short_description_ru'],
                'short_description_ro' => $content['short_description_ro'],
                'description_ru' => $content['description_ru'],
                'description_ro' => $content['description_ro'],
                'needs_translation_review' => (bool) $content['needs_translation_review'],
                'needs_content_review' => (bool) $content['needs_content_review'],
                'generated_content' => (bool) $content['generated_content'],
            ])->save();

            $batch?->addLog('RU/RO content prepared', [
                'sku' => $item->sku,
                'source_language' => $content['source_language'] ?? null,
                'ru_ready' => filled($content['description_ru']),
                'ro_ready' => filled($content['description_ro']),
                'needs_translation_review' => (bool) $content['needs_translation_review'],
            ]);

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
