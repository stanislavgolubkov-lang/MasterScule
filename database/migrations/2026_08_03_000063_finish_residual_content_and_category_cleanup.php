<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $content = [
            'TRF40753' => [
                'ru' => 'Стойка трансмиссионная с педалью Torin TRF40753, 0,75 т, 1360–2030 мм',
                'ro' => 'Cric de transmisie cu pedală Torin TRF40753, 0,75 t, 1360–2030 mm',
            ],
            'TRF3201' => [
                'ru' => 'Складная автомобильная опора Torin TRF3201 Heavy Duty, 12 т, 456–710 мм',
                'ro' => 'Suport auto pliabil Torin TRF3201 Heavy Duty, 12 t, 456–710 mm',
            ],
            'TRF3202' => [
                'ru' => 'Складная автомобильная опора Torin TRF3202 Heavy Duty, 12 т, 710–1065 мм',
                'ro' => 'Suport auto pliabil Torin TRF3202 Heavy Duty, 12 t, 710–1065 mm',
            ],
            'T83502' => [
                'ru' => 'Подкатной гидравлический домкрат Torin T83502 с педалью, 3,5 т, 145–500 мм',
                'ro' => 'Cric hidraulic tip cărucior Torin T83502 cu pedală, 3,5 t, 145–500 mm',
            ],
            'TY12001' => [
                'ru' => 'Настольный гидравлический пресс Torin TY12001, 12 т',
                'ro' => 'Presă hidraulică de banc Torin TY12001, 12 t',
            ],
        ];

        DB::transaction(function () use ($content, $now): void {
            foreach ($content as $sku => $copy) {
                $descriptionRu = $copy['ru'].'. Характеристики и комплектация приведены в карточке товара.';
                $descriptionRo = $copy['ro'].'. Caracteristicile și conținutul livrării sunt indicate în fișa produsului.';
                DB::table('products')->where('sku', $sku)->update([
                    'description' => $descriptionRu,
                    'description_ru' => $descriptionRu,
                    'description_ro' => $descriptionRo,
                    'updated_at' => $now,
                ]);
            }

            $this->moveProducts(['SK-1010'], 'pistoale-pentru-silicon-si-gresare', $now);
            $this->moveProducts(['HT1S997', 'HT1S998'], 'instrumente-izolate-vde', $now);
        });
    }

    private function moveProducts(array $skus, string $slug, $now): void
    {
        $targetId = DB::table('categories')->where('slug', $slug)->value('id');
        if (! $targetId) {
            return;
        }

        $products = DB::table('products')->whereIn('sku', $skus)->get(['id', 'sku', 'category_id']);
        foreach ($products as $product) {
            if ((int) $product->category_id === (int) $targetId) {
                continue;
            }

            DB::table('products')->where('id', $product->id)->update([
                'category_id' => $targetId,
                'needs_category_review' => false,
                'updated_at' => $now,
            ]);
            DB::table('category_product')->where('product_id', $product->id)->delete();
            DB::table('category_product')->insert([
                'product_id' => $product->id,
                'category_id' => $targetId,
                'is_primary' => true,
                'source' => 'verified_residual_catalog_cleanup_2026_08_03',
                'confidence' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Verified catalog corrections are intentionally irreversible.
    }
};
