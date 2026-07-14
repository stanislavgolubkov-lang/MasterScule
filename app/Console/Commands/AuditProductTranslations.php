<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class AuditProductTranslations extends Command
{
    protected $signature = 'masterscule:audit-product-translations {--mark-review}';

    protected $description = 'Audit RU and RO product text without generating translations';

    public function handle(): int
    {
        $mark = (bool) $this->option('mark-review');
        $stats = [
            'total_products' => 0,
            'missing_ru_name' => 0,
            'missing_ro_name' => 0,
            'ro_contains_cyrillic' => 0,
            'missing_ru_description' => 0,
            'missing_ro_description' => 0,
            'published_with_translation_issues' => 0,
            'marked_for_review' => 0,
        ];

        Product::orderBy('id')->chunkById(500, function ($products) use ($mark, &$stats) {
            foreach ($products as $product) {
                $stats['total_products']++;
                $nameRu = trim((string) ($product->name_ru ?: $product->name));
                $nameRo = trim((string) $product->name_ro);
                $descriptionRu = trim((string) ($product->short_description_ru ?: $product->short_description ?: $product->description_ru ?: $product->description));
                $descriptionRo = trim((string) ($product->short_description_ro ?: $product->description_ro));
                $cyrillic = preg_match('/\p{Cyrillic}/u', implode(' ', array_filter([
                    $product->name_ro,
                    $product->short_description_ro,
                    $product->description_ro,
                ]))) === 1;
                $issues = [];

                if ($nameRu === '') {
                    $issues[] = 'missing_ru_name';
                }
                if ($nameRo === '') {
                    $issues[] = 'missing_ro_name';
                }
                if ($cyrillic) {
                    $issues[] = 'ro_contains_cyrillic';
                }
                if ($descriptionRu === '') {
                    $issues[] = 'missing_ru_description';
                }
                if ($descriptionRo === '') {
                    $issues[] = 'missing_ro_description';
                }

                foreach ($issues as $issue) {
                    $stats[$issue]++;
                }

                if ($issues !== [] && ($product->status === 'published' || $product->is_active)) {
                    $stats['published_with_translation_issues']++;
                }
                if ($mark && $issues !== [] && ! $product->needs_translation_review) {
                    $product->forceFill(['needs_translation_review' => true])->save();
                    $stats['marked_for_review']++;
                }
            }
        });

        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());

        return self::SUCCESS;
    }
}
