<?php

use App\Models\Product;
use App\Services\Catalog\ProductContentQualityGuard;
use App\Services\Catalog\ProductImageAvailabilityService;
use App\Services\Catalog\ProductLanguageQualityGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('products')->where('sku', '97443С')->update([
            'sku' => '97443C',
            'updated_at' => $now,
        ]);
        DB::table('product_parser_items')->where('sku', '97443С')->update([
            'sku' => '97443C',
            'updated_at' => $now,
        ]);

        $language = app(ProductLanguageQualityGuard::class);
        $content = app(ProductContentQualityGuard::class);
        $images = app(ProductImageAvailabilityService::class);

        Product::query()->orderBy('id')->chunkById(250, function ($products) use ($language, $content, $images, $now): void {
            foreach ($products as $product) {
                $translationInvalid = ! $language->evaluate($product)['allowed'];
                $contentInvalid = ! $content->evaluate($product)['allowed'];
                $imageInvalid = ! $images->inspect($product->main_image)['available'];
                $updates = [];

                if ($translationInvalid && ! $product->needs_translation_review) {
                    $updates['needs_translation_review'] = true;
                }
                if ($contentInvalid && ! $product->needs_content_review) {
                    $updates['needs_content_review'] = true;
                }
                if ($imageInvalid && ! $product->needs_image_review) {
                    $updates['needs_image_review'] = true;
                }

                if ($updates !== []) {
                    $updates['updated_at'] = $now;
                    DB::table('products')->where('id', $product->id)->update($updates);
                }

                $parserUpdates = array_intersect_key($updates, array_flip([
                    'needs_translation_review',
                    'needs_content_review',
                    'needs_image_review',
                    'updated_at',
                ]));
                if ($parserUpdates !== []) {
                    DB::table('product_parser_items')->where('sku', $product->sku)->update($parserUpdates);
                }
            }
        });

        DB::table('products')
            ->where('status', '!=', 'published')
            ->where('is_active', true)
            ->update([
                'status' => 'draft',
                'approval_status' => 'pending_review',
                'needs_review' => true,
                'is_active' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // Quality flags and the corrected SKU are intentionally retained.
    }
};
