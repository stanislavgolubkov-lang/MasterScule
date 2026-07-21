<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categoryId = DB::table('categories')->where('slug', 'extractoare-si-prese')->value('id');
        if (! $categoryId) {
            return;
        }

        DB::transaction(function () use ($categoryId): void {
            $products = DB::table('products')->whereIn('sku', ['9TD034MR', '9TD014MR'])->get();
            foreach ($products as $product) {
                $now = now();
                DB::table('products')->where('id', $product->id)->update([
                    'category_id' => $categoryId,
                    'needs_category_review' => false,
                    'updated_at' => $now,
                ]);
                DB::table('category_product')->where('product_id', $product->id)->delete();
                DB::table('category_product')->insert([
                    'product_id' => $product->id,
                    'category_id' => $categoryId,
                    'is_primary' => true,
                    'source' => 'verified_socket_extractor_leaf_repair',
                    'confidence' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($product->source_parser_item_id) {
                    DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                        'category_id' => $categoryId,
                        'detected_category_id' => $categoryId,
                        'detected_category_path' => 'extractoare-si-prese',
                        'category_confidence_score' => 100,
                        'category_detection_method' => 'verified_socket_extractor_leaf_repair',
                        'needs_category_review' => false,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Exact leaf-category corrections are intentionally retained.
    }
};
