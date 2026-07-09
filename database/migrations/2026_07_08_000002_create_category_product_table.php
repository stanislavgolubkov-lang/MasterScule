<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_product')) {
            Schema::create('category_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->string('source')->nullable();
                $table->unsignedSmallInteger('confidence')->default(100);
                $table->timestamps();

                $table->unique(['product_id', 'category_id']);
                $table->index(['category_id', 'product_id']);
                $table->index(['product_id', 'is_primary']);
            });
        }

        DB::table('products')
            ->select(['id', 'category_id'])
            ->whereNotNull('category_id')
            ->orderBy('id')
            ->chunkById(500, function ($products): void {
                $now = now();
                $rows = $products
                    ->map(fn ($product) => [
                        'product_id' => $product->id,
                        'category_id' => $product->category_id,
                        'is_primary' => true,
                        'source' => 'legacy_primary',
                        'confidence' => 100,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                if ($rows !== []) {
                    DB::table('category_product')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
