<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-hoegert-sku-review';

    public function up(): void
    {
        $this->ensureCategories();
        $records = $this->records();
        $categoryIds = DB::table('categories')
            ->whereIn('slug', collect($records)->pluck('category')->filter()->unique()->all())
            ->pluck('id', 'slug');

        DB::transaction(function () use ($records, $categoryIds): void {
            foreach ($records as $sku => $content) {
                $product = DB::table('products')->where('sku', $sku)->first();
                if (! $product) {
                    continue;
                }

                $categoryId = $content['category'] ? $categoryIds->get($content['category']) : null;
                $this->updateProduct($product, $content, $categoryId ? (int) $categoryId : null);
            }
        });
    }

    private function records(): array
    {
        return [
            'HT1W854' => [
                'name_ru' => 'Т-образный шестигранный ключ HOEGERT HT1W854, 4 мм, шаровой наконечник',
                'name_ro' => 'Cheie hexagonală în T HOEGERT HT1W854, 4 mm, cap sferic',
                'description_ru' => 'Т-образный шестигранный ключ HOEGERT HT1W854 размером 4 мм изготовлен из высококачественной стали S2. Удлинённый стержень длиной 100 мм увеличивает рычаг, а шаровой наконечник позволяет работать под углом до 30°. Ширина Т-образной рукоятки составляет 75 мм.',
                'description_ro' => 'Cheia hexagonală în T HOEGERT HT1W854, de 4 mm, este fabricată din oțel S2 de calitate superioară. Tija lungă de 100 mm mărește pârghia, iar capul sferic permite lucrul la un unghi de până la 30°. Lățimea mânerului în T este de 75 mm.',
                'attributes' => [
                    'Тип' => 'Т-образный шестигранный ключ с шаровым наконечником',
                    'Размер' => '4 mm',
                    'Материал' => 'Высококачественная сталь S2',
                    'Длина' => '100 mm',
                    'Ширина рукоятки' => '75 mm',
                    'Максимальный рабочий угол' => '30°',
                ],
                'category' => null,
                'source_url' => 'https://en.hoegert.com/product/hexagonal-wrenches-type-t-with-ball-long-4/',
                'image_url' => 'https://hoegert.com/wp-content/uploads/2023/03/HT1W854.4c71b19a.png',
            ],
            'HT3B618' => [
                'name_ru' => 'Поворотные слесарные тиски HOEGERT HT3B618, губки 150 мм, основание 360°',
                'name_ro' => 'Menghină de banc rotativă HOEGERT HT3B618, fălci 150 mm, bază 360°',
                'description_ru' => 'Слесарные тиски HOEGERT HT3B618 с губками шириной 150 мм имеют поворотное на 360° основание и корпус из прочного ковкого чугуна. Закалённые стальные губки с перекрёстной насечкой, встроенная наковальня, закалённая резьба и крепление основания двумя болтами рассчитаны на интенсивную работу.',
                'description_ro' => 'Menghina de banc HOEGERT HT3B618, cu fălci de 150 mm, are bază rotativă la 360° și corp robust din fontă ductilă. Fălcile călite din oțel cu striații încrucișate, nicovala integrată, filetul călit și fixarea bazei cu două șuruburi sunt destinate lucrărilor intensive.',
                'attributes' => [
                    'Тип' => 'Поворотные слесарные тиски',
                    'Ширина губок' => '150 mm',
                    'Угол поворота основания' => '360°',
                    'Материал' => 'Ковкий чугун',
                    'Вес' => '13,6 kg',
                ],
                'category' => null,
                'source_url' => 'https://en.hoegert.com/product/swivel-bench-vise-150-mm/',
                'image_url' => 'https://hoegert.com/wp-content/uploads/2021/09/HT3B618.ebf5baa6.png',
            ],
            'HT3B651' => [
                'name_ru' => 'Магнитный угольник для сварки HOEGERT HT3B651, 22,5 кг, 45°/90°/135°',
                'name_ro' => 'Echer magnetic pentru sudură HOEGERT HT3B651, 22,5 kg, 45°/90°/135°',
                'description_ru' => 'Магнитный сварочный угольник HOEGERT HT3B651 стреловидной формы удерживает стальные детали с усилием до 22,5 кг и позволяет выставлять углы 45°, 90° и 135°. Корпус размером 155 × 102 мм изготовлен из толстой стали с порошковым покрытием; центральное отверстие подходит для труб диаметром до 28 мм.',
                'description_ro' => 'Echerul magnetic pentru sudură HOEGERT HT3B651, în formă de săgeată, susține piese din oțel cu o forță de până la 22,5 kg și permite poziționarea la 45°, 90° și 135°. Corpul de 155 × 102 mm este realizat din tablă groasă de oțel vopsită în câmp electrostatic; orificiul central permite fixarea țevilor cu diametrul de până la 28 mm.',
                'attributes' => [
                    'Тип' => 'Магнитный угольник для сварки',
                    'Удерживающее усилие' => '22,5 kg',
                    'Рабочие углы' => '45° / 90° / 135°',
                    'Габаритные размеры' => '155 × 102 mm',
                    'Максимальный диаметр трубы' => '28 mm',
                    'Форма' => 'Стрела',
                    'Материал' => 'Сталь',
                ],
                'category' => 'accesorii-pentru-sudura',
                'source_url' => 'https://hoegert.com/produkt/magnetyczny-katownik-spawalniczy-strzalkowy-225-kg/',
                'image_url' => 'https://hoegert.com/wp-content/uploads/2021/09/HT3B651.04b2a0c2.png',
            ],
            'HT7G120-1' => [
                'name_ru' => 'Набор комбинированных ключей HOEGERT HT7G120-1, 6–21 мм, 16 предметов, EVA',
                'name_ro' => 'Set de chei combinate HOEGERT HT7G120-1, 6–21 mm, 16 piese, EVA',
                'description_ru' => 'Набор HOEGERT HT7G120-1 содержит 16 комбинированных ключей HT1W406–HT1W421 размеров от 6 до 21 мм. Ключи изготовлены из хром-ванадиевой стали и размещены в ложементе из технической пены EVA для инструментального шкафа.',
                'description_ro' => 'Setul HOEGERT HT7G120-1 conține 16 chei combinate HT1W406–HT1W421, cu dimensiuni de la 6 la 21 mm. Cheile sunt fabricate din oțel crom-vanadiu și sunt așezate într-o inserție din spumă tehnică EVA pentru dulapul de scule.',
                'attributes' => [
                    'Тип' => 'Набор комбинированных ключей в ложементе',
                    'Количество предметов' => '16',
                    'Размеры ключей' => '6–21 mm',
                    'Материал' => 'Хром-ванадиевая сталь',
                    'Материал ложемента' => 'Техническая пена EVA',
                    'Комплектация' => 'HOEGERT HT1W406–HT1W421',
                ],
                'category' => null,
                'source_url' => 'https://en.hoegert.com/product/combination-wrench-set-16-pcs-technical-foam-2/',
                'image_url' => 'https://hoegert.com/wp-content/uploads/2024/11/HT7G120-1.ac3b4c5e.png',
            ],
            'HT7G139' => [
                'name_ru' => 'Набор изолированных инструментов HOEGERT HT7G139, 1000 В, 5 предметов, EVA',
                'name_ro' => 'Set de scule izolate HOEGERT HT7G139, 1000 V, 5 piese, EVA',
                'description_ru' => 'Набор HOEGERT HT7G139 в ложементе содержит пять электромонтажных инструментов: изолированные клещи HT1P909 и HT1P903 на 1000 В, отвёртки HT1S968 и HT1S969, а также индикатор напряжения HT1S981. Инструменты изготовлены из стали CrV/CrMo и размещены в технической пене EVA.',
                'description_ro' => 'Setul HOEGERT HT7G139 în inserție conține cinci scule pentru lucrări electrice: cleștii izolați HT1P909 și HT1P903 pentru 1000 V, șurubelnițele HT1S968 și HT1S969 și indicatorul de tensiune HT1S981. Sculele sunt fabricate din oțel CrV/CrMo și sunt așezate în spumă tehnică EVA.',
                'attributes' => [
                    'Тип' => 'Набор изолированных инструментов',
                    'Количество предметов' => '5',
                    'Максимальное рабочее напряжение' => '1000 V',
                    'Материал' => 'CrV / CrMo',
                    'Материал ложемента' => 'Техническая пена EVA',
                    'Комплектация' => 'HT1P909, HT1P903, HT1S968, HT1S969, HT1S981',
                ],
                'category' => 'seturi-scule-electrice-izolate',
                'source_url' => 'https://en.hoegert.com/product/tool-set-4/',
                'image_url' => 'https://hoegert.com/wp-content/uploads/2021/09/HT7G139.3cbf6089.png',
            ],
            'HT8G011' => [
                'name_ru' => 'Алкотестер HOEGERT HT8G011 с LCD-дисплеем, диапазон 0,08–1,99 ‰ BAC',
                'name_ro' => 'Alcooltest HOEGERT HT8G011 cu afișaj LCD, interval 0,08–1,99 ‰ BAC',
                'description_ru' => 'Алкотестер HOEGERT HT8G011 с полупроводниковым датчиком измеряет содержание алкоголя в выдыхаемом воздухе в диапазоне 0,08–1,99 ‰ BAC и сохраняет последние 10 результатов. Время подготовки составляет 10 секунд, ресурс — 1000 циклов, питание — встроенный Li-Ion аккумулятор 3,7 В ёмкостью 300 мА·ч.',
                'description_ro' => 'Alcooltestul HOEGERT HT8G011, cu senzor semiconductor, măsoară alcoolul din aerul expirat în intervalul 0,08–1,99 ‰ BAC și memorează ultimele 10 rezultate. Timpul de pregătire este de 10 secunde, durata nominală de 1000 de cicluri, iar alimentarea este asigurată de un acumulator Li-Ion de 3,7 V și 300 mAh.',
                'attributes' => [
                    'Тип' => 'Алкотестер',
                    'Тип датчика' => 'Полупроводниковый',
                    'Диапазон измерения' => '0,08–1,99 ‰ BAC / 0,008–0,199 % BAC',
                    'Допустимая погрешность' => '±0,018 % BAC',
                    'Время готовности' => '10 s',
                    'Запомненные результаты' => '10',
                    'Количество циклов' => '1000',
                    'Напряжение аккумулятора' => '3,7 V',
                    'Ёмкость аккумулятора' => '300 mAh',
                    'Материал' => 'ABS',
                ],
                'category' => 'alcooltestere',
                'source_url' => 'https://en.hoegert.com/product/breathalyzer-with-lcd-display/',
                'image_url' => 'https://hoegert.com/wp-content/uploads/2024/12/HT8G011_6.b6102afc.jpg',
            ],
            'HT8G393' => [
                'name_ru' => 'Съёмник обивочных клипс HOEGERT HT8G393, 230 мм',
                'name_ro' => 'Extractor pentru cleme de tapițerie HOEGERT HT8G393, 230 mm',
                'description_ru' => 'Съёмник HOEGERT HT8G393 предназначен для безопасного извлечения клипс обивки и кузовных панелей без повреждения деталей. Стальной вильчатый наконечник имеет внешнюю ширину 18 мм, рабочую ширину 9 мм и глубину 13 мм; общая длина инструмента составляет 230 мм.',
                'description_ro' => 'Extractorul HOEGERT HT8G393 este destinat demontării în siguranță a clemelor de tapițerie și a panourilor caroseriei, fără deteriorarea pieselor. Vârful din oțel, de tip furcă, are lățimea exterioară de 18 mm, lățimea de lucru de 9 mm și adâncimea de 13 mm; lungimea totală este de 230 mm.',
                'attributes' => [
                    'Тип' => 'Съёмник обивочных клипс',
                    'Исполнение' => 'Вильчатый',
                    'Материал' => 'Сталь',
                    'Длина' => '230 mm',
                    'Ширина наконечника' => '18 mm',
                    'Рабочая ширина' => '9 mm',
                    'Глубина наконечника' => '13 mm',
                ],
                'category' => 'extractoare-cleme-tapiterie',
                'source_url' => 'https://en.hoegert.com/product/clamp-for-upholstery-pins-230-mm/',
                'image_url' => 'https://hoegert.com/wp-content/uploads/2021/10/HT8G393.9eb8062b.png',
            ],
        ];
    }

    private function updateProduct(object $product, array $content, ?int $categoryId): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sourceDomain = parse_url($content['source_url'], PHP_URL_HOST);

        $updates = [
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
            'source_url' => $content['source_url'],
            'source_domain' => $sourceDomain,
            'source_type' => 'official_manufacturer',
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'needs_image_review' => false,
            'needs_content_review' => false,
            'generated_content' => false,
            'source_reviewed_at' => $now,
            'updated_at' => $now,
        ];

        if ($categoryId) {
            $updates['category_id'] = $categoryId;
            $updates['needs_category_review'] = false;
        }

        DB::table('products')->where('id', $product->id)->update($updates);

        if ($categoryId) {
            $this->syncCategory($product, $categoryId, $content['category'], $now);
        }

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
            'found_images_json' => json_encode([$content['image_url']], JSON_UNESCAPED_SLASHES),
            'selected_images_json' => json_encode([$content['image_url']], JSON_UNESCAPED_SLASHES),
            'official_source_url' => $content['source_url'],
            'official_source_domain' => $sourceDomain,
            'official_source_confidence' => 100,
            'fallback_source_url' => null,
            'fallback_source_domain' => null,
            'fallback_source_used' => false,
            'source_match_confidence' => 100,
            'needs_source_review' => false,
            'needs_image_review' => false,
            'needs_content_review' => false,
            'generated_content' => false,
            'content_source_type' => 'official_source',
            'image_source_type' => 'official_manufacturer',
            'source_reviewed_at' => $now,
            'image_reviewed_at' => $now,
            'updated_at' => $now,
        ];

        if ($categoryId) {
            $parserUpdates = array_replace($parserUpdates, [
                'category_id' => $categoryId,
                'detected_category_id' => $categoryId,
                'detected_category_path' => $content['category'],
                'category_confidence_score' => 100,
                'category_detection_method' => $this->mode,
                'needs_category_review' => false,
            ]);
        }

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update($parserUpdates);
    }

    private function syncCategory(object $product, int $categoryId, string $categorySlug, object $now): void
    {
        DB::table('category_product')->where('product_id', $product->id)->delete();
        DB::table('category_product')->insert([
            'product_id' => $product->id,
            'category_id' => $categoryId,
            'is_primary' => true,
            'source' => $this->mode,
            'confidence' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ((int) $product->category_id === $categoryId) {
            return;
        }

        DB::table('product_category_decisions')->insert([
            'product_id' => $product->id,
            'previous_category_id' => $product->category_id,
            'selected_category_id' => $categoryId,
            'taxonomy_version' => 'verified-2026-07-21',
            'input_hash' => hash('sha256', $this->mode.'|'.$product->sku.'|'.$product->category_id.'|'.$categoryId),
            'mode' => $this->mode,
            'status' => 'applied',
            'classifier_confidence' => 1,
            'verifier_confidence' => 1,
            'evidence' => json_encode(["Official HOEGERT source identifies SKU {$product->sku}; selected category {$categorySlug}."], JSON_UNESCAPED_UNICODE),
            'alternatives' => json_encode([]),
            'validation_errors' => json_encode([]),
            'applied_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureCategories(): void
    {
        $categories = [
            'accesorii-pentru-sudura' => [
                'parent' => 'sudura-richtuire-vopsire',
                'name' => 'Аксессуары для сварки',
                'name_ro' => 'Accesorii pentru sudură',
                'description' => 'Магнитные угольники, держатели и другие аксессуары для сварочных работ.',
                'description_ro' => 'Echere magnetice, suporturi și alte accesorii pentru lucrări de sudură.',
            ],
            'seturi-scule-electrice-izolate' => [
                'parent' => 'instrumente-electromontaj',
                'name' => 'Наборы изолированных электромонтажных инструментов',
                'name_ro' => 'Seturi de scule electrice izolate',
                'description' => 'Наборы изолированных инструментов для электромонтажных работ.',
                'description_ro' => 'Seturi de scule izolate pentru lucrări electrice.',
            ],
            'alcooltestere' => [
                'parent' => 'instrumente-de-masurare',
                'name' => 'Алкотестеры',
                'name_ro' => 'Alcooltestere',
                'description' => 'Электронные приборы для проверки содержания алкоголя в выдыхаемом воздухе.',
                'description_ro' => 'Dispozitive electronice pentru verificarea alcoolului din aerul expirat.',
            ],
            'extractoare-cleme-tapiterie' => [
                'parent' => 'sudura-richtuire-vopsire',
                'name' => 'Съёмники обивочных клипс',
                'name_ro' => 'Extractoare pentru cleme de tapițerie',
                'description' => 'Инструменты для безопасного демонтажа клипс обивки и кузовных панелей.',
                'description_ro' => 'Scule pentru demontarea sigură a clemelor de tapițerie și a panourilor caroseriei.',
            ],
        ];

        foreach ($categories as $slug => $category) {
            $parentId = DB::table('categories')->where('slug', $category['parent'])->value('id');
            if (! $parentId) {
                continue;
            }

            $now = now();
            if (DB::table('categories')->where('slug', $slug)->exists()) {
                DB::table('categories')->where('slug', $slug)->update([
                    'parent_id' => $parentId,
                    'name' => $category['name'],
                    'name_ro' => $category['name_ro'],
                    'description' => $category['description'],
                    'description_ro' => $category['description_ro'],
                    'is_active' => true,
                    'is_assignable' => true,
                    'is_menu_visible' => true,
                    'source' => 'curated',
                    'taxonomy_version' => 'verified-2026-07-21',
                    'updated_at' => $now,
                ]);

                continue;
            }

            DB::table('categories')->insert([
                'parent_id' => $parentId,
                'name' => $category['name'],
                'name_ro' => $category['name_ro'],
                'slug' => $slug,
                'description' => $category['description'],
                'description_ro' => $category['description_ro'],
                'sort_order' => 50,
                'is_active' => true,
                'is_assignable' => true,
                'is_menu_visible' => true,
                'source' => 'curated',
                'taxonomy_version' => 'verified-2026-07-21',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Curated HOEGERT SKU content, image provenance, and category decisions are intentionally retained.
    }
};
