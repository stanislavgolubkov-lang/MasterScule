<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductCategoryDetector
{
    public function __construct(private ProductParserSettings $settings)
    {
    }

    public function detect(string $sku, string $name, ?string $brand = null, ?string $group = null, ?string $subgroup = null): array
    {
        $rules = $this->settings->get('category_rules', config('product_parser.category_rules', []));
        $text = $this->normalize(implode(' ', array_filter([$sku, $name, $brand, $group, $subgroup])));
        $scores = [];
        $notes = [];

        foreach (($rules['group_mapping'] ?? []) as $needle => $slug) {
            if ($this->contains($text, $needle)) {
                $scores[$slug] = ($scores[$slug] ?? 0) + 45;
                $notes[] = "group: {$needle} -> {$slug}";
            }
        }

        foreach (($rules['sku_prefixes'] ?? []) as $pattern => $slug) {
            if ($this->skuMatches($sku, $pattern)) {
                $scores[$slug] = ($scores[$slug] ?? 0) + 35;
                $notes[] = "sku: {$pattern} -> {$slug}";
            }
        }

        foreach (($rules['keywords'] ?? []) as $slug => $keywords) {
            foreach ($keywords as $keyword) {
                if ($this->contains($text, $keyword)) {
                    $scores[$slug] = ($scores[$slug] ?? 0) + 18;
                    $notes[] = "keyword: {$keyword} -> {$slug}";
                }
            }
        }

        if ($similar = $this->similarProduct($sku, $brand)) {
            $slug = $similar->category?->slug;
            if ($slug) {
                $scores[$slug] = ($scores[$slug] ?? 0) + 28;
                $notes[] = "similar SKU {$similar->sku} -> {$slug}";
            }
        }

        if ($brand && Str::contains(Str::lower($brand), ['m7', 'mighty seven'])) {
            $scores['scule-pneumatice'] = ($scores['scule-pneumatice'] ?? 0) + 8;
            $notes[] = 'brand: M7 gives pneumatic hint';
        }

        arsort($scores);
        $slug = array_key_first($scores);
        $score = $slug ? min(98, (int) $scores[$slug]) : 0;
        $category = $slug ? Category::where('slug', $slug)->first() : null;
        $min = (int) ($rules['min_confidence'] ?? $this->settings->get('min_confidence_score', 70));

        return [
            'category_id' => $score >= $min ? $category?->id : null,
            'detected_category_id' => $category?->id,
            'detected_category_path' => $category ? $this->path($category) : null,
            'confidence' => $score,
            'method' => $notes ? 'rules' : 'none',
            'notes' => $notes,
            'needs_review' => ! $category || $score < $min,
        ];
    }

    private function similarProduct(string $sku, ?string $brand): ?Product
    {
        $family = preg_replace('/\d{1,3}[a-z]*$/iu', '', trim($sku));
        $family = $family && mb_strlen($family) >= 3 ? $family : mb_substr($sku, 0, 4);

        return Product::with(['category', 'brand'])
            ->where('sku', '!=', $sku)
            ->where('sku', 'like', $family.'%')
            ->when($brand, fn ($query) => $query->whereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', '%'.$brand.'%')))
            ->first();
    }

    private function path(Category $category): string
    {
        $parts = [];
        $current = $category;

        while ($current) {
            array_unshift($parts, $current->display_name);
            $current = $current->parent;
        }

        return implode(' > ', $parts);
    }

    private function skuMatches(string $sku, string $pattern): bool
    {
        $sku = Str::upper(trim($sku));
        $pattern = Str::upper(trim($pattern));

        if (Str::startsWith($pattern, '*')) {
            return Str::endsWith($sku, ltrim($pattern, '*'));
        }

        if (Str::endsWith($pattern, '*')) {
            return Str::startsWith($sku, rtrim($pattern, '*'));
        }

        return Str::startsWith($sku, $pattern);
    }

    private function contains(string $text, string $needle): bool
    {
        return Str::contains($text, $this->normalize($needle));
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        return preg_replace('/\s+/u', ' ', $value) ?: '';
    }
}
