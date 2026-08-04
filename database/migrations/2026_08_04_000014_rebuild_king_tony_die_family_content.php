<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $dimensions = [
            '12939MQ-A23' => 'M3×0,5',
            '12939MQ-A24' => 'M3×0,6',
            '12939MQ-A25' => 'M4×0,7',
            '12939MQ-A26' => 'M4×0,75',
            '12939MQ-A27' => 'M5×0,8',
            '12939MQ-A28' => 'M5×0,9',
            '12939MQ-A29' => 'M6×0,75',
            '12939MQ-A30' => 'M6×1,0',
            '12939MQ-A31' => 'M7×0,75',
            '12939MQ-A32' => 'M7×1,0',
            '12939MQ-A33' => 'M8×1,0',
            '12939MQ-A34' => 'M8×1,25',
            '12939MQ-A35' => 'M10×1,25',
            '12939MQ-A36' => 'M10×1,5',
            '12939MQ-A37' => 'M12×1,5',
            '12939MQ-A38' => 'M12×1,75',
            '12939MQ-A39' => '1/8″ NPT 27',
        ];

        DB::transaction(function () use ($dimensions): void {
            $now = now();

            foreach ($dimensions as $sku => $dimension) {
                $product = DB::table('products')->where('sku', $sku)->first(['id']);
                if (! $product) {
                    continue;
                }

                $nameRu = "Плашка KING TONY {$sku}, {$dimension}";
                $nameRo = "Filieră KING TONY {$sku}, {$dimension}";
                $descriptionRu = "Плашка KING TONY {$sku} предназначена для нарезания и восстановления наружной резьбы {$dimension}. Размер и шаг резьбы указаны в названии и характеристиках товара.";
                $descriptionRo = "Filiera KING TONY {$sku} este destinată executării și refacerii filetelor exterioare {$dimension}. Dimensiunea și pasul filetului sunt indicate în denumirea și caracteristicile produsului.";

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
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Verified family-level corrections are intentionally retained.
    }
};
