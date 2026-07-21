<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $product = DB::table('products')->where('sku', '49152122M')->first();
        if (! $product) {
            return;
        }

        $nameRu = 'Двусторонняя ударная головка KING TONY 49152122M, 21 × 22 мм, привод 1/2 дюйма';
        $nameRo = 'Cap tubular de impact cu două capete KING TONY 49152122M, 21 × 22 mm, antrenare 1/2 inch';
        $descriptionRu = 'Двусторонняя ударная головка KING TONY 49152122M имеет рабочие размеры 21 × 22 мм и привод 1/2 дюйма.';
        $descriptionRo = 'Capul tubular de impact cu două capete KING TONY 49152122M are dimensiunile de lucru 21 × 22 mm și antrenare de 1/2 inch.';
        $attributes = json_encode([
            'Тип' => 'Двусторонняя ударная головка',
            'Размер' => '21 × 22 mm',
            'Посадочный квадрат' => '1/2 inch',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $now = now();

        DB::transaction(function () use ($product, $nameRu, $nameRo, $descriptionRu, $descriptionRo, $attributes, $now): void {
            DB::table('products')->where('id', $product->id)->update([
                'name' => $nameRu,
                'name_ru' => $nameRu,
                'name_ro' => $nameRo,
                'short_description' => $descriptionRu,
                'short_description_ru' => $descriptionRu,
                'short_description_ro' => $descriptionRo,
                'description' => $descriptionRu,
                'description_ru' => $descriptionRu,
                'description_ro' => $descriptionRo,
                'attributes' => $attributes,
                'needs_content_review' => false,
                'generated_content' => false,
                'updated_at' => $now,
            ]);

            if ($product->source_parser_item_id) {
                DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                    'name_ru' => $nameRu,
                    'name_ro' => $nameRo,
                    'short_description_ru' => $descriptionRu,
                    'short_description_ro' => $descriptionRo,
                    'description_ru' => $descriptionRu,
                    'description_ro' => $descriptionRo,
                    'found_title' => $nameRu,
                    'found_description' => $descriptionRu,
                    'found_specs_json' => $attributes,
                    'needs_content_review' => false,
                    'generated_content' => false,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Curated exact-SKU content is intentionally retained.
    }
};
