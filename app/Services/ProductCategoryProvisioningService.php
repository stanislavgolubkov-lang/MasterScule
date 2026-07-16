<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class ProductCategoryProvisioningService
{
    public function __construct(private ProductTranslationService $translation) {}

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

        if ($existing) {
            return $existing;
        }

        $nameRu = preg_match('/\p{Cyrillic}/u', $leaf) === 1
            ? $leaf
            : $this->translation->translate($leaf, 'ru');
        $nameRo = preg_match('/\p{Cyrillic}/u', $leaf) !== 1
            ? $leaf
            : $this->translation->translate($leaf, 'ro');
        $nameRu = $nameRu ?: $leaf;
        $nameRo = $nameRo ?: $leaf;

        $slugBase = Str::slug(Str::ascii($nameRo ?: $nameRu));
        $slugBase = $slugBase !== '' ? $slugBase : 'tristool-'.substr(sha1($leaf), 0, 12);
        $slug = $slugBase;
        $suffix = 2;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$suffix++;
        }

        return Category::create([
            'parent_id' => $suggestedParent?->id,
            'name' => $nameRu,
            'name_ro' => $nameRo,
            'slug' => $slug,
            'description' => 'Категория добавлена парсером по структуре TrisTool.',
            'description_ro' => 'Categorie adaugata automat din structura TrisTool.',
            'sort_order' => ((int) Category::where('parent_id', $suggestedParent?->id)->max('sort_order')) + 10,
            'is_active' => true,
        ]);
    }

    private function leaf(array $breadcrumb): ?string
    {
        return collect($breadcrumb)
            ->map(fn ($value) => trim((string) $value))
            ->reject(fn (string $value) => $value === '' || Str::contains(Str::lower($value), [
                'главная',
                'home',
                'catalog',
                'каталог оборудования',
                'instrument si mobilier',
                'инструмент и мебель',
            ]))
            ->last();
    }

    private function normalize(string $value): string
    {
        return trim((string) preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower($value, 'UTF-8')));
    }
}
