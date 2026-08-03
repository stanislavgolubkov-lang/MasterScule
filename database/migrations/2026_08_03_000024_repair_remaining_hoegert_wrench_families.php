<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private string $catalog = 'https://en.hoegert.com/wp-content/uploads/2025/06/CATALOGUE_HT_25_-DE_EN_FR_ES_HR_HU_RO-BG.pdf';

    public function up(): void
    {
        $records = [
            ...$this->ratchetWrenches(),
            ...$this->flexRatchetWrenches(),
            ...$this->combinationWrenches(),
            ...$this->torqueWrenches(),
        ];
        $brandId = DB::table('brands')->where('name', 'Hoegert')->value('id');
        if (! $brandId) {
            return;
        }

        DB::transaction(function () use ($records, $brandId): void {
            foreach ($records as $sku => $content) {
                $product = DB::table('products')
                    ->where('brand_id', $brandId)
                    ->where('sku', $sku)
                    ->first();
                if (! $product) {
                    continue;
                }

                $this->updateProduct($product, $content);
            }
        });
    }

    private function ratchetWrenches(): array
    {
        $records = [];
        foreach ([
            'HT1R016' => 16, 'HT1R017' => 17, 'HT1R018' => 18,
            'HT1R021' => 21, 'HT1R022' => 22, 'HT1R024' => 24,
            'HT1R027' => 27, 'HT1R030' => 30, 'HT1R032' => 32,
        ] as $sku => $size) {
            $records[$sku] = [
                'name_ru' => "Комбинированный ключ с трещоткой HOEGERT {$sku}, {$size} мм, 72 зубца, CrV",
                'name_ro' => "Cheie combinată cu clichet HOEGERT {$sku}, {$size} mm, 72 dinți, CrV",
                'description_ru' => "Комбинированный ключ HOEGERT {$sku} размером {$size} мм изготовлен из хромованадиевой стали. Кольцевая часть имеет 12-гранный профиль и храповой механизм на 72 зубца; рожковая часть расположена под углом 15° для доступа к крепежу.",
                'description_ro' => "Cheia combinată HOEGERT {$sku}, de {$size} mm, este fabricată din oțel crom-vanadiu. Capătul inelar are profil cu 12 laturi și mecanism cu clichet de 72 de dinți; capătul deschis este înclinat la 15° pentru acces la elementele de fixare.",
                'source_url' => $this->catalog,
            ];
        }

        return $records;
    }

    private function flexRatchetWrenches(): array
    {
        $records = [];
        foreach ([
            'HT1R048' => 8, 'HT1R050' => 10, 'HT1R053' => 13,
            'HT1R054' => 14, 'HT1R055' => 15, 'HT1R056' => 16,
            'HT1R057' => 17, 'HT1R058' => 18, 'HT1R059' => 19,
            'HT1R061' => 21,
        ] as $sku => $size) {
            $records[$sku] = [
                'name_ru' => "Шарнирный комбинированный ключ с трещоткой HOEGERT {$sku}, {$size} мм, 72 зубца, CrV",
                'name_ro' => "Cheie combinată articulată cu clichet HOEGERT {$sku}, {$size} mm, 72 dinți, CrV",
                'description_ru' => "Шарнирный комбинированный ключ HOEGERT {$sku} размером {$size} мм изготовлен из хромованадиевой стали. Кольцевая часть с 12-гранным профилем и механизмом на 72 зубца поворачивается в диапазоне 0–90°, а рожковая часть расположена под углом 15°.",
                'description_ro' => "Cheia combinată articulată HOEGERT {$sku}, de {$size} mm, este fabricată din oțel crom-vanadiu. Capătul inelar cu profil în 12 laturi și mecanism de 72 de dinți pivotează între 0 și 90°, iar capătul deschis este înclinat la 15°.",
                'source_url' => $this->catalog,
            ];
        }

        return $records;
    }

    private function combinationWrenches(): array
    {
        $records = [];
        foreach ([
            'HT1W410-1' => 10,
            'HT1W416-1' => 16,
            'HT1W417-1' => 17,
            'HT1W436' => 36,
            'HT1W450' => 50,
        ] as $sku => $size) {
            $records[$sku] = [
                'name_ru' => "Комбинированный ключ HOEGERT {$sku}, {$size} мм, CrV, DIN 3113",
                'name_ro' => "Cheie combinată HOEGERT {$sku}, {$size} mm, CrV, DIN 3113",
                'description_ru' => "Комбинированный ключ HOEGERT {$sku} размером {$size} мм изготовлен из хромованадиевой стали с матовой поверхностью. Рожковая часть расположена под углом 15°; инструмент соответствует стандарту DIN 3113.",
                'description_ro' => "Cheia combinată HOEGERT {$sku}, de {$size} mm, este fabricată din oțel crom-vanadiu și are suprafață mată. Capătul deschis este înclinat la 15°, iar scula respectă standardul DIN 3113.",
                'source_url' => $this->catalog,
            ];
        }

        $records['HT1W492-1'] = [
            'name_ru' => 'Набор комбинированных ключей HOEGERT HT1W492-1, 6–24 мм, 12 предметов, CrV, DIN 3113',
            'name_ro' => 'Set de chei combinate HOEGERT HT1W492-1, 6–24 mm, 12 piese, CrV, DIN 3113',
            'description_ru' => 'Набор HOEGERT HT1W492-1 содержит 12 комбинированных ключей размером от 6 до 24 мм. Ключи изготовлены из хромованадиевой стали и соответствуют стандарту DIN 3113.',
            'description_ro' => 'Setul HOEGERT HT1W492-1 conține 12 chei combinate cu dimensiuni de la 6 la 24 mm. Cheile sunt fabricate din oțel crom-vanadiu și respectă standardul DIN 3113.',
            'source_url' => $this->catalog,
        ];
        $records['HT1W496-1'] = [
            'name_ru' => 'Набор комбинированных ключей HOEGERT HT1W496-1, 6–32 мм, 26 предметов, CrV, DIN 3113',
            'name_ro' => 'Set de chei combinate HOEGERT HT1W496-1, 6–32 mm, 26 piese, CrV, DIN 3113',
            'description_ru' => 'Набор HOEGERT HT1W496-1 содержит 26 комбинированных ключей размером от 6 до 32 мм. Ключи изготовлены из хромованадиевой стали и соответствуют стандарту DIN 3113.',
            'description_ro' => 'Setul HOEGERT HT1W496-1 conține 26 de chei combinate cu dimensiuni de la 6 la 32 mm. Cheile sunt fabricate din oțel crom-vanadiu și respectă standardul DIN 3113.',
            'source_url' => 'https://en.hoegert.com/product/combination-spanners-set-26-pcs-6-32-mm-crv-din-3113-2/',
        ];

        return $records;
    }

    private function torqueWrenches(): array
    {
        $records = [];
        $items = [
            'HT1W707' => ['1/2"', '42–210 Н·м', '42–210 Nm', 'CrV', 24, '±4%', 'https://en.hoegert.com/product/torque-wrench-1-2-42-210nm/'],
            'HT1W710' => ['1/4"', '5–25 Н·м', '5–25 Nm', 'CrMo', 72, '±3%', 'https://en.hoegert.com/product/torque-wrench-1-4-5-25nm-2/'],
            'HT1W711' => ['3/8"', '10–60 Н·м', '10–60 Nm', 'CrMo', 72, '±3%', 'https://en.hoegert.com/product/torque-wrench-3-8-10-60nm/'],
            'HT1W713' => ['1/2"', '40–220 Н·м', '40–220 Nm', 'CrMo', 72, '±3%', 'https://en.hoegert.com/product/torque-wrench-1-2-40-220nm/'],
            'HT1W714' => ['1/2"', '60–330 Н·м', '60–330 Nm', 'CrMo', 72, '±3%', $this->catalog],
        ];

        foreach ($items as $sku => [$drive, $rangeRu, $rangeRo, $material, $teeth, $accuracy, $source]) {
            $records[$sku] = [
                'name_ru' => "Динамометрический ключ HOEGERT {$sku}, {$drive}, {$rangeRu}",
                'name_ro' => "Cheie dinamometrică HOEGERT {$sku}, {$drive}, {$rangeRo}",
                'description_ru' => "Динамометрический ключ HOEGERT {$sku} с квадратом {$drive} имеет рабочий диапазон {$rangeRu}. Механизм на {$teeth} зубца изготовлен из стали {$material}; точность настройки составляет {$accuracy}, предусмотрено переключение направления вращения.",
                'description_ro' => "Cheia dinamometrică HOEGERT {$sku}, cu pătrat de {$drive}, are domeniul de lucru {$rangeRo}. Mecanismul cu {$teeth} de dinți este fabricat din oțel {$material}; precizia reglajului este {$accuracy}, iar sensul de rotație poate fi schimbat.",
                'source_url' => $source,
            ];
        }

        return $records;
    }

    private function updateProduct(object $product, array $content): void
    {
        $now = now();
        $sourceDomain = (string) parse_url($content['source_url'], PHP_URL_HOST);
        $shortRu = Str::limit($content['description_ru'], 240, '');
        $shortRo = Str::limit($content['description_ro'], 240, '');
        $common = [
            'name_ru' => $content['name_ru'],
            'name_ro' => $content['name_ro'],
            'short_description_ru' => $shortRu,
            'short_description_ro' => $shortRo,
            'description_ru' => $content['description_ru'],
            'description_ro' => $content['description_ro'],
            'needs_source_review' => false,
            'needs_content_review' => false,
            'needs_translation_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ];

        DB::table('products')->where('id', $product->id)->update($common + [
            'name' => $content['name_ru'],
            'short_description' => $shortRu,
            'description' => $content['description_ru'],
            'meta_description' => Str::limit($content['description_ru'], 150, ''),
            'source_url' => $content['source_url'],
            'source_domain' => $sourceDomain,
            'source_type' => 'official_manufacturer',
            'parser_confidence' => 100,
            'fallback_source_used' => false,
            'source_reviewed_at' => $now,
        ]);

        $parserUpdates = $common + [
            'found_title' => $content['name_ru'],
            'found_description' => $content['description_ru'],
            'official_source_url' => $content['source_url'],
            'official_source_domain' => $sourceDomain,
            'official_source_confidence' => 100,
            'fallback_source_url' => null,
            'fallback_source_domain' => null,
            'fallback_source_used' => false,
            'source_match_confidence' => 100,
            'content_source_type' => 'official_source',
            'source_reviewed_at' => $now,
        ];
        $query = DB::table('product_parser_items');
        $product->source_parser_item_id
            ? $query->where('id', $product->source_parser_item_id)->update($parserUpdates)
            : $query->where('sku', $product->sku)->update($parserUpdates);
    }

    public function down(): void
    {
        // Verified exact-SKU bilingual content is intentionally retained.
    }
};
