<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductLanguageQualityGuard
{
    public function __construct(
        private readonly ProductContentLanguage $language,
        private readonly ProductTechnicalIdentityPolicy $technicalIdentities,
    ) {}

    public function evaluate(Product $product): array
    {
        $errors = [];
        $ruName = trim((string) ($product->name_ru ?: $product->name));
        $ruDescription = trim((string) ($product->description_ru ?: $product->description));
        $roName = trim((string) $product->name_ro);
        $roDescription = trim((string) $product->description_ro);
        $ru = trim($ruName.' '.$ruDescription);
        $ro = trim($roName.' '.$roDescription);
        $roLanguageSample = $this->technicalIdentities->romanianLanguageSample($product, $ro);

        if ($ruName === '' || $ruDescription === '') {
            $errors['language_missing_ru'] = 'Russian name or description is missing.';
        }
        if ($ruName !== '' && ! $this->language->containsCyrillic($ruName)) {
            $errors['language_ru_name_missing_cyrillic'] = 'Russian name does not contain Russian text.';
        }
        if ($ruDescription !== '' && ! $this->language->containsCyrillic($ruDescription)) {
            $errors['language_ru_description_missing_cyrillic'] = 'Russian description does not contain Russian text.';
        }
        if ($this->language->containsUkrainian($ru.' '.$roLanguageSample)) {
            $errors['language_ukrainian_not_supported'] = 'Ukrainian content is not allowed; storefront content must be Russian and Romanian.';
        }
        if ($roName === '' || $roDescription === '') {
            $errors['language_missing_ro'] = 'Romanian name or description is missing.';
        }
        if ($roLanguageSample !== '' && preg_match('/\p{Cyrillic}/u', $roLanguageSample) === 1) {
            $errors['language_ro_contains_cyrillic'] = 'Romanian fields contain Cyrillic characters.';
        }
        if ($roName !== '' && $this->language->isLikelyEnglish($roName)) {
            $errors['language_ro_name_likely_english'] = 'Romanian name appears to be English.';
        }
        if ($roDescription !== '' && $this->language->isLikelyEnglish($roDescription)) {
            $errors['language_ro_description_likely_english'] = 'Romanian description appears to be English.';
        }

        $semanticPatterns = [
            'language_ro_impact_socket_mistranslation' => '/\bpriz(?:ă|a)\s+(?:adâncă\s+)?de impact\b/iu',
            'language_ro_impact_bit_mistranslation' => '/\bliliac de impact\b/iu',
            'language_ro_profile_mistranslation' => '/\b\d+\s+(?:cereale|boabe)\b/iu',
            'language_ro_bit_command_form' => '/\b(?:introduceți|inserați)\s*\(bit\)/iu',
            'language_ro_tap_mistranslation' => '/(?:\b(?:atingeți|măsura)\s+M\d|\bcontor\s+M\d)/iu',
            'language_ro_known_literal_translation' => '/\b(?:lanterna de sudur[ăa]|clemă de corp|pod care trag corpul|cap muf[ăa]|set prize de capăt|sarma solid|aliei de aluminiu|instrumente de îndrire|l(?:e)?erka|suport pentru haine|placa este)\b/iu',
            'language_ro_generic_machine_title' => '/^(?:bit sau adaptor|bit sau accesoriu|consumabil pentru scule pneumatice|accesoriu universal|clește sau sculă de tăiere|sculă pentru electrician)\b/iu',
            'language_ro_malformed_unit_tail' => '/,\s*mm(?:\s+\d+)?\s*$/u',
            'language_ro_malformed_separator' => '/(?:\s\.\s|,\s*["“”]\s*["“”]|\.\s*\.\s*)/u',
            'language_ro_duplicate_word' => '/\b([\p{L}]{4,})\s+\1\b/iu',
        ];

        foreach ($semanticPatterns as $code => $pattern) {
            $target = in_array($code, [
                'language_ro_malformed_separator',
                'language_ro_malformed_unit_tail',
            ], true) ? $roName : $ro;
            if (preg_match($pattern, $target) === 1) {
                $errors[$code] = 'Romanian content contains a known catalog translation defect.';
            }
        }

        if (preg_match('/filtr\p{L}*(?:\s+de)?\s+ulei.*\b\d+\s*g\./iu', $ro) === 1) {
            $errors['language_ro_oil_filter_facets_as_grams'] = 'Oil-filter facets are incorrectly expressed as grams in Romanian.';
        }

        if (preg_match('/\bдля\s+для\b/iu', $ru) === 1) {
            $errors['language_ru_duplicate_word'] = 'Russian content contains a duplicated word.';
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
