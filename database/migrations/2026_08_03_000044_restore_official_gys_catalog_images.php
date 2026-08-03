<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $images = require database_path('data/reviewed-gys-catalog-images.php');

        DB::transaction(function () use ($images): void {
            foreach ($images as $sku => $source) {
                $slug = Str::slug($sku);
                $directory = '/images/catalog-reviewed/gys-catalog-sourced/'.$slug;
                $main = $directory.'/'.$slug.'-main.webp';
                $preview = $directory.'/'.$slug.'-preview.webp';
                $thumb = $directory.'/'.$slug.'-thumb.webp';
                $absoluteMain = public_path(ltrim($main, '/'));

                if (! is_file($absoluteMain)
                    || ! is_file(public_path(ltrim($preview, '/')))
                    || ! is_file(public_path(ltrim($thumb, '/')))) {
                    continue;
                }

                $product = DB::table('products')
                    ->where('sku', $sku)
                    ->select('id', 'name_ru', 'source_parser_item_id', 'parser_source_urls')
                    ->first();
                if (! $product) {
                    continue;
                }

                $now = now();
                $sourceUrls = json_decode((string) $product->parser_source_urls, true);
                $sourceUrls = is_array($sourceUrls) ? $sourceUrls : [];
                $sourceUrls[] = $source['page_url'];
                $verification = [
                    'sku' => $sku,
                    'manufacturer_reference' => $source['reference'],
                    'verification' => 'exact_gys_catalog_object_id',
                ];

                DB::table('products')->where('id', $product->id)->update([
                    'main_image' => $main,
                    'gallery' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                    'needs_image_review' => false,
                    'parser_source_urls' => json_encode(array_values(array_unique($sourceUrls)), JSON_UNESCAPED_SLASHES),
                    'source_url' => $source['page_url'],
                    'source_domain' => 'planet.gys.fr',
                    'source_type' => 'official_manufacturer',
                    'fallback_source_used' => false,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'parser_confidence' => 100,
                    'updated_at' => $now,
                ]);

                if ($product->source_parser_item_id) {
                    DB::table('product_parser_image_assets')
                        ->where('parser_item_id', $product->source_parser_item_id)
                        ->update(['is_selected' => false, 'is_main' => false, 'updated_at' => $now]);
                    DB::table('product_parser_image_assets')->updateOrInsert(
                        ['parser_item_id' => $product->source_parser_item_id, 'source_url' => $source['image_url']],
                        [
                            'source_domain' => 'planet.gys.fr',
                            'original_path' => null,
                            'processed_path' => $main,
                            'preview_path' => $preview,
                            'thumb_path' => $thumb,
                            'width' => 1200,
                            'height' => 1200,
                            'mime_type' => 'image/webp',
                            'status' => 'processed',
                            'is_selected' => true,
                            'is_main' => true,
                            'has_watermark' => true,
                            'background_removed' => false,
                            'background_removal_failed' => false,
                            'needs_review' => false,
                            'error_message' => null,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                    DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                        'selected_images_json' => json_encode([$source['image_url']], JSON_UNESCAPED_SLASHES),
                        'processed_images_json' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                        'image_source_type' => 'official_manufacturer',
                        'official_source_url' => $source['page_url'],
                        'official_source_domain' => 'planet.gys.fr',
                        'official_source_confidence' => 100,
                        'fallback_source_url' => null,
                        'fallback_source_domain' => null,
                        'fallback_source_used' => false,
                        'source_match_confidence' => 100,
                        'needs_source_review' => false,
                        'source_reviewed_at' => $now,
                        'needs_image_review' => false,
                        'image_reviewed_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DB::table('product_parser_sources')->updateOrInsert(
                        ['parser_item_id' => $product->source_parser_item_id, 'url' => $source['page_url']],
                        [
                            'domain' => 'planet.gys.fr',
                            'title' => 'Official GYS catalog card for reference '.$source['reference'],
                            'snippet' => 'Exact reference and product image verified through the public manufacturer catalog.',
                            'source_type' => 'official_manufacturer',
                            'confidence_score' => 100,
                            'raw_data_json' => json_encode($verification, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }

                DB::table('product_images')->where('product_id', $product->id)->delete();
                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'path' => $main,
                    'alt' => $product->name_ru,
                    'sort_order' => 1,
                    'source_url' => $source['image_url'],
                    'source_page_url' => $source['page_url'],
                    'source_domain' => 'planet.gys.fr',
                    'is_official' => true,
                    'mime_type' => 'image/webp',
                    'width' => 1200,
                    'height' => 1200,
                    'file_size' => filesize($absoluteMain) ?: null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Verified official product media restoration is intentionally irreversible.
    }
};
