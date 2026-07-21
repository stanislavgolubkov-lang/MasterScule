<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $isContaminated = static fn (?string $value): bool => preg_match(
            '/(?:maximum|maxim)\s*\.\s*md|\+?373\s*\(?22\)?\s*54[-\s]*54[-\s]*54/iu',
            (string) $value,
        ) === 1;

        $cleanName = static function (?string $value): string {
            $value = preg_replace('/\b(?:https?:\/\/)?(?:www\.)?(?:maximum|maxim)\.md\b/iu', '', (string) $value) ?? (string) $value;

            return trim(preg_replace('/\s+/u', ' ', $value) ?? $value, " \t\n\r\0\x0B-–—:|");
        };

        $content = static function (string $sku, string $nameRu, string $nameRo): array {
            if ($sku === '082809') {
                $nameRu = 'Автоматическая сварочная маска GYS 082809 GYSMATIC AUTO PRO TRUE COLOR';
                $nameRo = 'Mască automată de sudură GYS 082809 GYSMATIC AUTO PRO TRUE COLOR';
                $shortRu = 'Автоматическая сварочная маска GYS GYSMATIC AUTO PRO TRUE COLOR, артикул 082809, с диапазонами затемнения DIN 5–9 и 9–13.';
                $shortRo = 'Mască automată de sudură GYS GYSMATIC AUTO PRO TRUE COLOR, cod 082809, cu intervale de întunecare DIN 5–9 și 9–13.';
                $descriptionRu = 'Автоматическая сварочная маска GYS GYSMATIC AUTO PRO TRUE COLOR (артикул 082809) защищает лицо и глаза при MMA, TIG и MIG/MAG сварке. Светофильтр оптического класса 1/1/1/1 имеет светлое состояние DIN 3, диапазоны затемнения DIN 5–9 и 9–13, четыре датчика и время срабатывания 0,08 мс. Размер обзорного окна — 100 × 93 мм; доступны регулировки чувствительности, задержки, затемнения и режим шлифования. Питание — солнечная батарея и две батарейки CR2032, масса — 540 г.';
                $descriptionRo = 'Masca automată de sudură GYS GYSMATIC AUTO PRO TRUE COLOR (cod 082809) protejează fața și ochii în timpul sudării MMA, TIG și MIG/MAG. Filtrul cu clasa optică 1/1/1/1 are starea luminoasă DIN 3, intervale de întunecare DIN 5–9 și 9–13, patru senzori și un timp de reacție de 0,08 ms. Câmpul vizual măsoară 100 × 93 mm; sunt disponibile reglaje pentru sensibilitate, întârziere, nuanță și modul de șlefuire. Alimentarea este solară și cu două baterii CR2032, iar greutatea este de 540 g.';

                return compact('nameRu', 'nameRo', 'shortRu', 'shortRo', 'descriptionRu', 'descriptionRo');
            }

            $nameRu = $nameRu !== '' ? $nameRu : 'Товар GYS '.$sku;
            $nameRo = $nameRo !== '' ? $nameRo : 'Produs GYS '.$sku;
            $shortRu = $nameRu.'. Артикул производителя GYS: '.$sku.'.';
            $shortRo = $nameRo.'. Cod de producător GYS: '.$sku.'.';
            $descriptionRu = $nameRu.'. Товар идентифицируется по артикулу производителя GYS '.$sku.'. Используйте артикул '.$sku.' для проверки модели, комплектации и совместимости перед заказом. Основные параметры и доступные характеристики приведены в карточке товара.';
            $descriptionRo = $nameRo.'. Produsul este identificat prin codul de producător GYS '.$sku.'. Folosiți codul '.$sku.' pentru verificarea modelului, a setului de livrare și a compatibilității înainte de comandă. Parametrii principali și caracteristicile disponibile sunt indicate în fișa produsului.';

            return compact('nameRu', 'nameRo', 'shortRu', 'shortRo', 'descriptionRu', 'descriptionRo');
        };

        $productTextColumns = [
            'name', 'name_ru', 'name_ro',
            'short_description', 'short_description_ru', 'short_description_ro',
            'description', 'description_ru', 'description_ro',
            'meta_title', 'meta_description',
        ];

        DB::table('products')
            ->select(array_merge(['id', 'sku'], $productTextColumns))
            ->where(function ($query) use ($productTextColumns): void {
                foreach ($productTextColumns as $column) {
                    $query->orWhere($column, 'like', '%maximum.md%')
                        ->orWhere($column, 'like', '%maxim.md%')
                        ->orWhere($column, 'like', '%54-54-54%');
                }
            })
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($cleanName, $content, $isContaminated): void {
                foreach ($products as $product) {
                    $values = (array) $product;
                    if (! collect($values)->contains(fn ($value) => is_string($value) && $isContaminated($value))) {
                        continue;
                    }

                    $sku = trim((string) $product->sku);
                    $replacement = $content(
                        $sku,
                        $cleanName($product->name_ru ?: $product->name),
                        $cleanName($product->name_ro),
                    );

                    DB::table('products')->where('id', $product->id)->update([
                        'name' => $replacement['nameRu'],
                        'name_ru' => $replacement['nameRu'],
                        'name_ro' => $replacement['nameRo'],
                        'short_description' => $replacement['shortRu'],
                        'short_description_ru' => $replacement['shortRu'],
                        'short_description_ro' => $replacement['shortRo'],
                        'description' => $replacement['descriptionRu'],
                        'description_ru' => $replacement['descriptionRu'],
                        'description_ro' => $replacement['descriptionRo'],
                        'meta_title' => $replacement['nameRu'].' | '.config('store.domain_label'),
                        'meta_description' => mb_strimwidth($replacement['descriptionRu'], 0, 155, '…'),
                        'needs_content_review' => false,
                        'needs_translation_review' => false,
                        'generated_content' => true,
                        'updated_at' => now(),
                    ]);
                }
            });

        $parserTextColumns = [
            'found_description',
            'short_description_ru', 'short_description_ro',
            'description_ru', 'description_ro',
        ];

        DB::table('product_parser_items')
            ->select(array_merge(['id', 'sku', 'brand', 'raw_name', 'name_ru', 'name_ro'], $parserTextColumns))
            ->where(function ($query) use ($parserTextColumns): void {
                foreach ($parserTextColumns as $column) {
                    $query->orWhere($column, 'like', '%maximum.md%')
                        ->orWhere($column, 'like', '%maxim.md%')
                        ->orWhere($column, 'like', '%54-54-54%');
                }
            })
            ->orderBy('id')
            ->chunkById(100, function ($items) use ($cleanName, $content): void {
                foreach ($items as $item) {
                    $sku = trim((string) $item->sku);
                    $replacement = $content(
                        $sku,
                        $cleanName($item->name_ru ?: trim(($item->brand ?: 'GYS').' '.$sku.' '.($item->raw_name ?: ''))),
                        $cleanName($item->name_ro),
                    );

                    DB::table('product_parser_items')->where('id', $item->id)->update([
                        'found_title' => $replacement['nameRu'],
                        'found_description' => null,
                        'name_ru' => $replacement['nameRu'],
                        'name_ro' => $replacement['nameRo'],
                        'short_description_ru' => $replacement['shortRu'],
                        'short_description_ro' => $replacement['shortRo'],
                        'description_ru' => $replacement['descriptionRu'],
                        'description_ro' => $replacement['descriptionRo'],
                        'needs_content_review' => false,
                        'needs_translation_review' => false,
                        'generated_content' => true,
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Third-party promotional copy is intentionally not restored.
    }
};
