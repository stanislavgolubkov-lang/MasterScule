<?php

namespace App\Services\Catalog;

use App\Models\Product;

class ProductContentQualityGuard
{
    private const GENERIC_DESCRIPTIONS = [
        'оборудование, инструмент и специнструмент для автосервиса, электроинструмент',
    ];

    public function evaluate(Product $product): array
    {
        $errors = [];
        $nameRu = trim((string) ($product->name_ru ?: $product->name));
        $nameRo = trim((string) $product->name_ro);
        $descriptionRu = trim((string) ($product->description_ru ?: $product->description));
        $descriptionRo = trim((string) $product->description_ro);

        if ($this->isGenericDescription($descriptionRu) || $this->isGenericDescription($descriptionRo)) {
            $errors['content_generic_description'] = 'Product description is a generic catalog placeholder.';
        }
        if ($this->isIncompleteDescription($descriptionRu)) {
            $errors['content_incomplete_description_ru'] = 'Russian description is incomplete.';
        }
        if ($this->isIncompleteDescription($descriptionRo)) {
            $errors['content_incomplete_description_ro'] = 'Romanian description is incomplete.';
        }
        if (! $this->hasBalancedDelimiters($nameRu)) {
            $errors['content_unbalanced_name_ru'] = 'Russian product name contains unbalanced delimiters.';
        }
        if (! $this->hasBalancedDelimiters($nameRo)) {
            $errors['content_unbalanced_name_ro'] = 'Romanian product name contains unbalanced delimiters.';
        }

        return ['allowed' => $errors === [], 'errors' => $errors];
    }

    private function isGenericDescription(string $value): bool
    {
        $normalized = preg_replace('/\s+/u', ' ', mb_strtolower(trim($value))) ?? '';

        if (in_array($normalized, self::GENERIC_DESCRIPTIONS, true)) {
            return true;
        }

        return preg_match('/\beste un produs\b.*\bdin categoria\b/u', $normalized) === 1
            || str_contains($normalized, 'cod producator:')
            || (str_contains($normalized, 'este recomandat pentru lucrari profesionale')
                && str_contains($normalized, 'categoria:'));
    }

    private function isIncompleteDescription(string $value): bool
    {
        $normalized = trim(strip_tags($value));
        if ($normalized === '') {
            return false;
        }

        if (preg_match('/^(?:состав|назначение|описание|componență|destinație|descriere)\s*:?$/iu', $normalized) === 1) {
            return true;
        }

        return mb_strlen($normalized) < 20;
    }

    private function hasBalancedDelimiters(string $value): bool
    {
        foreach ([['(', ')'], ['[', ']']] as [$open, $close]) {
            if (substr_count($value, $open) !== substr_count($value, $close)) {
                return false;
            }
        }

        return true;
    }
}
