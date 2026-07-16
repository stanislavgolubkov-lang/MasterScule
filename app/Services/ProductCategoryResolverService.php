<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ProductParserItem;

class ProductCategoryResolverService
{
    public function __construct(
        private ProductCategoryDetector $detector,
        private ProductCategoryLearningService $learning,
        private ProductCategoryProvisioningService $provisioning,
        private TrisToolsEnrichmentService $trisTools,
        private ProductParserSettings $settings,
    ) {}

    public function resolve(ProductParserItem $item): bool
    {
        $detected = $this->detector->detect(
            $item->sku,
            (string) ($item->parsed_name ?: $item->raw_name),
            $item->brand,
            $item->detected_group,
            $item->detected_subgroup,
            $item->vehicle_application,
        );

        if (! $detected['needs_review'] && $this->apply($item, $detected)) {
            return true;
        }

        $result = $this->trisTools->enrich($item->sku, $item->brand);
        $minimum = (int) $this->settings->get('min_fallback_confidence', 80);
        if (! ($result['found'] ?? false) || (int) ($result['confidence'] ?? 0) < $minimum) {
            $this->appendNote($item, 'TrisTool category lookup did not return a confident exact SKU match.');

            return false;
        }

        return $this->resolveFromSourceResult($item, $result);
    }

    public function resolveFromSourceResult(ProductParserItem $item, array $result): bool
    {
        if (! ($result['found'] ?? false)) {
            return false;
        }

        $minimum = max(
            90,
            (int) $this->settings->get('min_confidence_score', 90),
        );
        if ((int) ($result['source_match_confidence'] ?? $result['confidence'] ?? 0) < $minimum) {
            $this->appendNote($item, "Source confidence is below the required {$minimum}%.");

            return false;
        }

        $detected = $this->detector->detectFromTrisTools(
            $item->sku,
            (string) ($result['title'] ?? $item->parsed_name ?? $item->raw_name),
            $item->brand,
            $result['breadcrumb'] ?? [],
            $result['description'] ?? null,
            $result['specs'] ?? [],
        );

        $category = Category::find($detected['category_id'] ?? null);
        if (! $category || ($detected['needs_review'] ?? true)) {
            $suggestedParent = Category::find($detected['detected_category_id'] ?? null) ?: $category;
            $category = $this->provisioning->resolveOrCreate($result, $suggestedParent);
            if ($category) {
                $detected = [
                    'category_id' => $category->id,
                    'detected_category_id' => $category->id,
                    'detected_category_path' => $this->categoryPath($category),
                    'confidence' => max(90, min(98, (int) ($result['confidence'] ?? 90))),
                    'method' => 'tristools_category_created',
                    'notes' => ['Category resolved/created from TrisTool and verified source breadcrumbs.'],
                    'needs_review' => false,
                ];
            }
        }

        if (! $category || $detected['needs_review'] || ! $this->apply($item, $detected)) {
            $this->appendNote($item, 'TrisTool product was found, but its category could not be mapped or created safely.');

            return false;
        }

        $this->learning->learnFromItem(
            $item->fresh(),
            $category,
            'tristools',
            min((int) $detected['confidence'], (int) $result['confidence']),
            $result['breadcrumb'] ?? [],
        );

        $item->batch?->addLog('Parser learned category from TrisTool', [
            'sku' => $item->sku,
            'category' => $category->slug,
            'group' => $item->detected_group,
            'subgroup' => $item->detected_subgroup,
            'breadcrumb' => $result['breadcrumb'] ?? [],
        ]);

        return true;
    }

    private function categoryPath(Category $category): string
    {
        $parts = [];
        $current = $category;

        while ($current) {
            array_unshift($parts, $current->display_name);
            $current = $current->parent;
        }

        return implode(' > ', $parts);
    }

    private function apply(ProductParserItem $item, array $detected): bool
    {
        if (! filled($detected['category_id'] ?? null)) {
            return false;
        }

        $item->forceFill([
            'category_id' => $detected['category_id'],
            'detected_category_id' => $detected['detected_category_id'],
            'detected_category_path' => $detected['detected_category_path'],
            'category_confidence_score' => $detected['confidence'],
            'category_detection_method' => $detected['method'],
            'category_detection_notes_json' => array_values(array_unique(array_merge(
                $item->category_detection_notes_json ?: [],
                $detected['notes'] ?? [],
            ))),
            'needs_category_review' => false,
            'status' => 'searching',
        ])->save();

        return true;
    }

    private function appendNote(ProductParserItem $item, string $note): void
    {
        $item->forceFill([
            'category_detection_notes_json' => array_values(array_unique(array_merge(
                $item->category_detection_notes_json ?: [],
                [$note],
            ))),
        ])->save();
    }
}
