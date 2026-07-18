<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class CategoryCandidateService
{
    public function __construct(private CategoryTaxonomy $taxonomy) {}

    public function rank(Product $product): array
    {
        $product->loadMissing(['brand', 'category', 'parserItem']);
        $input = $this->input($product);
        $text = $this->normalize(implode(' ', array_filter([
            $input['sku'],
            $input['brand'],
            $input['name_ru'],
            $input['name_ro'],
            $input['source_title'],
            $input['source_subgroup'],
            $input['source_category_path'],
            $input['vehicle_application'],
            implode(' ', $input['attributes']),
        ])));
        $titleText = $this->normalize(implode(' ', array_filter([
            $input['name_ru'],
            $input['name_ro'],
            $input['source_title'],
        ])));
        $sourcePath = $this->normalize((string) $input['source_category_path']);
        $categories = $this->taxonomy->assignable();
        $scores = [];
        $evidence = [];

        foreach ($categories as $category) {
            $scores[$category->slug] = 0;
            $evidence[$category->slug] = [];

            foreach ([(string) $category->name, (string) $category->name_ro] as $name) {
                $normalizedName = $this->normalize($name);
                if (mb_strlen($normalizedName) >= 5 && $this->containsPhrase($sourcePath, $normalizedName)) {
                    $scores[$category->slug] += 90;
                    $evidence[$category->slug][] = 'source breadcrumb matches category name';
                }
            }
        }

        foreach (config('catalog_taxonomy.rules', []) as $slug => $phrases) {
            $slug = $this->taxonomy->resolveAlias($slug) ?: $slug;
            if (! array_key_exists($slug, $scores)) {
                continue;
            }

            $matched = 0;
            foreach ($phrases as $phrase) {
                if ($this->containsPhrase($text, $this->normalize($phrase))) {
                    $matched++;
                    $scores[$slug] += $matched === 1 ? 150 : 45;
                    $evidence[$slug][] = 'phrase: '.$phrase;
                }
            }
        }

        foreach (config('catalog_taxonomy.compound_rules', []) as $rule) {
            $slug = $this->taxonomy->resolveAlias($rule['slug'] ?? null) ?: ($rule['slug'] ?? null);
            if (! $slug || ! array_key_exists($slug, $scores)) {
                continue;
            }

            $from = $rule['from'] ?? [];
            if ($from !== [] && ! in_array($product->category?->slug, $from, true)) {
                continue;
            }

            $signalText = ($rule['scope'] ?? 'title') === 'all' ? $text : $titleText;
            $all = collect($rule['all'] ?? [])->every(
                fn (string $fragment) => str_contains($signalText, $this->normalize($fragment))
            );
            $anyFragments = $rule['any'] ?? [];
            $any = $anyFragments === [] || $this->containsAnyFragment($signalText, $anyFragments);
            $none = ! $this->containsAnyFragment($signalText, $rule['none'] ?? []);

            if ($all && $any && $none) {
                $this->boost(
                    $scores,
                    $evidence,
                    $slug,
                    (int) ($rule['score'] ?? 200),
                    (string) ($rule['evidence'] ?? 'compound taxonomy signal'),
                );
            }
        }

        $electricalContext = $this->containsAnyFragment($text, [
            'диэлектр', 'изолирован', '1000v', '1000 v', 'vde', 'зачистк', 'кабелерез',
            'стриппер', 'обжимн', 'оптоволок', 'wire stripper', 'crimping pliers', 'fiber optic',
        ]);
        $pliersContext = $this->containsAnyFragment($text, [
            'плоскогуб', 'пассатиж', 'бокорез', 'кусач', 'клещ', 'кабелерез', 'стриппер',
            'зачистк', 'обжимн', 'оптоволок', 'pliers', 'cutter', 'stripper', 'crimp',
        ]);
        if ($electricalContext && $pliersContext) {
            $this->boost($scores, $evidence, 'clesti-electrician-si-cabluri', 90, 'electrical safety/application context');
        }

        $sku = Str::upper(trim((string) $input['sku']));
        $override = config('catalog_taxonomy.sku_overrides.'.$sku);
        if ($override) {
            $override = $this->taxonomy->resolveAlias($override) ?: $override;
            if (array_key_exists($override, $scores)) {
                $scores[$override] += 1000;
                $evidence[$override][] = 'verified SKU override: '.$sku;
            }
        }

        foreach (config('catalog_taxonomy.sku_prefixes', []) as $prefix => $slug) {
            if (! Str::startsWith($sku, Str::upper($prefix))) {
                continue;
            }

            $slug = $this->taxonomy->resolveAlias($slug) ?: $slug;
            if (array_key_exists($slug, $scores)) {
                $scores[$slug] += 65;
                $evidence[$slug][] = 'SKU family: '.$prefix;
            }
        }

        $currentSlug = $this->taxonomy->resolveAlias($product->category?->slug);
        if ($currentSlug && array_key_exists($currentSlug, $scores)) {
            $scores[$currentSlug] += 12;
            $evidence[$currentSlug][] = 'current category hint';
        }

        $brand = $this->normalize((string) $input['brand']);
        $brandFallback = match (true) {
            str_contains($brand, 'm7'), str_contains($brand, 'mighty seven') => 'scule-pneumatice',
            str_contains($brand, 'jtc') => 'scule-speciale-auto',
            str_contains($brand, 'hoegert') => 'instrument-manual',
            str_contains($brand, 'torin'), str_contains($brand, 'tongrun') => 'echipamente-pentru-service',
            default => null,
        };
        if ($brandFallback && array_key_exists($brandFallback, $scores)) {
            $scores[$brandFallback] += 8;
            $evidence[$brandFallback][] = 'brand family fallback';
        }

        arsort($scores);
        $limit = max(3, (int) config('catalog_ai.candidate_limit', 14));
        $ranked = collect($scores)
            ->take($limit)
            ->map(function (int $score, string $slug) use ($categories, $evidence) {
                /** @var Category $category */
                $category = $categories->firstWhere('slug', $slug);

                return [
                    'category' => $category,
                    'slug' => $slug,
                    'score' => $score,
                    'evidence' => array_values(array_unique($evidence[$slug] ?? [])),
                ];
            })
            ->values();

        $top = $ranked->get(0);
        $second = $ranked->get(1);
        $confidence = $this->confidence((int) ($top['score'] ?? 0), (int) ($second['score'] ?? 0));

        return [
            'input' => $input,
            'input_hash' => hash('sha256', json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'text' => $text,
            'candidates' => $ranked,
            'selected_slug' => $top['slug'] ?? $currentSlug,
            'confidence' => $confidence,
            'evidence' => $top['evidence'] ?? [],
        ];
    }

    public function input(Product $product): array
    {
        $product->loadMissing(['brand', 'parserItem']);
        $item = $product->parserItem;
        $attributes = collect($product->attributes ?: [])
            ->reject(fn ($value, $key) => $this->ignoredAttribute((string) $key))
            ->map(fn ($value, $key) => trim((string) $key).' '.trim(is_scalar($value) ? (string) $value : ''))
            ->filter()
            ->take(20)
            ->values()
            ->all();

        return [
            'sku' => $product->sku,
            'brand' => $product->brand?->name,
            'name_ru' => $product->name_ru ?: $product->name,
            'name_ro' => $product->name_ro,
            'source_title' => $item?->found_title ?: $item?->parsed_name ?: $item?->raw_name,
            'source_group' => $item?->detected_group,
            'source_subgroup' => $item?->detected_subgroup,
            'source_category_path' => $item?->detected_category_path,
            'vehicle_application' => $product->vehicle_application ?: $item?->vehicle_application,
            'attributes' => $attributes,
        ];
    }

    public function normalize(string $value): string
    {
        $value = Str::lower($value);
        $value = str_replace(['ё', 'ţ', 'ț', 'ş', 'ș'], ['е', 't', 't', 's', 's'], $value);

        return trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value));
    }

    public function containsPhrase(string $text, string $phrase): bool
    {
        if ($phrase === '') {
            return false;
        }

        return str_contains(' '.$text.' ', ' '.$phrase.' ');
    }

    public function containsAnyFragment(string $text, array $fragments): bool
    {
        return collect($fragments)->contains(
            fn (string $fragment) => str_contains($text, $this->normalize($fragment))
        );
    }

    private function confidence(int $top, int $second): float
    {
        $margin = $top - $second;

        return match (true) {
            $top >= 280 && $margin >= 80 => 0.995,
            $top >= 180 && $margin >= 60 => 0.985,
            $top >= 150 && $margin >= 40 => 0.970,
            $top >= 110 && $margin >= 30 => 0.940,
            $top >= 70 && $margin >= 20 => 0.880,
            default => 0.700,
        };
    }

    private function ignoredAttribute(string $key): bool
    {
        $key = $this->normalize($key);

        if (in_array($key, [
            'brand', 'бренд', 'marca', 'sku', 'артикул', 'cod produs', 'group', 'группа', 'grup',
            'retail price', 'price retail', 'розничная цена', 'цена розничная', 'pret retail',
            'price source', 'источник цены', 'sursa pretului', 'select all', 'выбрать все', 'selecteaza tot',
        ], true)) {
            return true;
        }

        return collect([
            'уровень шума', 'noise level', 'nivel de zgomot', 'вес', 'greutate', 'weight',
            'габариты', 'размер упаковки', 'dimensiuni', 'warranty', 'гарантия', 'garantie',
            'цена', 'price', 'pret retail', 'мощность звука',
        ])->contains(fn (string $ignored) => $this->containsPhrase($key, $this->normalize($ignored)));
    }

    private function containsAny(string $text, array $phrases): bool
    {
        return collect($phrases)->contains(
            fn (string $phrase) => $this->containsPhrase($text, $this->normalize($phrase))
        );
    }

    private function boost(array &$scores, array &$evidence, string $slug, int $points, string $reason): void
    {
        if (! array_key_exists($slug, $scores)) {
            return;
        }

        $scores[$slug] += $points;
        $evidence[$slug][] = $reason;
    }
}
