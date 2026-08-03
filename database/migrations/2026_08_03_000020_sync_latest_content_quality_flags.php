<?php

use App\Models\Product;
use App\Services\Catalog\ProductContentQualityGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $guard = app(ProductContentQualityGuard::class);
        $now = now();

        Product::query()->orderBy('id')->chunkById(250, function ($products) use ($guard, $now): void {
            foreach ($products as $product) {
                if ($guard->evaluate($product)['allowed'] || $product->needs_content_review) {
                    continue;
                }

                DB::table('products')->where('id', $product->id)->update([
                    'needs_content_review' => true,
                    'updated_at' => $now,
                ]);
                DB::table('product_parser_items')->where('sku', $product->sku)->update([
                    'needs_content_review' => true,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Quality review flags are intentionally retained.
    }
};
