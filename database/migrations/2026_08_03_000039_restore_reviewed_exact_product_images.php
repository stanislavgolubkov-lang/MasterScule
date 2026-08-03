<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $images = [
            'JTC-4902' => ['jtc', 'jtc-4902'],
            'JTC-4924' => ['jtc', 'jtc-4924'],
            'JTC-6760' => ['jtc', 'jtc-6760'],
            'JTC-1415' => ['jtc', 'jtc-1415'],
            'JTC-6885' => ['jtc', 'jtc-6885'],
            'JTC-6892' => ['jtc', 'jtc-6892'],
            'JTC-1339' => ['jtc', 'jtc-1339'],
            '4795-18' => ['king-tony', '4795-18'],
            '3795-18' => ['king-tony', '3795-18'],
            '4725-12BR' => ['king-tony', '4725-12br'],
            '2752-06G' => ['king-tony', '2752-06g'],
        ];

        DB::transaction(function () use ($images): void {
            foreach ($images as $sku => [$brandDirectory, $slug]) {
                $product = DB::table('products')
                    ->where('sku', $sku)
                    ->select('id', 'name_ru', 'source_url', 'source_parser_item_id')
                    ->first();
                if (! $product) {
                    continue;
                }

                $directory = '/images/catalog-reviewed/'.$brandDirectory.'/'.$slug;
                $main = $directory.'/'.$slug.'-main.webp';
                $preview = $directory.'/'.$slug.'-main-preview.webp';
                $thumb = $directory.'/'.$slug.'-thumb.webp';
                if (! is_file(public_path(ltrim($main, '/')))
                    || ! is_file(public_path(ltrim($preview, '/')))
                    || ! is_file(public_path(ltrim($thumb, '/')))) {
                    continue;
                }

                $now = now();
                DB::table('products')->where('id', $product->id)->update([
                    'main_image' => $main,
                    'gallery' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                    'needs_image_review' => false,
                    'updated_at' => $now,
                ]);

                $asset = $product->source_parser_item_id
                    ? DB::table('product_parser_image_assets')
                        ->where('parser_item_id', $product->source_parser_item_id)
                        ->where('is_selected', true)
                        ->orderByDesc('is_main')
                        ->orderBy('id')
                        ->first()
                    : null;

                if ($asset) {
                    DB::table('product_parser_image_assets')->where('id', $asset->id)->update([
                        'processed_path' => $main,
                        'preview_path' => $preview,
                        'thumb_path' => $thumb,
                        'status' => 'processed',
                        'has_watermark' => true,
                        'needs_review' => false,
                        'error_message' => null,
                        'updated_at' => $now,
                    ]);
                }

                if ($product->source_parser_item_id) {
                    DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                        'processed_images_json' => json_encode([$main], JSON_UNESCAPED_SLASHES),
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
                    'source_url' => $asset?->source_url,
                    'source_page_url' => $product->source_url,
                    'source_domain' => $asset?->source_domain,
                    'is_official' => false,
                    'mime_type' => 'image/webp',
                    'width' => 1200,
                    'height' => 1200,
                    'file_size' => filesize(public_path(ltrim($main, '/'))) ?: null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Reviewed media restoration is intentionally irreversible.
    }
};
