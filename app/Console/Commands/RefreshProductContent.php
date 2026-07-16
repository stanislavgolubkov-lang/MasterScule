<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductParserContentBuilder;
use App\Services\ProductSearchService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Throwable;

class RefreshProductContent extends Command
{
    protected $signature = 'masterscule:refresh-product-content
        {--sku= : Refresh one SKU}
        {--brand= : Limit by brand name}
        {--limit=25 : Maximum products to process}
        {--all : Process all selected products instead of only products with missing/generic descriptions}
        {--commit : Apply updates; without this option the command is a dry-run}';

    protected $description = 'Refresh already imported product names and descriptions from official or fallback source pages';

    public function handle(ProductSearchService $search, ProductParserContentBuilder $contentBuilder): int
    {
        $commit = (bool) $this->option('commit');
        $sku = trim((string) $this->option('sku'));
        $brand = trim((string) $this->option('brand'));
        $limit = max(1, (int) $this->option('limit'));
        $onlyBad = ! (bool) $this->option('all') && $sku === '';
        $stats = [
            'checked' => 0,
            'matched' => 0,
            'would_update' => 0,
            'updated' => 0,
            'skipped_no_source' => 0,
            'errors' => 0,
        ];

        $products = Product::with(['brand', 'category'])
            ->when($sku !== '', fn (Builder $query) => $query->where('sku', $sku))
            ->when($brand !== '', fn (Builder $query) => $query->whereHas('brand', fn (Builder $brandQuery) => $brandQuery->where('name', 'like', '%'.$brand.'%')))
            ->when($onlyBad, fn (Builder $query) => $this->whereBadContent($query))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($products as $product) {
            $stats['checked']++;

            try {
                $result = $search->searchForParser(
                    (string) $product->sku,
                    $product->brand?->name,
                    preferLocal: false,
                    name: (string) ($product->name_ru ?: $product->name_ro ?: $product->name),
                );

                if (! ($result['found'] ?? false)) {
                    $stats['skipped_no_source']++;
                    $this->warn("{$product->sku}: source not found");
                    continue;
                }

                $stats['matched']++;
                $badRu = $this->isBadRu($product->description_ru ?: $product->description);
                $badRo = $this->isBadRo((string) $product->description_ro);

                $baseContent = [
                    'name_ru' => $product->name_ru ?: $product->name,
                    'name_ro' => $this->isBadRoName((string) $product->name_ro, $product) ? null : $product->name_ro,
                    'short_description_ru' => $badRu ? null : ($product->short_description_ru ?: $product->short_description),
                    'short_description_ro' => $badRo ? null : $product->short_description_ro,
                    'description_ru' => $badRu ? null : ($product->description_ru ?: $product->description),
                    'description_ro' => $badRo ? null : $product->description_ro,
                    'needs_translation_review' => (bool) $product->needs_translation_review,
                    'needs_content_review' => (bool) $product->needs_content_review,
                    'generated_content' => (bool) $product->generated_content,
                ];

                $content = $contentBuilder->mergeOfficialContent(
                    $baseContent,
                    $result['title'] ?? null,
                    $result['description'] ?? null,
                    (string) $product->sku,
                    $product->brand?->name,
                );

                $sourceUrl = $this->contentSourceUrl($result);
                $updates = [
                    'name' => $content['name_ru'],
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description' => $content['short_description_ru'],
                    'short_description_ru' => $content['short_description_ru'],
                    'short_description_ro' => $content['short_description_ro'],
                    'description' => $content['description_ru'],
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'meta_description' => Str::limit($content['short_description_ru'] ?: $content['description_ru'], 150, ''),
                    'attributes' => ($result['specs'] ?? []) ?: $product->attributes,
                    'package_contents' => ($result['package_contents'] ?? []) ?: $product->package_contents,
                    'parser_source_urls' => array_values(array_unique(array_filter(array_merge(
                        $product->parser_source_urls ?: [],
                        $result['source_urls'] ?? [],
                    )))),
                    'parser_confidence' => max((int) ($product->parser_confidence ?? 0), (int) ($result['confidence'] ?? 0)),
                    'fallback_source_used' => (bool) ($result['fallback_source_used'] ?? $product->fallback_source_used),
                    'needs_source_review' => (bool) ($result['needs_source_review'] ?? $product->needs_source_review),
                    'needs_content_review' => (bool) $content['needs_content_review'],
                    'needs_translation_review' => (bool) $content['needs_translation_review'],
                    'generated_content' => (bool) $content['generated_content'],
                ];

                if ($sourceUrl) {
                    $updates['source_url'] = $sourceUrl;
                    $updates['source_domain'] = preg_replace('/^www\./i', '', (string) parse_url($sourceUrl, PHP_URL_HOST));
                    $updates['source_type'] = $result['content_source_type'] ?? ($result['fallback_source_used'] ?? false ? 'fallback_reference' : 'official_manufacturer');
                }

                $willUpdate = $this->hasChanges($product, $updates);
                if ($willUpdate) {
                    $stats['would_update']++;
                }

                $this->line("{$product->sku}: ".($willUpdate ? 'content refresh ready' : 'already current'));

                if (! $commit || ! $willUpdate) {
                    continue;
                }

                $product->forceFill($updates)->save();
                $stats['updated']++;
            } catch (Throwable $exception) {
                $stats['errors']++;
                $this->error("{$product->sku}: {$exception->getMessage()}");
            }
        }

        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());
        $this->info($commit ? 'Product content refresh applied.' : 'Dry-run only. Re-run with --commit to apply changes.');

        return self::SUCCESS;
    }

    private function whereBadContent(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query
                ->whereNull('description_ru')
                ->orWhere('description_ru', '')
                ->orWhere('description_ru', 'like', 'Оборудование, инструмент%')
                ->orWhere('description_ru', 'like', '%специнструмент для автосервиса%')
                ->orWhere('description_ru', 'like', '%Official manufacturer media matched by exact SKU%')
                ->orWhereNull('description_ro')
                ->orWhere('description_ro', '')
                ->orWhere('description_ro', 'like', '% este un produs % din categoria %')
                ->orWhere('description_ro', 'like', '%Cod producator:%')
                ->orWhere('description_ro', 'like', '%Official manufacturer media matched by exact SKU%');
        });
    }

    private function isBadRu(?string $value): bool
    {
        $value = trim((string) $value);

        return $value === ''
            || Str::startsWith($value, 'Оборудование, инструмент')
            || Str::contains($value, [
                'специнструмент для автосервиса',
                'Official manufacturer media matched by exact SKU',
            ]);
    }

    private function isBadRo(string $value): bool
    {
        $value = trim($value);

        return $value === ''
            || preg_match('/\p{Cyrillic}/u', $value) === 1
            || Str::contains($value, [
                ' este un produs ',
                'Cod producator:',
                'Official manufacturer media matched by exact SKU',
            ]);
    }

    private function isBadRoName(string $value, Product $product): bool
    {
        $value = trim($value);
        if ($value === '' || preg_match('/\p{Cyrillic}/u', $value) === 1) {
            return true;
        }

        return Str::contains($value, trim(($product->category?->name_ro ?: 'Instrument manual').' '.$product->brand?->name.' '.$product->sku));
    }

    private function contentSourceUrl(array $result): ?string
    {
        $urls = array_filter([
            $result['official_source_url'] ?? null,
            $result['fallback_source_url'] ?? null,
            ...($result['source_urls'] ?? []),
        ]);

        foreach ($urls as $url) {
            if (! $this->isDirectImageUrl((string) $url)) {
                return (string) $url;
            }
        }

        return null;
    }

    private function isDirectImageUrl(string $url): bool
    {
        return (bool) preg_match('/\.(?:jpe?g|png|webp)(?:\?[^#]*)?$/i', $url);
    }

    private function hasChanges(Product $product, array $updates): bool
    {
        foreach ($updates as $field => $value) {
            if ($product->{$field} != $value) {
                return true;
            }
        }

        return false;
    }
}
