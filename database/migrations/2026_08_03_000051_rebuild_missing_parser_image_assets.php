<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $absolutePath = static function (string $publicPath): string {
            if (str_starts_with($publicPath, '/storage/')) {
                return storage_path('app/public/'.substr($publicPath, strlen('/storage/')));
            }

            return public_path(ltrim($publicPath, '/'));
        };

        $resize = static function (string $sourcePath, string $targetPath, int $size): bool {
            $bytes = @file_get_contents($sourcePath);
            $source = $bytes === false ? false : @imagecreatefromstring($bytes);
            if (! $source instanceof GdImage) {
                return false;
            }

            $canvas = imagecreatetruecolor($size, $size);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
            imagecopyresampled(
                $canvas,
                $source,
                0,
                0,
                0,
                0,
                $size,
                $size,
                imagesx($source),
                imagesy($source),
            );
            imagedestroy($source);

            @mkdir(dirname($targetPath), 0777, true);
            $written = imagewebp($canvas, $targetPath, 88);
            imagedestroy($canvas);

            return $written;
        };

        DB::transaction(function () use ($absolutePath, $resize): void {
            $now = now();
            $products = DB::table('products as p')
                ->leftJoin('product_parser_image_assets as a', function ($join): void {
                    $join->on('a.parser_item_id', '=', 'p.source_parser_item_id')
                        ->where('a.is_selected', true);
                })
                ->whereNotNull('p.source_parser_item_id')
                ->whereNotNull('p.main_image')
                ->whereNull('a.id')
                ->where('p.main_image', 'not like', '%gys-product.svg%')
                ->get(['p.id', 'p.sku', 'p.main_image', 'p.source_parser_item_id']);

            foreach ($products as $product) {
                $main = $product->main_image;
                $absoluteMain = $absolutePath($main);
                if (! is_file($absoluteMain)) {
                    continue;
                }

                $directory = str_replace('\\', '/', dirname($main));
                $base = pathinfo($main, PATHINFO_FILENAME);
                $stem = preg_replace('/-main$/', '', $base);
                $isReviewedPublicImage = str_starts_with($main, '/images/catalog-reviewed/');
                $preview = $directory.'/'.($isReviewedPublicImage ? $stem.'-preview.webp' : $base.'-preview.webp');
                $thumb = $directory.'/'.$stem.'-thumb.webp';
                $absolutePreview = $absolutePath($preview);
                $absoluteThumb = $absolutePath($thumb);

                if (! is_file($absolutePreview) && ! $resize($absoluteMain, $absolutePreview, 600)) {
                    continue;
                }
                if (! is_file($absoluteThumb) && ! $resize($absoluteMain, $absoluteThumb, 300)) {
                    continue;
                }

                $image = DB::table('product_images')
                    ->where('product_id', $product->id)
                    ->where('path', $main)
                    ->orderBy('sort_order')
                    ->first();
                if (! $image || ! $image->source_url) {
                    continue;
                }

                $dimensions = @getimagesize($absoluteMain);
                DB::table('product_parser_image_assets')->insert([
                    'parser_item_id' => $product->source_parser_item_id,
                    'source_url' => $image->source_url,
                    'source_domain' => $image->source_domain ?: parse_url($image->source_url, PHP_URL_HOST),
                    'original_path' => null,
                    'processed_path' => $main,
                    'preview_path' => $preview,
                    'thumb_path' => $thumb,
                    'width' => $dimensions[0] ?? null,
                    'height' => $dimensions[1] ?? null,
                    'mime_type' => 'image/webp',
                    'status' => 'processed',
                    'is_selected' => true,
                    'is_main' => true,
                    'has_watermark' => true,
                    'background_removed' => false,
                    'background_removal_failed' => false,
                    'needs_review' => false,
                    'error_message' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                    'selected_images_json' => json_encode([$image->source_url], JSON_UNESCAPED_SLASHES),
                    'processed_images_json' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                    'needs_image_review' => false,
                    'image_reviewed_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('products')->where('id', $product->id)->update([
                    'needs_image_review' => false,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Reconstructed derivatives and parser metadata are intentionally preserved.
    }
};
