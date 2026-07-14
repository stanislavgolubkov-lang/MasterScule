<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductLanguageQualityGuard
{
    public function evaluate(Product $product): array
    {
        $errors = [];
        $ru = trim(implode(' ', array_filter([$product->name_ru ?: $product->name, $product->description_ru ?: $product->description])));
        $ro = trim(implode(' ', array_filter([$product->name_ro, $product->description_ro])));

        if (! filled($product->name_ru ?: $product->name) || ! filled($product->description_ru ?: $product->description)) {
            $errors['language_missing_ru'] = 'Russian name or description is missing.';
        }
        if (! filled($product->name_ro) || ! filled($product->description_ro)) {
            $errors['language_missing_ro'] = 'Romanian name or description is missing.';
        }
        if ($ro !== '' && preg_match('/\p{Cyrillic}/u', $ro) === 1) {
            $errors['language_ro_contains_cyrillic'] = 'Romanian fields contain Cyrillic characters.';
        }
        if (Str::contains(Str::lower($ru.' '.$ro), ['lorem ipsum', 'unknown product', 'todo', 'tbd'])) {
            $errors['language_placeholder_text'] = 'Product content contains placeholder text.';
        }
        if (Str::contains(Str::lower($ru.' '.$ro), ['voluntari', 'romania', ' ron '])) {
            $errors['language_foreign_store_artifact'] = 'Product content contains foreign store artifacts.';
        }

        return ['allowed' => $errors === [], 'errors' => $errors];
    }
}
