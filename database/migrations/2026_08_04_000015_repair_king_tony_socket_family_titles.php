<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $socketSizes = [
            '433510SR' => '5/16″',
            '433512SR' => '3/8″',
            '433514SR' => '7/16″',
            '433515SR' => '15/32″',
            '433516SR' => '1/2″',
            '433518SR' => '9/16″',
            '433519SR' => '19/32″',
            '433520SR' => '5/8″',
            '433522SR' => '11/16″',
            '433524SR' => '3/4″',
            '433525SR' => '25/32″',
            '433526SR' => '13/16″',
            '433528SR' => '7/8″',
            '433530SR' => '15/16″',
            '433532SR' => '1″',
            '433534SR' => '1-1/16″',
            '433536SR' => '1-1/8″',
            '433538SR' => '1-3/16″',
            '433540SR' => '1-1/4″',
        ];

        $sets = [
            '2550MRV' => ['1/4″', 'Набор торцевых головок', 'Set de capete tubulare'],
            '2565MRV' => ['1/4″', 'Набор торцевых головок', 'Set de capete tubulare'],
            '9-3544MRV' => ['3/8″', 'Набор торцевых головок', 'Set de capete tubulare'],
            'SC7510MR' => ['1/2″', 'Набор инструментов с торцевыми головками и аксессуарами', 'Set de scule cu capete tubulare și accesorii'],
            'SC7596MR' => ['1/2″', 'Набор инструментов с торцевыми головками и аксессуарами', 'Set de scule cu capete tubulare și accesorii'],
        ];

        DB::transaction(function () use ($socketSizes, $sets): void {
            foreach ($socketSizes as $sku => $size) {
                $this->updateProduct(
                    $sku,
                    "Головка торцевая дюймовая KING TONY {$sku}, {$size}",
                    "Cap tubular în inci KING TONY {$sku}, {$size}",
                    "Торцевая головка KING TONY {$sku} размером {$size} предназначена для работы с крепежом соответствующего дюймового профиля.",
                    "Capul tubular KING TONY {$sku}, cu dimensiunea {$size}, este destinat lucrului cu elemente de fixare cu profil în inci.",
                );
            }

            foreach ($sets as $sku => [$square, $typeRu, $typeRo]) {
                $this->updateProduct(
                    $sku,
                    "{$typeRu} KING TONY {$sku}, квадрат {$square}",
                    "{$typeRo} KING TONY {$sku}, pătrat {$square}",
                    "{$typeRu} KING TONY {$sku} с присоединительным квадратом {$square} предназначен для профессионального монтажа и обслуживания крепежа.",
                    "{$typeRo} KING TONY {$sku}, cu pătrat de antrenare {$square}, este destinat lucrărilor profesionale de montare și întreținere a elementelor de fixare.",
                );
            }
        });
    }

    private function updateProduct(string $sku, string $nameRu, string $nameRo, string $descriptionRu, string $descriptionRo): void
    {
        $productId = DB::table('products')->where('sku', $sku)->value('id');
        if (! $productId) {
            return;
        }

        DB::table('products')->where('id', $productId)->update([
            'name' => $nameRu,
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description' => $descriptionRu,
            'short_description_ru' => $descriptionRu,
            'short_description_ro' => $descriptionRo,
            'description' => $descriptionRu,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Verified family-level corrections are intentionally retained.
    }
};
