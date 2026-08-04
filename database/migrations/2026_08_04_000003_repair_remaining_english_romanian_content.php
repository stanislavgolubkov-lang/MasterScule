<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (['7990R', '79900R-04', '7990A0'] as $sku) {
                DB::table('products')->where('sku', $sku)->update([
                    'name' => "Пневматический продувочный пистолет KING TONY {$sku}",
                    'name_ru' => "Пневматический продувочный пистолет KING TONY {$sku}",
                    'name_ro' => "Pistol pneumatic de suflat KING TONY {$sku}",
                    'short_description' => "Пневматический продувочный пистолет KING TONY {$sku} со стальным соплом.",
                    'short_description_ru' => "Пневматический продувочный пистолет KING TONY {$sku} со стальным соплом.",
                    'short_description_ro' => "Pistol pneumatic de suflat KING TONY {$sku}, cu duză din oțel.",
                    'description' => "Пневматический продувочный пистолет KING TONY {$sku} предназначен для очистки сжатым воздухом. Оснащён стальным соплом и рукояткой из армированного нейлона. Рекомендуемое давление — 90 psi; рабочий диапазон — 75–120 psi (5,3–8,4 кгс/см²), максимальное давление — 200 psi (14,07 кгс/см²). Расход воздуха: 350 л/мин со шлангом 1/4 дюйма и 430 л/мин со шлангом 3/8 дюйма. Уровень звукового давления — 85–90 дБ.",
                    'description_ru' => "Пневматический продувочный пистолет KING TONY {$sku} предназначен для очистки сжатым воздухом. Оснащён стальным соплом и рукояткой из армированного нейлона. Рекомендуемое давление — 90 psi; рабочий диапазон — 75–120 psi (5,3–8,4 кгс/см²), максимальное давление — 200 psi (14,07 кгс/см²). Расход воздуха: 350 л/мин со шлангом 1/4 дюйма и 430 л/мин со шлангом 3/8 дюйма. Уровень звукового давления — 85–90 дБ.",
                    'description_ro' => "Pistolul pneumatic de suflat KING TONY {$sku} este destinat curățării cu aer comprimat. Este prevăzut cu duză din oțel și mâner din nailon armat. Presiunea recomandată este de 90 psi, intervalul de lucru este de 75–120 psi (5,3–8,4 kgf/cm²), iar presiunea maximă este de 200 psi (14,07 kgf/cm²). Consumul de aer este de 350 l/min cu furtun de 1/4 inch și 430 l/min cu furtun de 3/8 inch. Nivelul presiunii acustice este de 85–90 dB.",
                    'updated_at' => now(),
                ]);
            }

            DB::table('products')->where('sku', '9TH41-XL')->update([
                'name' => 'Лёгкие рабочие перчатки из полиуретана KING TONY 9TH41-XL, размер XL',
                'name_ru' => 'Лёгкие рабочие перчатки из полиуретана KING TONY 9TH41-XL, размер XL',
                'name_ro' => 'Mănuși de lucru ușoare din poliuretan KING TONY 9TH41-XL, mărimea XL',
                'short_description' => 'Лёгкие рабочие перчатки KING TONY 9TH41-XL с полиуретановой ладонью, размер XL.',
                'short_description_ru' => 'Лёгкие рабочие перчатки KING TONY 9TH41-XL с полиуретановой ладонью, размер XL.',
                'short_description_ro' => 'Mănuși de lucru ușoare KING TONY 9TH41-XL, cu palmă din poliuretan, mărimea XL.',
                'description' => 'Рабочие перчатки KING TONY 9TH41-XL обеспечивают хорошую чувствительность, надёжный захват и комфорт. Сетчатая ткань улучшает вентиляцию, а эластичная манжета с застёжкой-липучкой обеспечивает плотную посадку. Соответствуют EN 388, уровень защиты 2121X.',
                'description_ru' => 'Рабочие перчатки KING TONY 9TH41-XL обеспечивают хорошую чувствительность, надёжный захват и комфорт. Сетчатая ткань улучшает вентиляцию, а эластичная манжета с застёжкой-липучкой обеспечивает плотную посадку. Соответствуют EN 388, уровень защиты 2121X.',
                'description_ro' => 'Mănușile de lucru KING TONY 9TH41-XL oferă sensibilitate bună, prindere sigură și confort. Materialul tip plasă asigură aerisirea, iar manșeta elastică cu închidere velcro permite o fixare fermă. Respectă EN 388, nivel de protecție 2121X.',
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // Verified catalog corrections are intentionally not reverted.
    }
};
