<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductParserContentBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RepairProductDescriptions extends Command
{
    protected $signature = 'masterscule:repair-product-descriptions {--commit} {--limit=0}';

    protected $description = 'Fill missing product names and descriptions without rebuilding the catalog';

    public function handle(ProductParserContentBuilder $contentBuilder): int
    {
        $commit = (bool) $this->option('commit');
        $limit = max(0, (int) $this->option('limit'));
        $stats = [
            'checked' => 0,
            'would_update' => 0,
            'updated' => 0,
            'fields_filled' => 0,
        ];

        $query = Product::with(['brand', 'category'])
            ->where(function ($query) {
                foreach ([
                    'name_ru',
                    'name_ro',
                    'short_description',
                    'short_description_ru',
                    'short_description_ro',
                    'description',
                    'description_ru',
                    'description_ro',
                ] as $column) {
                    $query->orWhereNull($column)->orWhere($column, '');
                }
            })
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $query->get()->each(function (Product $product) use ($contentBuilder, $commit, &$stats) {
            $stats['checked']++;
            $content = $contentBuilder->ensureComplete([
                'name_ru' => $product->name_ru ?: $product->name,
                'name_ro' => $product->name_ro,
                'short_description_ru' => $product->short_description_ru ?: $product->short_description,
                'short_description_ro' => $product->short_description_ro,
                'description_ru' => $product->description_ru ?: $product->description,
                'description_ro' => $product->description_ro,
                'needs_translation_review' => (bool) $product->needs_translation_review,
                'needs_content_review' => (bool) $product->needs_content_review,
                'generated_content' => (bool) $product->generated_content,
            ], $product->sku, (string) ($product->name_ru ?: $product->name_ro ?: $product->name ?: $product->sku), $product->brand?->name, null, [
                'category_slug' => $product->category?->slug,
                'category_name_ru' => $product->category?->name,
                'category_name_ro' => $product->category?->name_ro,
            ]);

            $updates = [];
            $this->fillMissing($updates, $product, 'name', $content['name_ru']);
            $this->fillMissing($updates, $product, 'name_ru', $content['name_ru']);
            $this->fillMissing($updates, $product, 'name_ro', $content['name_ro']);
            $this->fillMissing($updates, $product, 'short_description', $content['short_description_ru']);
            $this->fillMissing($updates, $product, 'short_description_ru', $content['short_description_ru']);
            $this->fillMissing($updates, $product, 'short_description_ro', $content['short_description_ro']);
            $this->fillMissing($updates, $product, 'description', $content['description_ru']);
            $this->fillMissing($updates, $product, 'description_ru', $content['description_ru']);
            $this->fillMissing($updates, $product, 'description_ro', $content['description_ro']);
            $this->fillMissing($updates, $product, 'meta_description', Str::limit($content['short_description_ru'] ?: $content['description_ru'], 150, ''));

            if ($updates === []) {
                return;
            }

            $stats['would_update']++;
            $stats['fields_filled'] += count($updates);

            if ($commit) {
                $product->forceFill($updates + [
                    'needs_translation_review' => (bool) $content['needs_translation_review'],
                    'needs_content_review' => (bool) $content['needs_content_review'],
                    'generated_content' => (bool) $content['generated_content'],
                ])->save();
                $stats['updated']++;
            }
        });

        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());
        $this->info($commit ? 'Missing descriptions repaired.' : 'Dry-run only. Re-run with --commit to apply changes.');

        return self::SUCCESS;
    }

    private function fillMissing(array &$updates, Product $product, string $field, ?string $value): void
    {
        if (trim((string) $product->{$field}) !== '' || trim((string) $value) === '') {
            return;
        }

        $updates[$field] = $value;
    }
}
