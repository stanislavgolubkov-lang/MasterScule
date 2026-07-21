<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $records = array_replace(
            $this->combinationWrenchRecords(),
            $this->impactTorxRecords(),
            $this->hoseRecords(),
        );
        $products = DB::table('products')->whereIn('sku', array_keys($records))->get()->keyBy('sku');

        DB::transaction(function () use ($records, $products): void {
            foreach ($records as $sku => $content) {
                $product = $products->get($sku);
                if (! $product) {
                    continue;
                }

                $this->updateProduct($product, $content);
            }
        });
    }

    private function combinationWrenchRecords(): array
    {
        $sizes = [
            'HT1W419-1' => 19,
            'HT1W421-1' => 21,
            'HT1W424' => 24,
            'HT1W426' => 26,
            'HT1W428' => 28,
            'HT1W430' => 30,
            'HT1W441' => 41,
            'HT1W446' => 46,
        ];

        $records = [];
        foreach ($sizes as $sku => $size) {
            $records[$sku] = [
                'name_ru' => "Комбинированный ключ HOEGERT {$sku}, {$size} мм, CrV, DIN 3113",
                'name_ro' => "Cheie combinată HOEGERT {$sku}, {$size} mm, CrV, DIN 3113",
                'description_ru' => "Комбинированный ключ HOEGERT {$sku} размером {$size} мм изготовлен из хром-ванадиевой стали по стандарту DIN 3113. Сочетает рожковую и накидную рабочие головки.",
                'description_ro' => "Cheia combinată HOEGERT {$sku}, de {$size} mm, este fabricată din oțel crom-vanadiu conform standardului DIN 3113. Combină un capăt fix și unul inelar.",
                'attributes' => [
                    'Тип' => 'Комбинированный ключ',
                    'Размер' => $size.' mm',
                    'Материал' => 'Хром-ванадиевая сталь',
                    'Стандарт' => 'DIN 3113',
                ],
                'needs_image_review' => in_array($sku, ['HT1W441', 'HT1W446'], true),
            ];
        }

        return $records;
    }

    private function impactTorxRecords(): array
    {
        $sizes = [
            'HT4R036' => 'T50',
            'HT4R037' => 'T45',
            'HT4R038' => 'T40',
            'HT4R039' => 'T30',
            'HT4R040' => 'T25',
            'HT4R041' => 'T20',
        ];

        $records = [];
        foreach ($sizes as $sku => $size) {
            $records[$sku] = [
                'name_ru' => "Ударная торцевая насадка TORX HOEGERT {$sku}, {$size}, привод 1/2 дюйма",
                'name_ro' => "Cap tubular de impact TORX HOEGERT {$sku}, {$size}, antrenare 1/2 inch",
                'description_ru' => "Ударная торцевая насадка TORX HOEGERT {$sku} имеет профиль {$size} и привод 1/2 дюйма. Изготовлена методом холодной ковки из хром-молибденовой стали, устойчивой к ударным нагрузкам, деформации, коррозии и износу.",
                'description_ro' => "Capul tubular de impact TORX HOEGERT {$sku} are profil {$size} și antrenare de 1/2 inch. Este fabricat prin forjare la rece din oțel crom-molibden, rezistent la impact, deformare, coroziune și uzură.",
                'attributes' => [
                    'Тип' => 'Ударная торцевая насадка TORX',
                    'Рабочий профиль' => 'TORX',
                    'Размер' => $size,
                    'Посадочный квадрат' => '1/2 inch',
                    'Материал' => 'Хром-молибденовая сталь',
                ],
                'needs_image_review' => $sku === 'HT4R041',
            ];
        }

        return $records;
    }

    private function hoseRecords(): array
    {
        return [
            'HT4R892' => $this->hose('HT4R892', 15, 6, 8, true),
            'HT4R894' => $this->hose('HT4R894', 9, 8, 12, false),
        ];
    }

    private function hose(string $sku, int $length, int $innerDiameter, int $outerDiameter, bool $needsImageReview): array
    {
        return [
            'name_ru' => "Спиральный полиуретановый пневмошланг HOEGERT {$sku}, {$length} м, {$innerDiameter} × {$outerDiameter} мм",
            'name_ro' => "Furtun pneumatic spiralat din poliuretan HOEGERT {$sku}, {$length} m, {$innerDiameter} × {$outerDiameter} mm",
            'description_ru' => "Спиральный полиуретановый пневмошланг HOEGERT {$sku} имеет длину {$length} м, внутренний диаметр {$innerDiameter} мм и наружный диаметр {$outerDiameter} мм. Оснащён наружными фитингами 1/4 дюйма с обеих сторон; рабочее давление — 200 psi, температурный диапазон — от −20 до +60 °C.",
            'description_ro' => "Furtunul pneumatic spiralat din poliuretan HOEGERT {$sku} are lungimea de {$length} m, diametrul interior de {$innerDiameter} mm și diametrul exterior de {$outerDiameter} mm. Este echipat la ambele capete cu racorduri exterioare de 1/4 inch; presiunea de lucru este 200 psi, iar intervalul de temperatură este de la −20 la +60 °C.",
            'attributes' => [
                'Тип' => 'Спиральный пневматический шланг',
                'Материал' => 'Полиуретан',
                'Длина' => $length.' m',
                'Внутренний диаметр' => $innerDiameter.' mm',
                'Наружный диаметр' => $outerDiameter.' mm',
                'Резьба' => '1/4 inch, наружная с двух сторон',
                'Рабочее давление' => '200 psi',
                'Рабочая температура' => '−20…+60 °C',
            ],
            'needs_image_review' => $needsImageReview,
        ];
    }

    private function updateProduct(object $product, array $content): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        DB::table('products')->where('id', $product->id)->update([
            'name' => $content['name_ru'],
            'name_ru' => $content['name_ru'],
            'name_ro' => $content['name_ro'],
            'short_description' => $content['description_ru'],
            'short_description_ru' => $content['description_ru'],
            'short_description_ro' => $content['description_ro'],
            'description' => $content['description_ru'],
            'description_ru' => $content['description_ru'],
            'description_ro' => $content['description_ro'],
            'attributes' => $attributes,
            'needs_image_review' => $content['needs_image_review'],
            'needs_content_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ]);

        if (! $product->source_parser_item_id) {
            return;
        }

        $parserUpdates = [
            'name_ru' => $content['name_ru'],
            'name_ro' => $content['name_ro'],
            'short_description_ru' => $content['description_ru'],
            'short_description_ro' => $content['description_ro'],
            'description_ru' => $content['description_ru'],
            'description_ro' => $content['description_ro'],
            'found_title' => $content['name_ru'],
            'found_description' => $content['description_ru'],
            'found_specs_json' => $attributes,
            'needs_image_review' => $content['needs_image_review'],
            'needs_content_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ];
        if ($content['needs_image_review']) {
            $parserUpdates['image_reviewed_at'] = null;
        }

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update($parserUpdates);
    }

    public function down(): void
    {
        // Curated HOEGERT SKU-family content is intentionally retained.
    }
};
