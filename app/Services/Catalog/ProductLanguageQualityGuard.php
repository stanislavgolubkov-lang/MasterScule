<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductLanguageQualityGuard
{
    public function __construct(private readonly ProductContentLanguage $language) {}

    public function evaluate(Product $product): array
    {
        $errors = [];
        $ru = trim(implode(' ', array_filter([$product->name_ru ?: $product->name, $product->description_ru ?: $product->description])));
        $ro = trim(implode(' ', array_filter([$product->name_ro, $product->description_ro])));

        if (! filled($product->name_ru ?: $product->name) || ! filled($product->description_ru ?: $product->description)) {
            $errors['language_missing_ru'] = 'Russian name or description is missing.';
        }
        if ($ru !== '' && preg_match('/\p{Cyrillic}/u', $ru) !== 1) {
            $errors['language_ru_missing_cyrillic'] = 'Russian fields do not contain Russian text.';
        }
        if ($this->language->containsUkrainian($ru.' '.$ro)) {
            $errors['language_ukrainian_not_supported'] = 'Ukrainian content is not allowed; storefront content must be Russian and Romanian.';
        }
        if (! filled($product->name_ro) || ! filled($product->description_ro)) {
            $errors['language_missing_ro'] = 'Romanian name or description is missing.';
        }
        if ($ro !== '' && preg_match('/\p{Cyrillic}/u', $ro) === 1) {
            $errors['language_ro_contains_cyrillic'] = 'Romanian fields contain Cyrillic characters.';
        }
        $packageContents = collect($product->package_contents ?? [])->implode(' ');

        if (Str::contains(Str::lower($ru.' '.$ro.' '.$packageContents), ['lorem ipsum', 'unknown product', 'draft parser preview', 'todo', 'tbd'])) {
            $errors['language_placeholder_text'] = 'Product content contains placeholder text.';
        }
        if (Str::contains(Str::lower($ru.' '.$ro), ['voluntari', 'romania', ' ron '])) {
            $errors['language_foreign_store_artifact'] = 'Product content contains foreign store artifacts.';
        }

        return ['allowed' => $errors === [], 'errors' => $errors];
    }
}
