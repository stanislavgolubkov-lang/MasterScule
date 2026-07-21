<?php

namespace App\Services\Catalog;

class ProductSkuContentOverrides
{
    public function apply(string $sku, array $content): array
    {
        $override = $this->for($sku);

        return $override === null ? $content : array_merge($content, $override);
    }

    public function for(string $sku): ?array
    {
        if (trim($sku) !== '082809') {
            return null;
        }

        return [
            'name_ru' => 'Автоматическая сварочная маска GYS 082809 GYSMATIC AUTO PRO TRUE COLOR',
            'name_ro' => 'Mască automată de sudură GYS 082809 GYSMATIC AUTO PRO TRUE COLOR',
            'short_description_ru' => 'Автоматическая сварочная маска GYS GYSMATIC AUTO PRO TRUE COLOR, артикул 082809, с диапазонами затемнения DIN 5–9 и 9–13.',
            'short_description_ro' => 'Mască automată de sudură GYS GYSMATIC AUTO PRO TRUE COLOR, cod 082809, cu intervale de întunecare DIN 5–9 și 9–13.',
            'description_ru' => 'Автоматическая сварочная маска GYS GYSMATIC AUTO PRO TRUE COLOR (артикул 082809) защищает лицо и глаза при MMA, TIG и MIG/MAG сварке. Светофильтр оптического класса 1/1/1/1 имеет светлое состояние DIN 3, диапазоны затемнения DIN 5–9 и 9–13, четыре датчика и время срабатывания 0,08 мс. Размер обзорного окна — 100 × 93 мм; доступны регулировки чувствительности, задержки, затемнения и режим шлифования. Питание — солнечная батарея и две батарейки CR2032, масса — 540 г.',
            'description_ro' => 'Masca automată de sudură GYS GYSMATIC AUTO PRO TRUE COLOR (cod 082809) protejează fața și ochii în timpul sudării MMA, TIG și MIG/MAG. Filtrul cu clasa optică 1/1/1/1 are starea luminoasă DIN 3, intervale de întunecare DIN 5–9 și 9–13, patru senzori și un timp de reacție de 0,08 ms. Câmpul vizual măsoară 100 × 93 mm; sunt disponibile reglaje pentru sensibilitate, întârziere, nuanță și modul de șlefuire. Alimentarea este solară și cu două baterii CR2032, iar greutatea este de 540 g.',
            'needs_translation_review' => false,
            'needs_content_review' => false,
            'generated_content' => false,
            'translation_source_type' => 'curated_sku',
        ];
    }
}
