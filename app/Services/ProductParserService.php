<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductParserItem;
use App\Models\ProductParserSource;
use Illuminate\Support\Str;
use Throwable;

class ProductParserService
{
    public function __construct(
        private ProductSearchService $search,
        private ProductImageCollectorService $imageCollector,
        private ProductParserItemPreparationService $preparation,
        private ProductParserContentBuilder $contentBuilder,
        private ProductTranslationService $translation,
        private ProductCategoryResolverService $categoryResolver,
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
            $existingProductId = Product::where('sku', $item->sku)->value('id');
            $result = match (true) {
                $forceFallback => $this->search->searchFallbackForParser($item->sku, $item->brand),
                $officialOnly => $this->search->searchOfficialForParser($item->sku, $item->brand),
                default => $this->search->searchForParser(
                    $item->sku,
                    $item->brand,
                    $options['language'] ?? 'auto',
                    preferLocal: false,
                    name: $item->raw_name,
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

            if (($result['found'] ?? false) && ! filled($result['existing_product_id'] ?? null)) {
                $this->categoryResolver->resolveFromSourceResult($item, $result);
                $item->refresh();
            }

            $categoryId = $item->category_id ?: ($result['category_id'] ?? null);
            $category = $categoryId ? Category::find($categoryId) : null;
            $translated = $this->translation->bilingual($result);
            $content = $this->contentBuilder->ensureComplete([
                'name_ru' => $translated['name_ru'],
                'name_ro' => $translated['name_ro'],
                'short_description_ru' => $translated['short_description_ru'],
                'short_description_ro' => $translated['short_description_ro'],
                'description_ru' => $translated['description_ru'],
                'description_ro' => $translated['description_ro'],
                'needs_translation_review' => ! $translated['complete'],
                'needs_content_review' => ! filled($result['description'] ?? null),
                'generated_content' => false,
                'translation_source_type' => $translated['translation_source_type'],
            ], $item->sku, (string) ($item->raw_name ?: $result['title'] ?: $item->sku), $item->brand, null, [
                'category_slug' => $category?->slug,
                'category_name_ru' => $category?->name,
                'category_name_ro' => $category?->name_ro,
            ]);
            $images = $result['images'] ?? [];
            $imageSourceDomain = parse_url($images[0] ?? '', PHP_URL_HOST)
                ?: ($result['official_source_domain'] ?? null);
            $this->imageCollector->collect($item, $images, $imageSourceDomain);
            $images = $item->imageAssets()->pluck('source_url')->values()->all();
            $ready = ($result['found'] ?? false)
                && ! ($result['needs_source_review'] ?? true)
                && filled($categoryId)
                && $translated['complete']
                && filled($result['description'] ?? null)
                && $images !== [];

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
                'status' => ! ($result['found'] ?? false)
                    ? 'needs_manual_review'
                    : ($ready ? 'ready_for_review' : 'needs_manual_review'),
                'confidence_score' => $result['confidence'] ?? 0,
                'found_title' => $result['title'] ?? null,
                'found_description' => $result['description'] ?? null,
                'found_specs_json' => array_filter(($result['specs'] ?? []) + [
                    '_package_contents' => $result['package_contents'] ?? [],
                    '_breadcrumb' => $result['breadcrumb'] ?? [],
                    '_automation_attempts' => $result['automation_attempts'] ?? 1,
                    '_automation_exhausted' => $result['automation_exhausted'] ?? false,
                ]),
                'found_images_json' => $images,
                'selected_images_json' => collect($images)->take(1)->values()->all(),
                'source_urls_json' => $result['source_urls'] ?? [],
                'existing_product_id' => $result['existing_product_id'] ?? $existingProductId,
                'error_message' => ($result['found'] ?? false)
                    ? collect($result['warnings'] ?? [])->implode(' ')
                    : 'All automatic TrisTool and external-source recovery attempts were exhausted.',
                'official_source_url' => $result['official_source_url'] ?? null,
                'official_source_domain' => $result['official_source_domain'] ?? null,
                'official_source_confidence' => $result['official_source_confidence'] ?? null,
                'fallback_source_url' => $result['fallback_source_url'] ?? null,
                'fallback_source_domain' => $result['fallback_source_domain'] ?? null,
                'fallback_source_used' => (bool) ($result['fallback_source_used'] ?? false),
                'tristools_url' => collect($result['sources'] ?? [])
                    ->first(fn (array $source) => str_contains((string) ($source['domain'] ?? ''), 'tristool.md'))['url']
                        ?? null,
                'tristools_match_confidence' => $result['confidence'] ?? null,
                'source_match_confidence' => $result['source_match_confidence'] ?? ($result['confidence'] ?? 0),
                'needs_source_review' => (bool) ($result['needs_source_review'] ?? true),
                'needs_content_review' => ! filled($result['description'] ?? null),
                'generated_content' => false,
                'content_source_type' => $result['content_source_type'] ?? null,
                'image_source_type' => $result['image_source_type'] ?? null,
                'translation_source_type' => $translated['translation_source_type'],
                'needs_image_review' => $images === [],
                'name_ru' => $content['name_ru'],
                'name_ro' => $content['name_ro'],
                'short_description_ru' => $content['short_description_ru'],
                'short_description_ro' => $content['short_description_ro'],
                'description_ru' => $content['description_ru'],
                'description_ro' => $content['description_ro'],
                'needs_translation_review' => ! $translated['complete'],
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

            if (($result['found'] ?? false) && $images) {
                $this->preparation->prepare($item->fresh(['imageAssets', 'batch']), $processImages);
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

        $failed = $statuses->contains(fn ($status) => in_array($status, ['failed', 'not_found', 'needs_manual_review', 'rejected'], true));
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

    private function inferCategoryIdFromBreadcrumb(array $breadcrumb): ?int
    {
        $text = Str::lower(implode(' ', $breadcrumb));

        $slug = match (true) {
            Str::contains($text, ['рихтов', 'покраск', 'tinichigerie', 'richtuire']) => 'tinichigerie-si-richtuire',
            default => null,
        };

        return $slug ? Category::where('slug', $slug)->value('id') : null;
    }
}
