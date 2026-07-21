<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $products = [
            'SG-912' => [
                'name_ru' => 'Быстросъёмная смазочная муфта M7 SG-912',
                'name_ro' => 'Cuplă rapidă de gresare M7 SG-912',
                'description_ru' => 'Смазочная муфта M7 SG-912 с быстросъёмным механизмом предназначена для быстрого подключения и отсоединения при подаче консистентной смазки.',
                'description_ro' => 'Cupla de gresare M7 SG-912 are mecanism cu eliberare rapidă pentru conectare și deconectare rapidă la alimentarea cu unsoare.',
                'attributes' => ['Тип' => 'Смазочная муфта', 'Механизм' => 'Быстросъёмный'],
                'source_url' => 'https://www.mighty-seven.com/product/3597',
                'source_domain' => 'mighty-seven.com',
            ],
            'SG-911' => [
                'name_ru' => 'Поворотная смазочная муфта 360° M7 SG-911',
                'name_ro' => 'Cuplă rotativă de gresare 360° M7 SG-911',
                'description_ru' => 'Смазочная муфта M7 SG-911 поворачивается на 360° и облегчает подключение к пресс-маслёнкам в местах с ограниченным доступом.',
                'description_ro' => 'Cupla de gresare M7 SG-911 se rotește la 360° și facilitează conectarea la niplurile de gresare în spații cu acces limitat.',
                'attributes' => ['Тип' => 'Смазочная муфта', 'Механизм' => 'Поворотный', 'Угол поворота' => '360°'],
                'source_url' => 'https://www.mighty-seven.com/product/3594',
                'source_domain' => 'mighty-seven.com',
            ],
            'SG-910' => [
                'name_ru' => 'Угловая смазочная муфта 90° M7 SG-910',
                'name_ro' => 'Cuplă unghiulară de gresare 90° M7 SG-910',
                'description_ru' => 'Угловая смазочная муфта M7 SG-910 выполнена под углом 90° для подключения к пресс-маслёнкам в труднодоступных местах.',
                'description_ro' => 'Cupla unghiulară de gresare M7 SG-910 are un unghi de 90° pentru conectarea la niplurile de gresare greu accesibile.',
                'attributes' => ['Тип' => 'Смазочная муфта', 'Угол поворота' => '90°'],
                'source_url' => 'https://www.mighty-seven.com/product/3595',
                'source_domain' => 'mighty-seven.com',
            ],
            'QB-9433W' => [
                'name_ru' => 'Гладкий зачистной диск M7 QB-9433W, 3-1/2 дюйма, шток 6 мм, для QB-812',
                'name_ro' => 'Disc neted de curățare M7 QB-9433W, 3-1/2 inch, tijă 6 mm, pentru QB-812',
                'description_ru' => 'Гладкий зачистной диск M7 QB-9433W диаметром 3-1/2 дюйма со штоком 6 мм предназначен для пневматического зачистного инструмента M7 QB-812.',
                'description_ro' => 'Discul neted de curățare M7 QB-9433W are diametrul de 3-1/2 inch și tijă de 6 mm și este destinat sculei pneumatice M7 QB-812.',
                'attributes' => ['Тип' => 'Зачистной диск', 'Диаметр' => '3-1/2 inch', 'Диаметр штока' => '6 mm', 'Совместимость' => 'M7 QB-812'],
                'source_url' => 'https://sklep.anb.com.pl/data/include/cms/M7/2025/Katalogi/2025_2026_Katalog_M7_int2.pdf',
                'source_domain' => 'sklep.anb.com.pl',
            ],
            'QB-9211A' => [
                'name_ru' => 'Набор точильных камней M7 QB-9211A, 5 предметов, шток 3 мм',
                'name_ro' => 'Set de pietre de șlefuit M7 QB-9211A, 5 piese, tijă 3 mm',
                'description_ru' => 'Набор M7 QB-9211A содержит пять точильных камней со штоком 3 мм для совместимых пневматических прямых шлифмашин M7.',
                'description_ro' => 'Setul M7 QB-9211A conține cinci pietre de șlefuit cu tijă de 3 mm pentru polizoare pneumatice drepte M7 compatibile.',
                'attributes' => ['Тип' => 'Набор точильных камней', 'Количество предметов' => '5', 'Диаметр штока' => '3 mm'],
                'source_url' => 'https://www.mighty-seven.com/product/2801',
                'source_domain' => 'mighty-seven.com',
            ],
            'QB-9213A' => [
                'name_ru' => 'Набор точильных камней M7 QB-9213A, 5 предметов, шток 6 мм',
                'name_ro' => 'Set de pietre de șlefuit M7 QB-9213A, 5 piese, tijă 6 mm',
                'description_ru' => 'Набор M7 QB-9213A содержит пять точильных камней со штоком 6 мм для совместимых пневматических прямых шлифмашин M7.',
                'description_ro' => 'Setul M7 QB-9213A conține cinci pietre de șlefuit cu tijă de 6 mm pentru polizoare pneumatice drepte M7 compatibile.',
                'attributes' => ['Тип' => 'Набор точильных камней', 'Количество предметов' => '5', 'Диаметр штока' => '6 mm'],
                'source_url' => 'https://www.mighty-seven.com/product/2802',
                'source_domain' => 'mighty-seven.com',
            ],
            'SX-3301' => [
                'name_ru' => 'Пневматический пистолет-распылитель M7 SX-3301, бачок 1 л',
                'name_ro' => 'Pistol pneumatic de pulverizare M7 SX-3301, rezervor 1 l',
                'description_ru' => 'M7 SX-3301 — пневматический пистолет для распыления очистителя с нижним бачком 1 л. Рабочее давление составляет 90 PSI (6,3 бар), средний расход воздуха — 10 CFM (300 л/мин), длина — 300 мм, масса — 0,69 кг.',
                'description_ro' => 'M7 SX-3301 este un pistol pneumatic pentru pulverizarea solventului, cu rezervor inferior de 1 l. Presiunea de lucru este de 90 PSI (6,3 bar), consumul mediu de aer de 10 CFM (300 l/min), lungimea de 300 mm și greutatea de 0,69 kg.',
                'attributes' => ['Тип' => 'Пистолет-распылитель для очистителя', 'Объём' => '1 l', 'Рабочее давление' => '90 PSI / 6,3 bar', 'Среднее потребление воздуха' => '10 CFM / 300 l/min', 'Общая длина' => '300 mm', 'Вес' => '0,69 kg', 'Размер воздушного штуцера' => '1/4 inch'],
                'source_url' => 'https://www.mighty-seven.com/product/576',
                'source_domain' => 'mighty-seven.com',
            ],
            'QA-611A' => [
                'name_ru' => 'Пневматическая угловая шлифмашина 90° M7 QA-611A, 19 000 об/мин',
                'name_ro' => 'Polizor pneumatic unghiular 90° M7 QA-611A, 19 000 rot/min',
                'description_ru' => 'Пневматическая угловая шлифмашина M7 QA-611A работает со скоростью 19 000 об/мин. Рабочее давление — 90 PSI (6,3 бар), средний расход воздуха — 10 CFM (283 л/мин), длина — 160 мм, масса — 0,51 кг.',
                'description_ro' => 'Polizorul pneumatic unghiular M7 QA-611A funcționează la 19 000 rot/min. Presiunea de lucru este de 90 PSI (6,3 bar), consumul mediu de aer de 10 CFM (283 l/min), lungimea de 160 mm și greutatea de 0,51 kg.',
                'attributes' => ['Тип' => 'Пневматическая угловая шлифмашина', 'Угол поворота' => '90°', 'Скорость свободного вращения' => '19 000 rpm', 'Рабочее давление' => '90 PSI / 6,3 bar', 'Среднее потребление воздуха' => '10 CFM / 283 l/min', 'Общая длина' => '160 mm', 'Вес' => '0,51 kg', 'Размер воздушного штуцера' => '1/4 inch', 'Уровень звукового давления' => '74,4 dBA', 'Уровень вибрации' => '1,7 m/s²'],
                'source_url' => 'https://www.mighty-seven.com/product/165',
                'source_domain' => 'mighty-seven.com',
            ],
            'QA-601' => [
                'name_ru' => 'Компактная пневматическая угловая шлифмашина M7 QA-601, 19 000 об/мин',
                'name_ro' => 'Polizor pneumatic unghiular compact M7 QA-601, 19 000 rot/min',
                'description_ru' => 'M7 QA-601 — компактная пневматическая угловая шлифмашина для обработки в ограниченном пространстве. Скорость свободного вращения составляет 19 000 об/мин.',
                'description_ro' => 'M7 QA-601 este un polizor pneumatic unghiular compact pentru lucrări în spații restrânse. Turația în gol este de 19 000 rot/min.',
                'attributes' => ['Тип' => 'Пневматическая угловая шлифмашина', 'Угол поворота' => '90°', 'Скорость свободного вращения' => '19 000 rpm'],
                'source_url' => 'https://www.mighty-seven.com/product_list/1',
                'source_domain' => 'mighty-seven.com',
            ],
            'SC-0320' => [
                'name_ru' => 'Набор для снятия дизельных форсунок M7 SC-0320 с пневматическим обратным молотком, 19 предметов',
                'name_ro' => 'Set pentru demontarea injectoarelor diesel M7 SC-0320 cu ciocan pneumatic invers, 19 piese',
                'description_ru' => 'Набор M7 SC-0320 предназначен для демонтажа дизельных форсунок и включает 19 предметов, в том числе пневматический обратный молоток и присоединительные элементы.',
                'description_ro' => 'Setul M7 SC-0320 este destinat demontării injectoarelor diesel și include 19 piese, inclusiv un ciocan pneumatic invers și elemente de conectare.',
                'attributes' => ['Тип' => 'Набор для снятия дизельных форсунок', 'Количество предметов' => '19', 'Механизм' => 'Пневматический обратный молоток'],
                'source_url' => 'https://www.mighty-seven.com/product/3570',
                'source_domain' => 'mighty-seven.com',
            ],
            'HT1S704' => [
                'name_ru' => 'Набор ударных адаптеров Högert HT1S704, HEX 1/4 дюйма, 3 предмета',
                'name_ro' => 'Set de adaptoare de impact Högert HT1S704, HEX 1/4 inch, 3 piese',
                'description_ru' => 'Набор Högert HT1S704 соединяет электроинструмент с хвостовиком HEX 1/4 дюйма с ударными головками 1/4, 3/8 и 1/2 дюйма. Три адаптера изготовлены из термообработанной легированной стали и имеют защитный шариковый фиксатор.',
                'description_ro' => 'Setul Högert HT1S704 conectează sculele electrice cu prindere HEX de 1/4 inch la capete de impact de 1/4, 3/8 și 1/2 inch. Cele trei adaptoare sunt fabricate din oțel aliat tratat termic și au bilă de siguranță.',
                'attributes' => ['Тип' => 'Набор ударных адаптеров', 'Количество предметов' => '3', 'Посадочное место' => 'HEX 1/4 inch', 'Размер' => '1/4, 3/8, 1/2 inch', 'Материал' => 'Термообработанная легированная сталь'],
                'source_url' => 'https://en.hoegert.com/product/impact-adapter-set-for-1-4-sockets-3-pcs/',
                'source_domain' => 'en.hoegert.com',
            ],
            'TEL05004s-sc' => [
                'name_ru' => 'Универсальный суппорт для коробок передач Torin TEL05004s-sc, для стойки TEL05004S',
                'name_ro' => 'Suport universal pentru cutii de viteze Torin TEL05004s-sc, pentru cricul TEL05004S',
                'description_ru' => 'Универсальный суппорт Torin TEL05004s-sc предназначен для фиксации коробки передач на трансмиссионной стойке TEL05004S грузоподъёмностью 500 кг. Грузоподъёмность относится к совместимой стойке, а не к суппорту отдельно.',
                'description_ro' => 'Suportul universal Torin TEL05004s-sc este destinat fixării cutiei de viteze pe cricul de transmisie TEL05004S cu capacitatea de 500 kg. Capacitatea se referă la cricul compatibil, nu la suport separat.',
                'attributes' => ['Тип' => 'Универсальный суппорт для коробок передач', 'Совместимость' => 'Torin TEL05004S (500 kg)'],
                'source_url' => null,
                'source_domain' => null,
                'needs_source_review' => true,
            ],
        ];

        DB::transaction(function () use ($products): void {
            foreach ($products as $sku => $data) {
                $product = DB::table('products')->where('sku', $sku)->first();
                if (! $product) {
                    continue;
                }

                $needsSourceReview = (bool) ($data['needs_source_review'] ?? false);
                $now = now();
                DB::table('products')->where('id', $product->id)->update([
                    'name' => $data['name_ru'],
                    'name_ru' => $data['name_ru'],
                    'name_ro' => $data['name_ro'],
                    'short_description' => $data['description_ru'],
                    'short_description_ru' => $data['description_ru'],
                    'short_description_ro' => $data['description_ro'],
                    'description' => $data['description_ru'],
                    'description_ru' => $data['description_ru'],
                    'description_ro' => $data['description_ro'],
                    'attributes' => json_encode($data['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'source_url' => $data['source_url'],
                    'source_domain' => $data['source_domain'],
                    'source_type' => $data['source_url'] ? 'verified_manufacturer_source' : null,
                    'needs_source_review' => $needsSourceReview,
                    'needs_content_review' => false,
                    'generated_content' => false,
                    'source_reviewed_at' => $needsSourceReview ? null : $now,
                    'updated_at' => $now,
                ]);

                if (! $product->source_parser_item_id) {
                    continue;
                }

                DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                    'name_ru' => $data['name_ru'],
                    'name_ro' => $data['name_ro'],
                    'short_description_ru' => $data['description_ru'],
                    'short_description_ro' => $data['description_ro'],
                    'description_ru' => $data['description_ru'],
                    'description_ro' => $data['description_ro'],
                    'found_title' => $data['name_ru'],
                    'found_description' => $data['description_ru'],
                    'found_specs_json' => json_encode($data['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'official_source_url' => $needsSourceReview ? null : $data['source_url'],
                    'official_source_domain' => $needsSourceReview ? null : $data['source_domain'],
                    'official_source_confidence' => $needsSourceReview ? null : 100,
                    'source_match_confidence' => $needsSourceReview ? null : 100,
                    'needs_source_review' => $needsSourceReview,
                    'needs_content_review' => false,
                    'generated_content' => false,
                    'content_source_type' => $needsSourceReview ? 'reviewed_reference' : 'official_source',
                    'source_reviewed_at' => $needsSourceReview ? null : $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Curated catalog corrections are intentionally not reverted to stock values.
    }
};
