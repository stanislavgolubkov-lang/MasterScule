<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $product = DB::table('products')->where('sku', '711125H')->first();
        $categoryId = DB::table('categories')->where('slug', 'biti-insertii-adaptoare')->value('id');

        if (! $product || ! $categoryId) {
            return;
        }

        $nameRu = 'Ударная бита KING TONY 711125H, HEX H2.5, хвостовик 1/4 дюйма, 110 мм';
        $nameRo = 'Bit de impact KING TONY 711125H, HEX H2.5, prindere 1/4 inch, 110 mm';
        $descriptionRu = 'Ударная бита KING TONY 711125H имеет рабочий профиль HEX H2.5, хвостовик 1/4 дюйма и длину 110 мм.';
        $descriptionRo = 'Bitul de impact KING TONY 711125H are profil de lucru HEX H2.5, prindere de 1/4 inch și lungime de 110 mm.';
        $attributes = json_encode([
            'Тип' => 'Ударная бита',
            'Рабочий профиль' => 'HEX',
            'Размер' => 'H2.5',
            'Посадочное место' => '1/4 inch',
            'Длина' => '110 mm',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $now = now();

        DB::transaction(function () use ($product, $categoryId, $nameRu, $nameRo, $descriptionRu, $descriptionRo, $attributes, $now): void {
            DB::table('products')->where('id', $product->id)->update([
                'category_id' => $categoryId,
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
                'needs_category_review' => false,
                'needs_content_review' => false,
                'generated_content' => false,
                'updated_at' => $now,
            ]);

            DB::table('category_product')->where('product_id', $product->id)->delete();
            DB::table('category_product')->insert([
                'product_id' => $product->id,
                'category_id' => $categoryId,
                'is_primary' => true,
                'source' => 'verified_decimal_hex_bit_repair_2026_07_21',
                'confidence' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($product->source_parser_item_id) {
                DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                    'category_id' => $categoryId,
                    'detected_category_id' => $categoryId,
                    'detected_category_path' => 'biti-insertii-adaptoare',
                    'category_confidence_score' => 100,
                    'category_detection_method' => 'verified_decimal_hex_bit_repair_2026_07_21',
                    'needs_category_review' => false,
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
