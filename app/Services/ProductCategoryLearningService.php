<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ProductParserCategoryLearning;
use App\Models\ProductParserItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductCategoryLearningService
{
    public function resolve(
        string $sku,
        ?string $brand = null,
        ?string $group = null,
        ?string $subgroup = null,
    ): ?array {
        $candidates = array_filter([
            ['sku', $sku, 99],
        ], fn (array $candidate) => filled($candidate[1]));

        foreach ($candidates as [$type, $value, $confidence]) {
            if ($learning = $this->bestLearning($type, (string) $value, $brand)) {
                return [
                    'category' => $learning->category,
                    'confidence' => max($confidence, (int) $learning->confidence),
                    'method' => 'learned_'.$type,
                    'note' => "learned {$type}: {$learning->key_value} -> {$learning->category->slug}",
                ];
            }
        }

        return null;
    }

    public function resolveBreadcrumb(array $breadcrumb, ?string $brand = null): ?array
    {
        return null;
    }

    public function learnFromItem(
        ProductParserItem $item,
        Category $category,
        string $source,
        int $confidence,
        array $breadcrumb = [],
    ): void {
        if (! in_array($source, ['admin_verified', 'catalog_agent_verified'], true)) {
            return;
        }

        $brand = $item->brand;
        $context = array_filter([
            'sku' => $item->sku,
            'group' => $item->detected_group,
            'subgroup' => $item->detected_subgroup,
            'breadcrumb' => $breadcrumb,
        ]);

        $this->record('sku', $item->sku, $brand, $category, $source, $confidence, $context);

        if (filled($item->detected_subgroup) && $this->reusableSubgroup($item->detected_subgroup)) {
            $this->record('subgroup', $item->detected_subgroup, $brand, $category, $source, $confidence, $context);
        } elseif (filled($item->detected_group) && ($category->children()->exists() || $category->parent_id === null)) {
            $this->record('group', $item->detected_group, $brand, $category, $source, $confidence, $context);
        }

        $breadcrumbValue = $this->breadcrumbValue($breadcrumb);
        if ($breadcrumbValue !== '') {
            $this->record('tristools_breadcrumb', $breadcrumbValue, $brand, $category, $source, $confidence, $context);
        }
    }

    private function record(
        string $type,
        string $value,
        ?string $brand,
        Category $category,
        string $source,
        int $confidence,
        array $context,
    ): void {
        $normalized = $this->normalize($value);
        if ($normalized === '') {
            return;
        }

        $learning = ProductParserCategoryLearning::firstOrCreate(
            [
                'key_type' => $type,
                'key_hash' => sha1($normalized),
                'brand_key' => $this->brandKey($brand),
                'category_id' => $category->id,
            ],
            [
                'key_value' => trim($value),
                'source' => $source,
                'confidence' => min(100, $confidence),
                'observations' => 0,
                'context_json' => $context,
                'last_seen_at' => now(),
            ],
        );

        $learning->forceFill([
            'key_value' => trim($value),
            'source' => $source,
            'confidence' => max((int) $learning->confidence, min(100, $confidence)),
            'context_json' => $context,
            'last_seen_at' => now(),
        ])->save();
        $learning->increment('observations');
    }

    private function bestLearning(string $type, string $value, ?string $brand): ?ProductParserCategoryLearning
    {
        $normalized = $this->normalize($value);
        if ($normalized === '') {
            return null;
        }

        /** @var Collection<int, ProductParserCategoryLearning> $matches */
        $matches = ProductParserCategoryLearning::with('category')
            ->where('key_type', $type)
            ->where('key_hash', sha1($normalized))
            ->whereIn('brand_key', array_unique([$this->brandKey($brand), '*']))
            ->whereIn('source', ['admin_verified', 'catalog_agent_verified'])
            ->orderByDesc('observations')
            ->orderByDesc('confidence')
            ->get()
            ->filter(fn (ProductParserCategoryLearning $learning) => $learning->category?->is_active);

        $best = $matches->first();
        $second = $matches->skip(1)->first();

        if (! $best) {
            return null;
        }

        if ($second
            && $second->category_id !== $best->category_id
            && (int) $second->observations >= (int) $best->observations) {
            return null;
        }

        return $best;
    }

    private function breadcrumbValue(array $breadcrumb): string
    {
        return trim(implode(' > ', array_filter(array_map(
            fn ($value) => trim((string) $value),
            $breadcrumb,
        ))));
    }

    private function normalize(string $value): string
    {
        $value = Str::lower(trim($value));

        return trim((string) preg_replace('/[^\pL\pN]+/u', ' ', $value));
    }

    private function brandKey(?string $brand): string
    {
        $brand = $this->normalize((string) $brand);

        return $brand !== '' ? $brand : '*';
    }

    private function reusableSubgroup(string $subgroup): bool
    {
        $subgroup = $this->normalize($subgroup);

        if (Str::contains($subgroup, [
            'разное',
            'пневматический инструмент',
            'ручной инструмент',
            'инструменты универсальные',
        ])) {
            return false;
        }

        return ! collect([
            'разное',
            'squad',
            'пневматический инструмент',
            'ручной инструмент',
            'инструменты универсальные',
        ])->contains(fn (string $generic) => Str::contains($subgroup, $this->normalize($generic)));
    }
}
