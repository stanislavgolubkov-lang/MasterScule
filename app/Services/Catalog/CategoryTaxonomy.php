<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryTaxonomy
{
    private ?Collection $assignableCache = null;

    public function version(): string
    {
        return (string) config('catalog_ai.taxonomy_version', '2026-07-17');
    }

    public function rootSlug(): string
    {
        return (string) config('catalog_taxonomy.root', 'instrumente-si-mobilier');
    }

    public function mainSectionSlugs(): array
    {
        return array_values(config('catalog_taxonomy.main_sections', []));
    }

    public function assignable(): Collection
    {
        if ($this->assignableCache !== null) {
            return $this->assignableCache;
        }

        $excluded = collect(config('catalog_taxonomy.non_assignable', []))
            ->merge(array_keys(config('catalog_taxonomy.aliases', [])))
            ->unique()
            ->all();

        return $this->assignableCache = Category::with('parent.parent.parent.parent')
            ->where('is_active', true)
            ->where('is_assignable', true)
            ->when($excluded !== [], fn ($query) => $query->whereNotIn('slug', $excluded))
            ->orderBy('slug')
            ->get();
    }

    public function resolveAlias(?string $slug): ?string
    {
        if (! $slug) {
            return null;
        }

        return config('catalog_taxonomy.aliases.'.$slug, $slug);
    }

    public function findAssignable(string $slug): ?Category
    {
        $slug = $this->resolveAlias($slug) ?: $slug;

        return $this->assignable()->firstWhere('slug', $slug);
    }

    public function path(Category $category): string
    {
        $parts = [];
        $seen = [];

        while ($category && ! isset($seen[$category->id])) {
            $seen[$category->id] = true;
            array_unshift($parts, $category->display_name);
            $category = $category->parent;
        }

        return implode(' > ', $parts);
    }

    public function isInBranch(Category $category, string $branchSlug): bool
    {
        $seen = [];

        while ($category && ! isset($seen[$category->id])) {
            if ($category->slug === $branchSlug) {
                return true;
            }

            $seen[$category->id] = true;
            $category = $category->parent;
        }

        return false;
    }

    public function payload(Collection $categories): array
    {
        return $categories->map(fn (Category $category) => [
            'slug' => $category->slug,
            'name_ru' => $category->name,
            'name_ro' => $category->name_ro,
            'path' => $this->path($category),
            'description' => $category->description,
        ])->values()->all();
    }

    public function syncStructure(bool $apply = false): array
    {
        $changes = [];
        $version = $this->version();
        $mainSections = $this->mainSectionSlugs();
        $nonAssignable = collect(config('catalog_taxonomy.non_assignable', []))
            ->merge(array_keys(config('catalog_taxonomy.aliases', [])))
            ->unique();

        foreach (config('catalog_taxonomy.parent_overrides', []) as $slug => $parentSlug) {
            $category = Category::where('slug', $slug)->first();
            $parent = Category::where('slug', $parentSlug)->first();

            if (! $category || ! $parent || $category->parent_id === $parent->id) {
                continue;
            }

            $changes[] = ['slug' => $slug, 'field' => 'parent', 'from' => $category->parent?->slug, 'to' => $parentSlug];
            if ($apply) {
                $category->forceFill(['parent_id' => $parent->id])->save();
            }
        }

        foreach (Category::all() as $category) {
            $next = [
                'is_assignable' => ! $nonAssignable->contains($category->slug),
                'is_menu_visible' => $category->slug !== $this->rootSlug()
                    && ! $nonAssignable->contains($category->slug),
                'source' => str_contains((string) $category->description, 'добавлена парсером') ? 'parser' : ($category->source ?: 'catalog'),
                'taxonomy_version' => $version,
            ];

            if ($category->slug === $this->rootSlug()) {
                $next['is_assignable'] = false;
            }

            if ($category->parent?->slug === $this->rootSlug() && ! in_array($category->slug, $mainSections, true)) {
                $next['is_menu_visible'] = false;
            }

            foreach ($next as $field => $value) {
                if ($category->{$field} !== $value) {
                    $changes[] = ['slug' => $category->slug, 'field' => $field, 'from' => $category->{$field}, 'to' => $value];
                }
            }

            if ($apply) {
                $category->forceFill($next)->save();
            }
        }

        if ($apply) {
            $this->assignableCache = null;
        }

        return $changes;
    }
}
