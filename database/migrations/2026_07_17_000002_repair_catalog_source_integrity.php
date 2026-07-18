<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE products
            SET source_parser_item_id = (
                SELECT MAX(product_parser_items.id)
                FROM product_parser_items
                WHERE product_parser_items.sku = products.sku
            )
            WHERE source_parser_item_id IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM product_parser_items
                  WHERE product_parser_items.id = products.source_parser_item_id
              )
              AND EXISTS (
                  SELECT 1 FROM product_parser_items
                  WHERE product_parser_items.sku = products.sku
              )
        SQL);

        DB::table('product_parser_category_learnings')
            ->whereNotIn('source', ['admin_verified', 'catalog_agent_verified'])
            ->delete();
        DB::table('product_parser_category_learnings')
            ->where('key_type', '!=', 'sku')
            ->delete();

        Schema::table('products', function (Blueprint $table): void {
            $table->foreign('source_parser_item_id', 'products_source_parser_item_fk')
                ->references('id')
                ->on('product_parser_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign('products_source_parser_item_fk');
        });
    }
};
