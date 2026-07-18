<?php

namespace App\Services;

use App\Models\Category;

class ProductCategoryProvisioningService
{
    public function resolveOrCreate(array $source, ?Category $suggestedParent = null): ?Category
    {
        $breadcrumbs = collect([
            $source['breadcrumb'] ?? [],
            $source['official_breadcrumb'] ?? [],
        ])->filter(fn ($items) => is_array($items) && $items !== []);

        $leaf = $breadcrumbs
            ->map(fn (array $items) => $this->leaf($items))
            ->first(fn (?string $value) => filled($value));

        if (! $leaf) {
            return null;
        }

        $normalizedLeaf = $this->normalize($leaf);
        $existing = Category::query()
            ->get()
            ->first(fn (Category $category) => in_array($normalizedLeaf, [
                $this->normalize((string) $category->name),
                $this->normalize((string) $category->name_ro),
            ], true));

        if ($existing && $existing->is_active && $existing->is_assignable) {
            return $existing;
        }

        // Unknown source breadcrumbs must never mutate the public taxonomy.
        // The canonical category agent handles the product after parsing.
        return $suggestedParent?->is_active && $suggestedParent?->is_assignable
            ? $suggestedParent
            : null;
    }

    private function leaf(array $breadcrumb): ?string
    {
        return collect($breadcrumb)
            ->map(fn ($value) => trim((string) $value))
            ->reject(fn (string $value) => $value === '' || str_contains(mb_strtolower($value), 'главная') || collect([
                'home',
                'catalog',
                'каталог оборудования',
                'instrument si mobilier',
                'инструмент и мебель',
            ])->contains(fn (string $ignored) => str_contains(mb_strtolower($value), $ignored)))
            ->last();
    }

    private function normalize(string $value): string
    {
        return trim((string) preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower($value, 'UTF-8')));
    }
}
