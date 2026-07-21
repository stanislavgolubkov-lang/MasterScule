<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $product = DB::table('products')->where('sku', '203503')->first(['id', 'main_image']);

        if (! $product || ! str_contains((string) $product->main_image, '/203503/')) {
            return;
        }

        DB::table('product_images')->where('product_id', $product->id)->delete();
        DB::table('products')->where('id', $product->id)->update([
            'main_image' => null,
            'gallery' => null,
            'needs_image_review' => true,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // The rejected family image is intentionally not restored.
    }
};
