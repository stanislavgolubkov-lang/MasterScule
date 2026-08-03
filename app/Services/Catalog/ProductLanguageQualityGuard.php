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
        $ruName = trim((string) ($product->name_ru ?: $product->name));
        $ruDescription = trim((string) ($product->description_ru ?: $product->description));
        $roName = trim((string) $product->name_ro);
        $roDescription = trim((string) $product->description_ro);
        $ru = trim($ruName.' '.$ruDescription);
        $ro = trim($roName.' '.$roDescription);

        if ($ruName === '' || $ruDescription === '') {
            $errors['language_missing_ru'] = 'Russian name or description is missing.';
        }
        if ($ruName !== '' && ! $this->language->containsCyrillic($ruName)) {
            $errors['language_ru_name_missing_cyrillic'] = 'Russian name does not contain Russian text.';
        }
        if ($ruDescription !== '' && ! $this->language->containsCyrillic($ruDescription)) {
            $errors['language_ru_description_missing_cyrillic'] = 'Russian description does not contain Russian text.';
        }
        if ($this->language->containsUkrainian($ru.' '.$ro)) {
            $errors['language_ukrainian_not_supported'] = 'Ukrainian content is not allowed; storefront content must be Russian and Romanian.';
        }
        if ($roName === '' || $roDescription === '') {
            $errors['language_missing_ro'] = 'Romanian name or description is missing.';
        }
        if ($ro !== '' && preg_match('/\p{Cyrillic}/u', $ro) === 1) {
            $errors['language_ro_contains_cyrillic'] = 'Romanian fields contain Cyrillic characters.';
        }
        if ($roName !== '' && $this->language->isLikelyEnglish($roName)) {
            $errors['language_ro_name_likely_english'] = 'Romanian name appears to be English.';
        }
        if ($roDescription !== '' && $this->language->isLikelyEnglish($roDescription)) {
            $errors['language_ro_description_likely_english'] = 'Romanian description appears to be English.';
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
