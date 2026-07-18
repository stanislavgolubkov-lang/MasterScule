<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

class CatalogStorefrontNavigation
{
    private ?array $visibleIds = null;

    public function mainSections(): Collection
    {
        $root = Category::where('slug', config('catalog_taxonomy.root'))->first();
        $slugs = config('catalog_taxonomy.main_sections', []);

        return $this->prune(Category::with('childrenRecursive')
            ->where('is_active', true)
            ->where('is_menu_visible', true)
            ->when($root, fn ($query) => $query->where('parent_id', $root->id))
            ->when(! $root, fn ($query) => $query->whereNull('parent_id'))
            ->whereIn('slug', $slugs)
            ->get())
            ->sortBy(fn (Category $category) => array_search($category->slug, $slugs, true))
            ->values();
    }

    public function roots(): Collection
    {
        return Category::with('childrenRecursive')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->get()
            ->map(function (Category $root): Category {
                $root->setRelation('childrenRecursive', $this->prune($root->childrenRecursive));

                return $root;
            })
            ->filter(fn (Category $root) => $root->slug === config('catalog_taxonomy.root')
                ? $root->childrenRecursive->isNotEmpty()
                : $this->isVisible($root))
            ->values();
    }

    public function children(Category $category): Collection
    {
        $category->loadMissing('childrenRecursive');

        return $this->prune($category->childrenRecursive)
            ->sortBy('sort_order')
            ->values();
    }

    public function isVisible(Category $category): bool
    {
        return $category->is_active
            && ($category->slug === config('catalog_taxonomy.root') || $category->is_menu_visible)
            && in_array($category->id, $this->visibleIds(), true);
    }

    public function nearestVisibleAncestor(?Category $category): ?Category
    {
        while ($category) {
            if ($this->isVisible($category) && $category->slug !== config('catalog_taxonomy.root')) {
                return $category;
            }
            $category = $category->parent;
        }

        return null;
    }

    private function prune(Collection $categories): Collection
    {
        return $categories
            ->filter(fn (Category $category) => $this->isVisible($category))
            ->map(function (Category $category): Category {
                $category->setRelation('childrenRecursive', $this->prune(
                    collect($category->childrenRecursive ?? [])
                ));

                return $category;
            })
            ->values();
    }

    private function visibleIds(): array
    {
        if ($this->visibleIds !== null) {
            return $this->visibleIds;
        }

        $parents = Category::pluck('parent_id', 'id')->all();
        $visible = [];

        foreach (Product::availableForSale()->whereNotNull('category_id')->pluck('category_id')->unique() as $categoryId) {
            $current = (int) $categoryId;
            $seen = [];
            while ($current && array_key_exists($current, $parents) && ! isset($seen[$current])) {
                $visible[$current] = true;
                $seen[$current] = true;
                $current = (int) ($parents[$current] ?? 0);
            }
        }

        return $this->visibleIds = array_map('intval', array_keys($visible));
    }
}
