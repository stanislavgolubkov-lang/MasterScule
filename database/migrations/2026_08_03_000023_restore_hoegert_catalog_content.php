<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/hoegert-2025-catalog-content.json');
        $records = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $brandId = DB::table('brands')->where('name', 'Hoegert')->value('id');
        if (! $brandId) {
            return;
        }

        DB::transaction(function () use ($records, $brandId): void {
            foreach ($records as $sku => $content) {
                $product = DB::table('products')
                    ->where('brand_id', $brandId)
                    ->where('sku', $sku)
                    ->first();
                if (! $product) {
                    continue;
                }

                $now = now();
                $sourceDomain = (string) parse_url($content['source_url'], PHP_URL_HOST);
                $shortRu = Str::limit($content['description_ru'], 240, '');
                $shortRo = Str::limit($content['description_ro'], 240, '');
                $sourceUrls = array_values(array_unique(array_filter([
                    ...$this->decodeArray($product->parser_source_urls ?? null),
                    $content['source_url'],
                ])));

                DB::table('products')->where('id', $product->id)->update([
                    'name' => $content['name_ru'],
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description' => $shortRu,
                    'short_description_ru' => $shortRu,
                    'short_description_ro' => $shortRo,
                    'description' => $content['description_ru'],
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'meta_description' => Str::limit($content['description_ru'], 150, ''),
                    'source_url' => $content['source_url'],
                    'source_domain' => $sourceDomain,
                    'source_type' => 'official_manufacturer',
                    'parser_source_urls' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES),
                    'parser_confidence' => 100,
                    'fallback_source_used' => false,
                    'needs_source_review' => false,
                    'needs_content_review' => false,
                    'needs_translation_review' => false,
                    'generated_content' => false,
                    'source_reviewed_at' => $now,
                    'updated_at' => $now,
                ]);

                $parserUpdates = [
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description_ru' => $shortRu,
                    'short_description_ro' => $shortRo,
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'found_title' => $content['name_ru'],
                    'found_description' => $content['description_ru'],
                    'official_source_url' => $content['source_url'],
                    'official_source_domain' => $sourceDomain,
                    'official_source_confidence' => 100,
                    'fallback_source_url' => null,
                    'fallback_source_domain' => null,
                    'fallback_source_used' => false,
                    'source_match_confidence' => 100,
                    'needs_source_review' => false,
                    'needs_content_review' => false,
                    'needs_translation_review' => false,
                    'generated_content' => false,
                    'content_source_type' => 'official_source',
                    'source_reviewed_at' => $now,
                    'updated_at' => $now,
                ];

                $parserQuery = DB::table('product_parser_items');
                $product->source_parser_item_id
                    ? $parserQuery->where('id', $product->source_parser_item_id)->update($parserUpdates)
                    : $parserQuery->where('sku', $sku)->update($parserUpdates);
            }
        });
    }

    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function down(): void
    {
        // Exact-SKU bilingual catalog content is intentionally retained.
    }
};
