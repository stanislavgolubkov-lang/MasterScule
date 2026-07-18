<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'category_id'])
            ->orderBy('id')
            ->chunkById(500, function ($products): void {
                foreach ($products as $product) {
                    DB::table('category_product')
                        ->where('product_id', $product->id)
                        ->where('is_primary', true)
                        ->where('category_id', '<>', $product->category_id)
                        ->update([
                            'category_id' => $product->category_id,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // The previous mismatched primary links were invalid state and must not be restored.
    }
};
