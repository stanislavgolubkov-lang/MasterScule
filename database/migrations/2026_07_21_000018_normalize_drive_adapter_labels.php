<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $product = DB::table('products')->where('sku', '2813')->first();
        if (! $product) {
            return;
        }

        $nameRu = 'Переходной адаптер KING TONY 2813, вход 1/4 дюйма (F), выход 3/8 дюйма (M)';
        $nameRo = 'Adaptor de trecere KING TONY 2813, intrare 1/4 inch (F), ieșire 3/8 inch (M)';
        $attributes = json_encode([
            'Тип' => 'Переходной адаптер',
            'Входной квадрат' => '1/4 inch (F)',
            'Выходной квадрат' => '3/8 inch (M)',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $now = now();

        DB::transaction(function () use ($product, $nameRu, $nameRo, $attributes, $now): void {
            DB::table('products')->where('id', $product->id)->update([
                'name' => $nameRu,
                'name_ru' => $nameRu,
                'name_ro' => $nameRo,
                'attributes' => $attributes,
                'updated_at' => $now,
            ]);

            if ($product->source_parser_item_id) {
                DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                    'name_ru' => $nameRu,
                    'name_ro' => $nameRo,
                    'found_title' => $nameRu,
                    'found_specs_json' => $attributes,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Normalized labels are intentionally retained.
    }
};
