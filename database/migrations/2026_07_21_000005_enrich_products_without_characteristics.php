<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $products = [
            'QB-9434W' => [
                'name_ru' => 'Зачистной диск M7 QB-9434W, 3-1/2 дюйма, шток 6 мм, для QB-812',
                'name_ro' => 'Disc de curățare M7 QB-9434W, 3-1/2 inch, tijă 6 mm, pentru QB-812',
                'short_ru' => 'Зачистной диск M7 QB-9434W диаметром 3-1/2 дюйма со штоком 6 мм для пневматического зачистного инструмента QB-812.',
                'short_ro' => 'Disc de curățare M7 QB-9434W cu diametrul de 3-1/2 inch și tijă de 6 mm, pentru scula pneumatică QB-812.',
                'description_ru' => 'Зачистной диск M7 QB-9434W предназначен для пневматического инструмента M7 QB-812. Диаметр диска составляет 3-1/2 дюйма, диаметр штока — 6 мм. Используется для удаления покрытий и зачистки поверхности в соответствии с назначением совместимого инструмента.',
                'description_ro' => 'Discul de curățare M7 QB-9434W este destinat sculei pneumatice M7 QB-812. Diametrul discului este de 3-1/2 inch, iar diametrul tijei este de 6 mm. Se utilizează pentru îndepărtarea acoperirilor și curățarea suprafețelor conform destinației sculei compatibile.',
                'attributes' => ['Тип' => 'Зачистной диск', 'Диаметр' => '3-1/2 inch', 'Диаметр штока' => '6 mm', 'Совместимость' => 'M7 QB-812'],
                'source_url' => 'https://sklep.anb.com.pl/data/include/cms/M7/2025/Katalogi/2025_2026_Katalog_M7_int2.pdf',
                'source_domain' => 'sklep.anb.com.pl',
                'source_type' => 'official_manufacturer_catalog',
            ],
            'SC-005' => [
                'name_ru' => 'Набор съёмников шаровых опор и рулевых тяг M7 SC-005',
                'name_ro' => 'Set separatoare pentru articulații sferice și bare de direcție M7 SC-005',
                'short_ru' => 'Набор M7 SC-005 для разъединения шаровых опор, рулевых тяг и сошек Pitman.',
                'short_ro' => 'Set M7 SC-005 pentru separarea articulațiilor sferice, barelor de direcție și brațelor Pitman.',
                'description_ru' => 'Набор съёмников M7 SC-005 предназначен для разъединения шаровых опор, рулевых тяг и сошек Pitman. В комплект входят съёмники с рабочими размерами 17,46 мм, 23,81 мм и 28,57 мм, ударная рукоятка и адаптер для пневмомолотка.',
                'description_ro' => 'Setul de separatoare M7 SC-005 este destinat demontării articulațiilor sferice, barelor de direcție și brațelor Pitman. Include separatoare cu dimensiunile de lucru 17,46 mm, 23,81 mm și 28,57 mm, un mâner de lovire și un adaptor pentru ciocan pneumatic.',
                'attributes' => ['Тип' => 'Набор съёмников', 'Размер съёмника рулевой тяги' => '11/16 inch (17,46 mm)', 'Размер съёмника шаровой опоры' => '15/16 inch (23,81 mm)', 'Размер съёмника сошки Pitman' => '1-1/8 inch (28,57 mm)'],
                'source_url' => 'https://www.mighty-seven.com/product/562',
                'source_domain' => 'mighty-seven.com',
                'source_type' => 'official_manufacturer',
            ],
            'SB-6042' => [
                'name_ru' => 'Пистолет для накачки шин с манометром M7 SB-6042',
                'name_ro' => 'Pistol de umflat anvelope cu manometru M7 SB-6042',
                'short_ru' => 'Пистолет M7 SB-6042 с манометром, максимальным давлением 11 бар и шлангом длиной 100 см.',
                'short_ro' => 'Pistol M7 SB-6042 cu manometru, presiune maximă de 11 bar și furtun de 100 cm.',
                'description_ru' => 'Пистолет M7 SB-6042 предназначен для накачки шин и контроля давления. Манометр отображает значения в PSI, барах, кгс/см² и кПа. Максимальное давление — 160 PSI (11 бар), рекомендуемое рабочее давление — 90 PSI (6,3 бар). Комплектуется шлангом длиной 100 см и зажимом для вентиля шины.',
                'description_ro' => 'Pistolul M7 SB-6042 este destinat umflării anvelopelor și verificării presiunii. Manometrul indică valorile în PSI, bar, kgf/cm² și kPa. Presiunea maximă este de 160 PSI (11 bar), iar presiunea de lucru recomandată este de 90 PSI (6,3 bar). Este echipat cu furtun de 100 cm și clemă pentru ventilul anvelopei.',
                'attributes' => ['Тип' => 'Пистолет для накачки шин', 'Максимальное давление' => '160 PSI / 11 bar', 'Рабочее давление' => '90 PSI / 6,3 bar', 'Расход воздуха при 100 PSI' => '1,41 CFM', 'Диаметр шланга (рекомендуется)' => '3/8 inch', 'Размер воздушного штуцера' => '1/4 inch', 'Длина шланга' => '100 cm', 'Тип наконечника' => 'Зажим для вентиля шины', 'Вес' => '0,62 kg'],
                'source_url' => 'https://www.mighty-seven.com/product/3480',
                'source_domain' => 'mighty-seven.com',
                'source_type' => 'official_manufacturer',
            ],
            'SM-0901' => [
                'name_ru' => 'Установка для ручного и пневматического извлечения жидкостей M7 SM-0901, 9 л',
                'name_ro' => 'Extractor manual și pneumatic de lichide M7 SM-0901, 9 l',
                'short_ru' => 'Установка M7 SM-0901 объёмом 9 л для ручного и пневматического извлечения технических жидкостей.',
                'short_ro' => 'Extractor M7 SM-0901 de 9 l pentru extragerea manuală și pneumatică a lichidelor tehnice.',
                'description_ru' => 'M7 SM-0901 — установка объёмом 9 л для ручного и пневматического извлечения или перекачивания технических жидкостей. Усиленный композитный резервуар рассчитан на работу в мастерской. Диапазон давления воздуха — 70–170 PSI, масса — 4,7 кг.',
                'description_ro' => 'M7 SM-0901 este un extractor de 9 l pentru extragerea sau transferul manual și pneumatic al lichidelor tehnice. Rezervorul compozit ranforsat este conceput pentru utilizare în atelier. Intervalul presiunii aerului este de 70–170 PSI, iar greutatea este de 4,7 kg.',
                'attributes' => ['Тип' => 'Ручной/пневматический', 'Объём' => '9 l', 'Рабочее давление' => '70–170 PSI', 'Вес' => '4,7 kg'],
                'source_url' => 'https://www.mighty-seven.com/product/3465',
                'source_domain' => 'mighty-seven.com',
                'source_type' => 'official_manufacturer',
            ],
            'SG-400' => [
                'name_ru' => 'Пневматический шприц для смазки M7 SG-400, импульсная подача, 400 см³',
                'name_ro' => 'Pistol pneumatic de gresare M7 SG-400, alimentare în impulsuri, 400 cm³',
                'short_ru' => 'Пневматический шприц M7 SG-400 с импульсной подачей и ёмкостью 400 см³.',
                'short_ro' => 'Pistol pneumatic de gresare M7 SG-400 cu alimentare în impulsuri și capacitate de 400 cm³.',
                'description_ru' => 'Пневматический шприц M7 SG-400 предназначен для импульсной подачи консистентной смазки. Рабочее давление — 2,8–8,4 кгс/см², испытательное — 10,5 кгс/см². Ёмкость составляет 400 см³, воздушный штуцер — 1/4 дюйма, рекомендуемый шланг — 3/8 дюйма.',
                'description_ro' => 'Pistolul pneumatic de gresare M7 SG-400 este destinat alimentării în impulsuri cu unsoare. Presiunea de lucru este de 2,8–8,4 kgf/cm², iar presiunea de probă este de 10,5 kgf/cm². Capacitatea este de 400 cm³, racordul de aer este de 1/4 inch, iar furtunul recomandat este de 3/8 inch.',
                'attributes' => ['Тип' => 'Импульсная подача', 'Рабочее давление' => '2,8–8,4 kgf/cm²', 'Испытательное давление' => '10,5 kgf/cm²', 'Объём' => '400 cm³', 'Размер воздушного штуцера' => '1/4 inch', 'Диаметр шланга (рекомендуется)' => '3/8 inch', 'Уровень звукового давления' => '80,2 dBA', 'Уровень вибрации' => '0,7 m/s²', 'Вес' => '1,46 kg'],
                'source_url' => 'https://www.mighty-seven.com/product/496',
                'source_domain' => 'mighty-seven.com',
                'source_type' => 'official_manufacturer',
            ],
            'SG-401' => [
                'name_ru' => 'Пневматический шприц для смазки M7 SG-401, непрерывная подача, 400 см³',
                'name_ro' => 'Pistol pneumatic de gresare M7 SG-401, alimentare continuă, 400 cm³',
                'short_ru' => 'Пневматический шприц M7 SG-401 с непрерывной подачей 288 мл/мин и ёмкостью 400 см³.',
                'short_ro' => 'Pistol pneumatic de gresare M7 SG-401 cu debit continuu de 288 ml/min și capacitate de 400 cm³.',
                'description_ru' => 'Пневматический шприц M7 SG-401 с прочным литым алюминиевым корпусом обеспечивает непрерывную подачу смазки до 288 мл/мин. Рабочее давление — 2,8–8,4 кгс/см², испытательное — 10,5 кгс/см². Ёмкость составляет 400 см³.',
                'description_ro' => 'Pistolul pneumatic de gresare M7 SG-401 are corp robust din aluminiu turnat și asigură alimentare continuă cu un debit de până la 288 ml/min. Presiunea de lucru este de 2,8–8,4 kgf/cm², iar presiunea de probă este de 10,5 kgf/cm². Capacitatea este de 400 cm³.',
                'attributes' => ['Тип' => 'Непрерывная подача', 'Производительность' => '288 ml/min', 'Рабочее давление' => '2,8–8,4 kgf/cm²', 'Испытательное давление' => '10,5 kgf/cm²', 'Объём' => '400 cm³', 'Размер воздушного штуцера' => '1/4 inch', 'Диаметр шланга (рекомендуется)' => '3/8 inch', 'Уровень звукового давления' => '74 dBA', 'Уровень вибрации' => '2,0 m/s²', 'Вес' => '1,6 kg'],
                'source_url' => 'https://www.mighty-seven.com/product/498',
                'source_domain' => 'mighty-seven.com',
                'source_type' => 'official_manufacturer',
            ],
            '934-086MRVD' => [
                'name_ru' => 'Набор изолированных инструментов VDE KING TONY 934-086MRVD, 86 предметов, EVA',
                'name_ro' => 'Set de scule izolate VDE KING TONY 934-086MRVD, 86 piese, EVA',
                'short_ru' => 'Набор из 86 изолированных инструментов VDE KING TONY в EVA-ложементах для инструментальной тележки.',
                'short_ro' => 'Set KING TONY cu 86 de scule izolate VDE în tăvi EVA pentru cărucior de scule.',
                'description_ru' => 'KING TONY 934-086MRVD — набор из 86 изолированных инструментов VDE в EVA-ложементах для тележки. Содержит 15 рожковых ключей, 15 накидных ключей с изгибом 75°, 38 торцевых головок и принадлежностей 1/4 и 3/8 дюйма, 6 пассатижей и 12 отвёрток. Размер ложемента — 375 × 280 мм, размер ящика B3 — 579 × 380 мм. Масса — 9,8 кг.',
                'description_ro' => 'KING TONY 934-086MRVD este un set de 86 de scule izolate VDE în tăvi EVA pentru cărucior. Include 15 chei fixe, 15 chei inelare cotite la 75°, 38 de tubulare și accesorii de 1/4 și 3/8 inch, 6 clești și 12 șurubelnițe. Tava are 375 × 280 mm, sertarul B3 are 579 × 380 mm, iar greutatea este de 9,8 kg.',
                'attributes' => ['Тип' => 'Набор изолированных инструментов', 'Количество предметов' => '86', 'Максимальное рабочее напряжение' => '1000 V AC / 1500 V DC', 'Размер ложемента' => '375 × 280 mm', 'Размер ящика' => '579 × 380 mm', 'Вес' => '9,8 kg'],
                'source_url' => 'https://www.kingtony.com/product/86-PC-VDE-Insulated-Tool-Set-for-Tool-Trolley-EVA-FOAM-934-086MRVD',
                'source_domain' => 'kingtony.com',
                'source_type' => 'official_manufacturer',
            ],
            '12FVE10MRN' => [
                'name_ru' => 'Набор изолированных рожковых ключей VDE KING TONY 12FVE10MRN, 10–22 мм, 10 предметов',
                'name_ro' => 'Set chei fixe izolate VDE KING TONY 12FVE10MRN, 10–22 mm, 10 piese',
                'short_ru' => 'Набор из 10 изолированных рожковых ключей KING TONY размеров 10–22 мм по IEC 60900:2012.',
                'short_ro' => 'Set de 10 chei fixe izolate KING TONY, dimensiuni 10–22 mm, conform IEC 60900:2012.',
                'description_ru' => 'KING TONY 12FVE10MRN — набор из 10 изолированных рожковых ключей для работы под напряжением. Размеры: 10, 11, 12, 13, 14, 16, 17, 18, 19 и 22 мм. Инструменты соответствуют IEC 60900:2012. Масса набора — 1,28 кг.',
                'description_ro' => 'KING TONY 12FVE10MRN este un set de 10 chei fixe izolate pentru lucrări sub tensiune. Dimensiuni: 10, 11, 12, 13, 14, 16, 17, 18, 19 și 22 mm. Sculele respectă standardul IEC 60900:2012. Greutatea setului este de 1,28 kg.',
                'attributes' => ['Тип' => 'Набор рожковых ключей', 'Количество предметов' => '10', 'Размеры ключей' => '10, 11, 12, 13, 14, 16, 17, 18, 19, 22 mm', 'Рабочий профиль' => 'Рожковый', 'Стандарт' => 'IEC 60900:2012', 'Максимальное рабочее напряжение' => '1000 V AC / 1500 V DC', 'Вес' => '1,28 kg'],
                'source_url' => 'https://www.kingtony.com/product/10-PC-VDE-Insulated-Open-End-Wrench-Set-12FVE10MRN',
                'source_domain' => 'kingtony.com',
                'source_type' => 'official_manufacturer',
            ],
            'T25002' => [
                'name_ru' => 'Стенд для двигателя с редуктором Torin BIG RED T25002, 500 кг',
                'name_ro' => 'Suport pentru motor cu reductor Torin BIG RED T25002, 500 kg',
                'short_ru' => 'Передвижной стенд Torin BIG RED T25002 грузоподъёмностью 500 кг с поворотной монтажной плитой.',
                'short_ro' => 'Suport mobil Torin BIG RED T25002 cu capacitate de 500 kg și placă de montare rotativă.',
                'description_ru' => 'Torin BIG RED T25002 — передвижной стенд грузоподъёмностью 500 кг для установки и обслуживания снятого двигателя. Регулируемые монтажные рычаги подходят для различных блоков, а плита поворачивается на 360°. Габариты стенда — 960 × 610 × 1015 мм, масса нетто — 67 кг.',
                'description_ro' => 'Torin BIG RED T25002 este un suport mobil cu capacitate de 500 kg pentru montarea și întreținerea motorului demontat. Brațele de montare sunt reglabile pentru diferite blocuri, iar placa se rotește la 360°. Dimensiunile suportului sunt 960 × 610 × 1015 mm, iar greutatea netă este de 67 kg.',
                'attributes' => ['Тип' => 'Стенд для двигателя', 'Грузоподъёмность' => '500 kg', 'Поворот монтажной плиты' => '360°', 'Габаритные размеры' => '960 × 610 × 1015 mm', 'Размер упаковки' => '940 × 620 × 70 / 830 × 380 × 300 mm', 'Масса нетто' => '67 kg', 'Масса брутто' => '77 kg', 'Количество колёс' => '5'],
                'source_url' => 'https://toolwork.cl/products/banco-de-motores-de-500-kgs-con-reductor',
                'source_domain' => 'toolwork.cl',
                'source_type' => 'reviewed_distributor',
                'needs_source_review' => true,
            ],
            'TP10002' => [
                'name_ru' => 'Гидравлический подъёмный стол Torin BIG RED TP10002, 1000 кг',
                'name_ro' => 'Masă hidraulică de ridicare Torin BIG RED TP10002, 1000 kg',
                'short_ru' => 'Передвижной гидравлический стол TP10002 грузоподъёмностью 1000 кг и высотой подъёма 500–1700 мм.',
                'short_ro' => 'Masă hidraulică mobilă TP10002 cu capacitate de 1000 kg și înălțime de ridicare 500–1700 mm.',
                'description_ru' => 'Torin BIG RED TP10002 — передвижной гидравлический стол для подъёма, позиционирования и перевозки тяжёлых агрегатов. Грузоподъёмность — 1000 кг, диапазон подъёма — 500–1700 мм, размер платформы — 1200 × 610 мм. Подъём выполняется ножной педалью, клапан опускания расположен на рукоятке.',
                'description_ro' => 'Torin BIG RED TP10002 este o masă hidraulică mobilă pentru ridicarea, poziționarea și transportul agregatelor grele. Capacitatea este de 1000 kg, intervalul de ridicare este de 500–1700 mm, iar platforma are 1200 × 610 mm. Ridicarea se face cu pedala, iar supapa de coborâre este montată pe mâner.',
                'attributes' => ['Тип' => 'Гидравлический подъёмный стол', 'Грузоподъёмность' => '1000 kg', 'Диапазон подъёма' => '500–1700 mm', 'Размер платформы' => '1200 × 610 mm', 'Размер упаковки' => '1450 × 680 × 510 mm', 'Масса нетто' => '190 kg', 'Масса брутто' => '200 kg', 'Управление подъёмом' => 'Ножная гидравлическая педаль'],
                'source_url' => 'https://en.tongrunjacks.com/products_details/247.html',
                'source_domain' => 'en.tongrunjacks.com',
                'source_type' => 'official_manufacturer',
            ],
            'TP04001' => [
                'name_ru' => 'Гидравлический подъёмный стол Torin BIG RED TP04001, 300 кг',
                'name_ro' => 'Masă hidraulică de ridicare Torin BIG RED TP04001, 300 kg',
                'short_ru' => 'Передвижной гидравлический стол TP04001 грузоподъёмностью 300 кг и высотой подъёма 360–1290 мм.',
                'short_ro' => 'Masă hidraulică mobilă TP04001 cu capacitate de 300 kg și înălțime de ridicare 360–1290 mm.',
                'description_ru' => 'Torin BIG RED TP04001 — передвижной гидравлический стол для подъёма, позиционирования и перевозки агрегатов и других грузов. Грузоподъёмность — 300 кг, диапазон подъёма — 360–1290 мм, размер платформы — 712 × 500 мм. Подъём выполняется ножной педалью, клапан опускания расположен на рукоятке.',
                'description_ro' => 'Torin BIG RED TP04001 este o masă hidraulică mobilă pentru ridicarea, poziționarea și transportul agregatelor și al altor sarcini. Capacitatea este de 300 kg, intervalul de ridicare este de 360–1290 mm, iar platforma are 712 × 500 mm. Ridicarea se face cu pedala, iar supapa de coborâre este montată pe mâner.',
                'attributes' => ['Тип' => 'Гидравлический подъёмный стол', 'Грузоподъёмность' => '300 kg', 'Диапазон подъёма' => '360–1290 mm', 'Размер платформы' => '712 × 500 mm', 'Размер упаковки' => '1050 × 560 × 400 mm', 'Масса нетто' => '104 kg', 'Масса брутто' => '121,5 kg', 'Управление подъёмом' => 'Ножная гидравлическая педаль'],
                'source_url' => 'https://en.tongrunjacks.com/products_details/243.html',
                'source_domain' => 'en.tongrunjacks.com',
                'source_type' => 'official_manufacturer',
            ],
        ];

        foreach ($products as $sku => $data) {
            $product = DB::table('products')->where('sku', $sku)->first(['id']);
            if (! $product) {
                continue;
            }

            $reviewSource = (bool) ($data['needs_source_review'] ?? false);
            DB::table('products')->where('id', $product->id)->update([
                'name' => $data['name_ru'],
                'name_ru' => $data['name_ru'],
                'name_ro' => $data['name_ro'],
                'short_description' => $data['short_ru'],
                'short_description_ru' => $data['short_ru'],
                'short_description_ro' => $data['short_ro'],
                'description' => $data['description_ru'],
                'description_ru' => $data['description_ru'],
                'description_ro' => $data['description_ro'],
                'attributes' => json_encode($data['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'source_url' => $data['source_url'],
                'source_domain' => $data['source_domain'],
                'source_type' => $data['source_type'],
                'fallback_source_used' => false,
                'needs_content_review' => false,
                'needs_source_review' => $reviewSource,
                'generated_content' => false,
                'source_reviewed_at' => $reviewSource ? null : now(),
                'updated_at' => now(),
            ]);

            $parser = DB::table('product_parser_items')->where('sku', $sku)->orderByDesc('id')->first(['id', 'found_specs_json']);
            if (! $parser) {
                continue;
            }

            $specs = json_decode((string) $parser->found_specs_json, true);
            $specs = is_array($specs) ? array_replace($specs, $data['attributes']) : $data['attributes'];
            DB::table('product_parser_items')->where('id', $parser->id)->update([
                'name_ru' => $data['name_ru'],
                'name_ro' => $data['name_ro'],
                'short_description_ru' => $data['short_ru'],
                'short_description_ro' => $data['short_ro'],
                'description_ru' => $data['description_ru'],
                'description_ro' => $data['description_ro'],
                'found_specs_json' => json_encode($specs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'official_source_url' => $reviewSource ? null : $data['source_url'],
                'official_source_domain' => $reviewSource ? null : $data['source_domain'],
                'official_source_confidence' => $reviewSource ? null : 96,
                'fallback_source_used' => false,
                'needs_content_review' => false,
                'needs_source_review' => $reviewSource,
                'generated_content' => false,
                'content_source_type' => $data['source_type'],
                'source_reviewed_at' => $reviewSource ? null : now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Verified source-based catalog improvements are intentionally not reverted.
    }
};
