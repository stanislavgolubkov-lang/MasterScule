<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $families = [
            '4025' => ['402509'],
            '4330MR' => ['433008MR', '433009MR', '433029MR'],
            '7100H' => ['711107H', '711507H'],
        ];

        DB::transaction(function () use ($families): void {
            foreach ($families as $family => $skus) {
                $slug = Str::slug($family);
                $directory = '/images/catalog-reviewed/king-tony-families/'.$slug;
                $main = $directory.'/'.$slug.'-main.webp';
                $preview = $directory.'/'.$slug.'-preview.webp';
                $thumb = $directory.'/'.$slug.'-thumb.webp';
                $absoluteMain = public_path(ltrim($main, '/'));
                if (! is_file($absoluteMain)) {
                    continue;
                }

                foreach (DB::table('products')->whereIn('sku', $skus)->get(['id', 'name_ru', 'source_url', 'source_parser_item_id']) as $product) {
                    $now = now();
                    $sourceUrl = 'https://www.kingtony.com/upload/products/'.rawurlencode($family).'.png';
                    DB::table('products')->where('id', $product->id)->update([
                        'main_image' => $main,
                        'gallery' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                        'needs_image_review' => false,
                        'updated_at' => $now,
                    ]);
                    if ($product->source_parser_item_id) {
                        DB::table('product_parser_image_assets')->where('parser_item_id', $product->source_parser_item_id)
                            ->update(['is_selected' => false, 'is_main' => false, 'updated_at' => $now]);
                        DB::table('product_parser_image_assets')->updateOrInsert(
                            ['parser_item_id' => $product->source_parser_item_id, 'source_url' => $sourceUrl],
                            [
                                'source_domain' => 'www.kingtony.com',
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
                                'needs_review' => false,
                                'error_message' => null,
                                'updated_at' => $now,
                                'created_at' => $now,
                            ]
                        );
                        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                            'selected_images_json' => json_encode([$sourceUrl], JSON_UNESCAPED_SLASHES),
                            'processed_images_json' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                            'image_source_type' => 'official_manufacturer_family',
                            'needs_image_review' => false,
                            'image_reviewed_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                    DB::table('product_images')->where('product_id', $product->id)->delete();
                    DB::table('product_images')->insert([
                        'product_id' => $product->id,
                        'path' => $main,
                        'alt' => $product->name_ru,
                        'sort_order' => 1,
                        'source_url' => $sourceUrl,
                        'source_page_url' => $product->source_url,
                        'source_domain' => 'www.kingtony.com',
                        'is_official' => true,
                        'mime_type' => 'image/webp',
                        'width' => 1200,
                        'height' => 1200,
                        'file_size' => filesize($absoluteMain) ?: null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Verified catalog-family media restoration is intentionally irreversible.
    }
};
