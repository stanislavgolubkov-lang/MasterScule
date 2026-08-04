<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $categoryId = DB::table('categories')
                ->where('slug', 'alte-scule-pneumatice')
                ->value('id');

            if (! $categoryId) {
                return;
            }

            $products = DB::table('products')
                ->whereIn('sku', ['7990R', '79900R-04', '7990A0'])
                ->get(['id']);

            foreach ($products as $product) {
                DB::table('products')->where('id', $product->id)->update([
                    'category_id' => $categoryId,
                    'updated_at' => now(),
                ]);

                DB::table('category_product')->where('product_id', $product->id)->delete();
                DB::table('category_product')->insert([
                    'product_id' => $product->id,
                    'category_id' => $categoryId,
                    'is_primary' => true,
                    'source' => 'verified_deep_audit',
                    'confidence' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Verified category corrections are intentionally not reverted.
    }
};
