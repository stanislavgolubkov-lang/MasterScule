<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-gys-chargers-boosters-2026-08-03';

    public function up(): void
    {
        DB::transaction(function (): void {
            $this->purgeRetiredMarketplaceSourceHistory();

            $records = $this->records();
            $products = DB::table('products')->whereIn('sku', array_keys($records))->get()->keyBy('sku');
            $categoryId = DB::table('categories')->where('slug', 'baterii-incarcatoare')->value('id');

            foreach ($records as $sku => $content) {
                if ($product = $products->get($sku)) {
                    $this->updateProduct($product, $content, $categoryId);
                }
            }
        });
    }

    private function records(): array
    {
        return [
            '023260' => $this->record(
                'Автоматическое зарядное устройство GYS 023260 TCB 90, 12 В, 5,5 А',
                'Încărcător automat GYS 023260 TCB 90, 12 V, 5,5 A',
                'GYS TCB 90 (023260) — автоматическое зарядное устройство мощностью 120 Вт для 12-вольтовых свинцово-кислотных и гелевых аккумуляторов ёмкостью до 90 А·ч. Максимальный зарядный ток составляет 5,5 А.',
                'GYS TCB 90 (023260) este un încărcător automat de 120 W pentru baterii de 12 V cu plumb-acid și GEL, cu o capacitate de până la 90 Ah. Curentul maxim de încărcare este de 5,5 A.',
                ['Тип' => 'Автоматическое зарядное устройство', 'Напряжение зарядки' => '12 V', 'Зарядный ток' => '5.5 A', 'Диапазон ёмкости аккумулятора' => '15–90 Ah', 'Поддерживаемые аккумуляторы' => 'Свинцово-кислотные / GEL', 'Мощность' => '120 W'],
                'https://www.eurodel.no/batterilader-gys-tcb-90-automatic',
                'exact_sku_distributor'
            ),
            '024526' => $this->record(
                'Автоматическое зарядное устройство GYS 024526 BATIUM 15.24, 6/12/24 В',
                'Încărcător automat GYS 024526 BATIUM 15.24, 6/12/24 V',
                'GYS BATIUM 15.24 (024526) — микропроцессорное зарядное устройство для жидкостных, GEL, AGM и VRLA аккумуляторов 6, 12 и 24 В ёмкостью 35–225 А·ч. Оно поддерживает токи 7, 10 и 15 А, зарядную кривую IWUoU, восстановление глубоко разряженных батарей и безопасную зарядку без отключения аккумулятора от автомобиля.',
                'GYS BATIUM 15.24 (024526) este un încărcător cu microprocesor pentru baterii lichide, GEL, AGM și VRLA de 6, 12 și 24 V, cu capacitatea de 35–225 Ah. Oferă curenți de 7, 10 și 15 A, curbă IWUoU, recuperarea bateriilor descărcate profund și încărcare sigură fără deconectarea bateriei de la vehicul.',
                ['Тип' => 'Автоматическое зарядное устройство', 'Напряжение зарядки' => '6 / 12 / 24 V', 'Зарядный ток' => '7 / 10 / 15 A (22 A RMS)', 'Диапазон ёмкости аккумулятора' => '35–225 Ah', 'Поддерживаемые аккумуляторы' => 'Свинцово-кислотные: жидкостные / GEL / AGM / VRLA', 'Кривая зарядки' => 'IWUoU', 'Мощность' => '450 W'],
                'https://www.accutotaal.com/media/pdf/datasheet_024526.pdf',
                weight: '7.1 kg',
                dimensions: '210 × 362 × 141 mm'
            ),
            '025882' => $this->record(
                'Литиевое пусковое устройство GYS 025882 NOMAD POWER 400, 12 В',
                'Booster cu litiu GYS 025882 NOMAD POWER 400, 12 V',
                'GYS NOMAD POWER 400 (025882) объединяет пусковое устройство для 12-вольтовых автомобилей, внешний аккумулятор и аварийный фонарь. Встроенный аккумулятор LiCoO2 имеет ёмкость 3 А·ч и энергию 45 Вт·ч; пусковой ток составляет 500 А, ток прокрутки — 1000 А, пиковый ток — 1200 А.',
                'GYS NOMAD POWER 400 (025882) combină un booster pentru vehicule de 12 V, o baterie externă și o lampă de urgență. Bateria LiCoO2 integrată are 3 Ah și 45 Wh; curentul de pornire este de 500 A, curentul de antrenare de 1000 A, iar curentul de vârf de 1200 A.',
                ['Тип' => 'Литиевое пусковое устройство и внешний аккумулятор', 'Напряжение' => '12 V', 'Тип внутреннего аккумулятора' => 'LiCoO2', 'Ёмкость аккумулятора' => '3 Ah', 'Энергия аккумулятора' => '45 Wh', 'Пусковой ток' => '500 A', 'Ток прокрутки' => '1000 A', 'Пиковый ток' => '1200 A', 'Выходы питания' => '2 × USB / 15 V DC, 5 A'],
                'https://www.accutotaal.com/media/pdf/datasheet_025882.pdf',
                weight: '0.498 kg',
                dimensions: '17.5 × 8.4 × 3.1 cm'
            ),
            '026117' => $this->record(
                'Гибридное пусковое устройство GYS 026117 STARTRONIC HYBRID 950, 12 В',
                'Booster hibrid GYS 026117 STARTRONIC HYBRID 950, 12 V',
                'GYS STARTRONIC HYBRID 950 (026117) использует пять суперконденсаторов по 950 Ф и резервный литий-ионный аккумулятор для запуска 12-вольтовых автомобилей с батареями 5–100 А·ч. Пусковой ток составляет 950 А, пиковый — 1660 А; система рассчитана до 10 000 циклов и работает при температуре от −40 до +65 °C.',
                'GYS STARTRONIC HYBRID 950 (026117) utilizează cinci supercondensatoare de 950 F și o baterie litiu-ion de rezervă pentru pornirea vehiculelor de 12 V cu baterii de 5–100 Ah. Curentul de pornire este de 950 A, iar cel de vârf de 1660 A; sistemul este proiectat pentru până la 10.000 de cicluri și funcționează între −40 și +65 °C.',
                ['Тип' => 'Гибридное пусковое устройство с суперконденсаторами', 'Напряжение' => '12 V', 'Диапазон ёмкости аккумулятора' => '5–100 Ah', 'Суперконденсаторы' => '5 × 950 F', 'Пусковой ток' => '950 A', 'Пиковый ток' => '1660 A', 'Количество циклов' => '10 000', 'Рабочая температура' => '−40…+65 °C'],
                'https://www.accutotaal.com/media/pdf/datasheet_026117.pdf',
                weight: '1.86 kg',
                dimensions: '25 × 18.5 × 6 cm'
            ),
            '026155' => $this->record(
                'Автономное пусковое устройство GYS 026155 GYSPACK PRO, 12 В, 22 А·ч',
                'Booster autonom GYS 026155 GYSPACK PRO, 12 V, 22 Ah',
                'GYS GYSPACK PRO (026155) — автономное пусковое устройство и источник питания 12 В со встроенным аккумулятором 22 А·ч. Оно выдаёт 600 А пускового тока, 1100 А тока прокрутки и до 1750 А пикового тока; оснащено LED-фонарём, тестером заряда и кабелями 2 × 1,1 м с зажимами 500 А.',
                'GYS GYSPACK PRO (026155) este un booster autonom și o sursă de alimentare de 12 V cu baterie integrată de 22 Ah. Furnizează 600 A la pornire, 1100 A curent de antrenare și până la 1750 A curent de vârf; include lampă LED, tester de încărcare și cabluri de 2 × 1,1 m cu cleme de 500 A.',
                ['Тип' => 'Автономное пусковое устройство и источник питания', 'Напряжение' => '12 V', 'Ёмкость аккумулятора' => '22 Ah', 'Пусковой ток' => '600 A', 'Ток прокрутки' => '1100 A', 'Пиковый ток' => '1750 A', 'Длина кабеля' => '2 × 1.1 m', 'Сечение кабеля' => '25 mm²'],
                'https://www.mastroweld.hu/pdf/termeklapok/gyspackpro.pdf',
                weight: '8.9 kg',
                dimensions: '34 × 20 × 43 cm'
            ),
            '026179' => $this->record(
                'Автономное пусковое устройство GYS 026179 GYSPACK 750, 12 В, 28 А·ч',
                'Booster autonom GYS 026179 GYSPACK 750, 12 V, 28 Ah',
                'GYS GYSPACK 750 (026179) объединяет профессиональное пусковое устройство и переносной источник питания 12 В со встроенным AGM-аккумулятором 28 А·ч. Пусковой ток составляет 750 А, ток прокрутки — 1450 А, пиковый — 2500 А; предусмотрены LED-фонарь, тестер заряда и кабели 2 × 1,3 м с зажимами 600 А.',
                'GYS GYSPACK 750 (026179) combină un booster profesional și o sursă portabilă de 12 V cu baterie AGM integrată de 28 Ah. Curentul de pornire este de 750 A, curentul de antrenare de 1450 A, iar cel de vârf de 2500 A; dispune de lampă LED, tester de încărcare și cabluri de 2 × 1,3 m cu cleme de 600 A.',
                ['Тип' => 'Автономное пусковое устройство и источник питания', 'Напряжение' => '12 V', 'Тип внутреннего аккумулятора' => 'AGM', 'Ёмкость аккумулятора' => '28 Ah', 'Пусковой ток' => '750 A', 'Ток прокрутки' => '1450 A', 'Пиковый ток' => '2500 A', 'Длина кабеля' => '2 × 1.3 m', 'Сечение кабеля' => '25 mm²'],
                'https://www.accutotaal.com/media/pdf/datasheet_026179.pdf',
                weight: '14 kg',
                dimensions: '36 × 20 × 43 cm'
            ),
            '026322' => $this->record(
                'Автономное пусковое устройство GYS 026322 GYSPACK AIR с компрессором',
                'Booster autonom GYS 026322 GYSPACK AIR cu compresor',
                'GYS GYSPACK AIR (026322) — пусковое устройство и источник питания 12 В со встроенным аккумулятором 18 А·ч и компрессором 4 бар производительностью 13 л/мин. Оно выдаёт 480 А пускового тока, 900 А тока прокрутки и до 1250 А пикового тока, а также оснащено LED-фонарём и тестером заряда.',
                'GYS GYSPACK AIR (026322) este un booster și o sursă de alimentare de 12 V cu baterie integrată de 18 Ah și compresor de 4 bar, cu debit de 13 l/min. Furnizează 480 A la pornire, 900 A curent de antrenare și până la 1250 A curent de vârf și include lampă LED și tester de încărcare.',
                ['Тип' => 'Автономное пусковое устройство с компрессором', 'Напряжение' => '12 V', 'Ёмкость аккумулятора' => '18 Ah', 'Пусковой ток' => '480 A', 'Ток прокрутки' => '900 A', 'Пиковый ток' => '1250 A', 'Компрессор' => '4 bar', 'Производительность компрессора' => '13 l/min'],
                'https://www.gys.com.ua/pdf/gys-chargers.pdf',
                'manufacturer_catalog',
                weight: '8.5 kg',
                dimensions: '33 × 17 × 37 cm'
            ),
            '026735' => $this->record(
                'Пусковое устройство GYS 026735 STARTRONIC 800 с суперконденсаторами',
                'Booster GYS 026735 STARTRONIC 800 cu supercondensatoare',
                'GYS STARTRONIC 800 (026735) — автономное пусковое устройство без внутреннего аккумулятора для 12-вольтовых автомобилей с батареями 5–90 А·ч. Пять суперконденсаторов по 800 Ф обеспечивают пусковой ток 800 А и пиковый ток 1400 А; ресурс рассчитан до 10 000 циклов.',
                'GYS STARTRONIC 800 (026735) este un booster autonom fără baterie internă pentru vehicule de 12 V cu baterii de 5–90 Ah. Cele cinci supercondensatoare de 800 F furnizează 800 A la pornire și 1400 A curent de vârf, cu o durată proiectată de până la 10.000 de cicluri.',
                ['Тип' => 'Пусковое устройство с суперконденсаторами', 'Напряжение' => '12 V', 'Диапазон ёмкости аккумулятора' => '5–90 Ah', 'Суперконденсаторы' => '5 × 800 F', 'Пусковой ток' => '800 A', 'Пиковый ток' => '1400 A', 'Количество циклов' => '10 000', 'Рабочая температура' => '−40…+65 °C'],
                'https://www.comptoirdespros.com/media/FT_Gys_Startronic_800_026735.pdf',
                weight: '1.86 kg',
                dimensions: '26.7 × 19.5 × 5 cm'
            ),
            '027336' => $this->record(
                'Автономное пусковое устройство GYS 027336 GYSPACK 600, 12 В, 22 А·ч',
                'Booster autonom GYS 027336 GYSPACK 600, 12 V, 22 Ah',
                'GYS GYSPACK 600 (027336) — автономное пусковое устройство и источник питания 12 В со встроенным аккумулятором 22 А·ч. Оно обеспечивает 550 А пускового тока, 1100 А тока прокрутки и до 1750 А пикового тока и предназначено для запуска бензиновых и дизельных автомобилей.',
                'GYS GYSPACK 600 (027336) este un booster autonom și o sursă de alimentare de 12 V cu baterie integrată de 22 Ah. Furnizează 550 A la pornire, 1100 A curent de antrenare și până la 1750 A curent de vârf și este destinat vehiculelor pe benzină și diesel.',
                ['Тип' => 'Автономное пусковое устройство и источник питания', 'Напряжение' => '12 V', 'Ёмкость аккумулятора' => '22 Ah', 'Пусковой ток' => '550 A', 'Ток прокрутки' => '1100 A', 'Пиковый ток' => '1750 A'],
                'https://gys.herkules-sc.pl/wp-content/uploads/2021/06/GYS_Charger_2021.pdf',
                'manufacturer_catalog'
            ),
            '029378' => $this->record(
                'Автоматическое зарядное устройство GYS 029378 GYSFLASH 6.12, 12 В, 6 А',
                'Încărcător automat GYS 029378 GYSFLASH 6.12, 12 V, 6 A',
                'GYSFLASH 6.12 (029378) — автоматическое восьмиступенчатое зарядное устройство для 12-вольтовых свинцово-кислотных аккумуляторов 1,2–125 А·ч и поддерживающей зарядки до 170 А·ч. Модель выдаёт 6 А, поддерживает AGM и Refresh, защищена по IP65 и безопасна для бортовой электроники.',
                'GYSFLASH 6.12 (029378) este un încărcător automat în opt etape pentru baterii cu plumb-acid de 12 V, 1,2–125 Ah, cu întreținere până la 170 Ah. Modelul furnizează 6 A, include modurile AGM și Refresh, are protecție IP65 și este sigur pentru electronica vehiculului.',
                ['Тип' => 'Автоматическое зарядное устройство', 'Напряжение зарядки' => '12 V', 'Зарядный ток' => '6 A', 'Диапазон ёмкости аккумулятора' => '1.2–125 Ah (maintenance 170 Ah)', 'Поддерживаемые аккумуляторы' => 'Свинцово-кислотные: жидкостные / GEL / AGM / Start&Stop', 'Количество ступеней зарядки' => '8', 'Мощность' => '90 W', 'Степень защиты' => 'IP65'],
                'https://handleidingen.acculaders.nl/wp-content/uploads/2020/11/productsheet-gys-gysflash-6-12.pdf',
                weight: '0.77 kg',
                dimensions: '190 × 100 × 52 mm'
            ),
            '029392' => $this->record(
                'Автоматическое зарядное устройство GYS 029392 GYSFLASH 12.12, 12 В, 12 А',
                'Încărcător automat GYS 029392 GYSFLASH 12.12, 12 V, 12 A',
                'GYSFLASH 12.12 (029392) — восьмиступенчатое зарядное устройство на 12 А для 12-вольтовых аккумуляторов 20–250 А·ч и поддерживающей зарядки до 330 А·ч. Режим Supply поддерживает питание автомобиля при демонстрации или замене батареи, а AGM и Refresh расширяют возможности обслуживания.',
                'GYSFLASH 12.12 (029392) este un încărcător în opt etape de 12 A pentru baterii de 12 V, 20–250 Ah, cu întreținere până la 330 Ah. Modul Supply menține alimentarea vehiculului în showroom sau la schimbarea bateriei, iar modurile AGM și Refresh extind posibilitățile de service.',
                ['Тип' => 'Автоматическое зарядное устройство', 'Напряжение зарядки' => '12 V', 'Зарядный ток' => '12 A', 'Диапазон ёмкости аккумулятора' => '20–250 Ah (maintenance 330 Ah)', 'Количество ступеней зарядки' => '8', 'Мощность' => '195 W', 'Степень защиты' => 'IP65', 'Функции' => 'AGM / Refresh / Supply'],
                'https://www.accutotaal.com/media/pdf/datasheet_029392.pdf',
                weight: '1.1 kg',
                dimensions: '221 × 111 × 58 mm'
            ),
            '029460' => $this->record(
                'Автоматическое зарядное устройство GYS 029460 GYSFLASH 6.24, 6/12/24 В',
                'Încărcător automat GYS 029460 GYSFLASH 6.24, 6/12/24 V',
                'GYSFLASH 6.24 (029460) — семиступенчатое зарядное устройство для аккумуляторов 6, 12 и 24 В. При 6/12 В оно выдаёт 6 А и обслуживает батареи 1,2–125 А·ч, при 24 В — 4 А для батарей 15–100 А·ч; корпус защищён по IP65.',
                'GYSFLASH 6.24 (029460) este un încărcător în șapte etape pentru baterii de 6, 12 și 24 V. La 6/12 V furnizează 6 A pentru baterii de 1,2–125 Ah, iar la 24 V furnizează 4 A pentru baterii de 15–100 Ah; carcasa are protecție IP65.',
                ['Тип' => 'Автоматическое зарядное устройство', 'Напряжение зарядки' => '6 / 12 / 24 V', 'Зарядный ток' => '6 A (6/12 V) / 4 A (24 V)', 'Диапазон ёмкости аккумулятора' => '1.2–125 Ah (6/12 V) / 15–100 Ah (24 V)', 'Количество ступеней зарядки' => '7', 'Мощность' => '130 W', 'Степень защиты' => 'IP65'],
                'https://documents.kramp.com/029460GYS_EN.pdf',
                weight: '0.85 kg',
                dimensions: '190 × 100 × 52 mm'
            ),
            '029477' => $this->record(
                'Автоматическое зарядное устройство GYS 029477 GYSFLASH 9.24, 6/12/24 В',
                'Încărcător automat GYS 029477 GYSFLASH 9.24, 6/12/24 V',
                'GYSFLASH 9.24 (029477) — восьмиступенчатое зарядное устройство для аккумуляторов 6, 12 и 24 В. Оно выдаёт 9 А при 6/12 В для батарей 18–220 А·ч и 6 А при 24 В для батарей 15–125 А·ч; предусмотрены Refresh, температурная коррекция и защита IP65.',
                'GYSFLASH 9.24 (029477) este un încărcător în opt etape pentru baterii de 6, 12 și 24 V. Furnizează 9 A la 6/12 V pentru baterii de 18–220 Ah și 6 A la 24 V pentru baterii de 15–125 Ah; include Refresh, compensare termică și protecție IP65.',
                ['Тип' => 'Автоматическое зарядное устройство', 'Напряжение зарядки' => '6 / 12 / 24 V', 'Зарядный ток' => '9 A (6/12 V) / 6 A (24 V)', 'Диапазон ёмкости аккумулятора' => '18–220 Ah (6/12 V) / 15–125 Ah (24 V)', 'Количество ступеней зарядки' => '8', 'Мощность' => '190 W', 'Степень защиты' => 'IP65', 'Функции' => 'Refresh / temperature compensation'],
                'https://documents.kramp.com/029477GYS_EN.pdf',
                weight: '1.1 kg',
                dimensions: '221 × 111 × 58 mm'
            ),
            '029729' => $this->record(
                'Зарядное устройство GYS 029729 GYSFLASH 6.12 LITHIUM, 12 В, 6 А',
                'Încărcător GYS 029729 GYSFLASH 6.12 LITHIUM, 12 V, 6 A',
                'GYSFLASH 6.12 LITHIUM (029729) предназначен для зарядки и обслуживания 12-вольтовых аккумуляторов LiFePO4 ёмкостью 1,2–125 А·ч. Восьмиступенчатая кривая с балансировкой EBS, режимы 4 и 6 А, функция UVP Wake Up и защита IP65 обеспечивают безопасное восстановление и зарядку.',
                'GYSFLASH 6.12 LITHIUM (029729) este destinat încărcării și întreținerii bateriilor LiFePO4 de 12 V, cu capacitatea de 1,2–125 Ah. Curba în opt etape cu echilibrare EBS, modurile de 4 și 6 A, funcția UVP Wake Up și protecția IP65 asigură recuperarea și încărcarea în siguranță.',
                ['Тип' => 'Автоматическое зарядное устройство', 'Напряжение зарядки' => '12 V', 'Зарядный ток' => '4 / 6 A', 'Диапазон ёмкости аккумулятора' => '1.2–125 Ah (maintenance 170 Ah)', 'Поддерживаемые аккумуляторы' => 'Литий-железо-фосфатные LiFePO4', 'Количество ступеней зарядки' => '8', 'Мощность' => '90 W', 'Степень защиты' => 'IP65', 'Функции' => 'EBS / UVP Wake Up'],
                'https://www.accutotaal.com/media/pdf/datasheet_029729.pdf',
                weight: '0.77 kg',
                dimensions: '190 × 100 × 52 mm'
            ),
            '085879' => $this->record(
                'Литиевое пусковое устройство GYS 085879 NOMAD POWER PRO 901 FC',
                'Booster cu litiu GYS 085879 NOMAD POWER PRO 901 FC',
                'GYS NOMAD POWER PRO 901 FC (085879) — пусковое устройство, внешний аккумулятор и аварийный фонарь для 12-вольтовых автомобилей с батареями 5–150 А·ч. Аккумулятор LiCoO2 имеет 6 А·ч и 92,5 Вт·ч; устройство выдаёт 900 А пускового, 1400 А прокрутки и 1700 А пикового тока и заряжается за 1 ч 15 мин от блока 67 Вт.',
                'GYS NOMAD POWER PRO 901 FC (085879) este un booster, o baterie externă și o lampă de urgență pentru vehicule de 12 V cu baterii de 5–150 Ah. Bateria LiCoO2 are 6 Ah și 92,5 Wh; aparatul furnizează 900 A la pornire, 1400 A la antrenare și 1700 A la vârf și se încarcă în 1 h 15 min cu un adaptor de 67 W.',
                ['Тип' => 'Литиевое пусковое устройство и внешний аккумулятор', 'Напряжение' => '12 V', 'Диапазон ёмкости аккумулятора' => '5–150 Ah', 'Тип внутреннего аккумулятора' => 'LiCoO2', 'Ёмкость аккумулятора' => '6 Ah', 'Энергия аккумулятора' => '92.5 Wh', 'Пусковой ток' => '900 A', 'Ток прокрутки' => '1400 A', 'Пиковый ток' => '1700 A', 'Время полной зарядки' => '1 h 15 min (67 W) / 5 h (15 W)', 'Выходы питания' => 'USB-A / USB-C PD 60 W / 15 V DC, 10 A'],
                'https://www.1001piles.com/media/pdf/LEX1H33_FR.pdf',
                weight: '0.82 kg',
                dimensions: '22.8 × 10 × 3.8 cm'
            ),
            '087323' => $this->record(
                'Литиевое пусковое устройство GYS 087323 NOMAD POWER 501',
                'Booster cu litiu GYS 087323 NOMAD POWER 501',
                'GYS NOMAD POWER 501 (087323) объединяет пусковое устройство для 12-вольтовых автомобилей с батареями 5–90 А·ч, внешний аккумулятор и аварийный фонарь. Аккумулятор LiCoO2 имеет 3 А·ч и 45 Вт·ч; токи составляют 500 А при запуске, 1000 А при прокрутке и 1200 А в пике.',
                'GYS NOMAD POWER 501 (087323) combină un booster pentru vehicule de 12 V cu baterii de 5–90 Ah, o baterie externă și o lampă de urgență. Bateria LiCoO2 are 3 Ah și 45 Wh; curenții sunt de 500 A la pornire, 1000 A la antrenare și 1200 A la vârf.',
                ['Тип' => 'Литиевое пусковое устройство и внешний аккумулятор', 'Напряжение' => '12 V', 'Диапазон ёмкости аккумулятора' => '5–90 Ah', 'Тип внутреннего аккумулятора' => 'LiCoO2', 'Ёмкость аккумулятора' => '3 Ah', 'Энергия аккумулятора' => '45 Wh', 'Пусковой ток' => '500 A', 'Ток прокрутки' => '1000 A', 'Пиковый ток' => '1200 A', 'Время полной зарядки' => '3 h 30 min', 'Выходы питания' => '2 × USB / 15 V DC, 5 A'],
                'https://www.suomentyokalu.fi/app/uploads/2017/03/087323.pdf',
                weight: '0.48 kg',
                dimensions: '17.5 × 8.5 × 3 cm'
            ),
        ];
    }

    private function record(
        string $nameRu,
        string $nameRo,
        string $descriptionRu,
        string $descriptionRo,
        array $attributes,
        string $referenceUrl,
        string $referenceType = 'manufacturer_datasheet',
        ?string $weight = null,
        ?string $dimensions = null,
    ): array {
        return compact(
            'nameRu', 'nameRo', 'descriptionRu', 'descriptionRo', 'attributes',
            'referenceUrl', 'referenceType', 'weight', 'dimensions'
        );
    }

    private function updateProduct(object $product, array $content, ?int $categoryId): void
    {
        $now = now();
        $sourceDomain = parse_url($content['referenceUrl'], PHP_URL_HOST);
        $isManufacturerPublication = str_starts_with($content['referenceType'], 'manufacturer_');
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sourceUrls = $this->appendReferenceUrl($product->parser_source_urls ?? null, $content['referenceUrl']);

        DB::table('products')->where('id', $product->id)->update([
            'name' => $content['nameRu'],
            'name_ru' => $content['nameRu'],
            'name_ro' => $content['nameRo'],
            'short_description' => $content['descriptionRu'],
            'short_description_ru' => $content['descriptionRu'],
            'short_description_ro' => $content['descriptionRo'],
            'description' => $content['descriptionRu'],
            'description_ru' => $content['descriptionRu'],
            'description_ro' => $content['descriptionRo'],
            'attributes' => $attributes,
            'weight' => $content['weight'],
            'dimensions' => $content['dimensions'],
            'category_id' => $categoryId,
            'parser_source_urls' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES),
            'source_url' => $content['referenceUrl'],
            'source_domain' => $sourceDomain,
            'source_type' => $content['referenceType'],
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'needs_content_review' => false,
            'generated_content' => false,
            'meta_title' => $content['nameRu'].' | MasterScule.md',
            'meta_description' => mb_substr($content['descriptionRu'], 0, 250),
            'updated_at' => $now,
        ]);

        if (! $product->source_parser_item_id) {
            return;
        }

        $parserItem = DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->first();
        $parserSourceUrls = $this->appendReferenceUrl($parserItem?->source_urls_json, $content['referenceUrl']);

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
            'name_ru' => $content['nameRu'],
            'name_ro' => $content['nameRo'],
            'short_description_ru' => $content['descriptionRu'],
            'short_description_ro' => $content['descriptionRo'],
            'description_ru' => $content['descriptionRu'],
            'description_ro' => $content['descriptionRo'],
            'found_title' => $content['nameRu'],
            'found_description' => $content['descriptionRu'],
            'found_specs_json' => $attributes,
            'source_urls_json' => json_encode($parserSourceUrls, JSON_UNESCAPED_SLASHES),
            'official_source_url' => $isManufacturerPublication ? $content['referenceUrl'] : null,
            'official_source_domain' => $isManufacturerPublication ? $sourceDomain : null,
            'official_source_confidence' => $isManufacturerPublication ? 95 : null,
            'fallback_source_url' => null,
            'fallback_source_domain' => null,
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'needs_content_review' => false,
            'generated_content' => false,
            'content_source_type' => $content['referenceType'],
            'translation_source_type' => 'curated_translation',
            'translation_reviewed_at' => $now,
            'category_id' => $categoryId,
            'detected_category_id' => $categoryId,
            'detected_category_path' => 'baterii-incarcatoare',
            'category_confidence_score' => 100,
            'category_detection_method' => $this->mode,
            'needs_category_review' => false,
            'updated_at' => $now,
        ]);

        DB::table('product_parser_sources')->updateOrInsert(
            ['parser_item_id' => $product->source_parser_item_id, 'url' => $content['referenceUrl']],
            [
                'domain' => $sourceDomain,
                'title' => 'GYS reference — '.$product->sku,
                'snippet' => 'GYS publication matched by exact SKU.',
                'source_type' => $content['referenceType'],
                'confidence_score' => 95,
                'raw_data_json' => json_encode(['sku' => $product->sku, 'brand' => 'GYS'], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function appendReferenceUrl(?string $json, string $referenceUrl): array
    {
        $urls = json_decode($json ?: '[]', true);
        $urls = is_array($urls) ? $urls : [];
        $urls = array_filter($urls, fn ($url) => is_string($url) && ! $this->isRetiredMarketplaceUrl($url));
        $urls[] = $referenceUrl;

        return array_values(array_unique($urls));
    }

    private function purgeRetiredMarketplaceSourceHistory(): void
    {
        DB::table('products')
            ->where(function ($query): void {
                $query->where('parser_source_urls', 'like', '%maximum.md%')
                    ->orWhere('parser_source_urls', 'like', '%maxim.md%')
                    ->orWhere('parser_source_urls', 'like', '%simpalsmedia.com%');
            })
            ->orderBy('id')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    $urls = json_decode($product->parser_source_urls ?: '[]', true);
                    $urls = is_array($urls) ? $urls : [];
                    $urls = array_values(array_filter(
                        $urls,
                        fn ($url) => is_string($url) && ! $this->isRetiredMarketplaceUrl($url)
                    ));

                    DB::table('products')->where('id', $product->id)->update([
                        'parser_source_urls' => json_encode($urls, JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
                }
            });

        DB::table('product_parser_items')
            ->where(function ($query): void {
                $query->where('source_urls_json', 'like', '%maximum.md%')
                    ->orWhere('source_urls_json', 'like', '%maxim.md%')
                    ->orWhere('source_urls_json', 'like', '%simpalsmedia.com%');
            })
            ->orderBy('id')
            ->chunkById(200, function ($items): void {
                foreach ($items as $item) {
                    $urls = json_decode($item->source_urls_json ?: '[]', true);
                    $urls = is_array($urls) ? $urls : [];
                    $urls = array_values(array_filter(
                        $urls,
                        fn ($url) => is_string($url) && ! $this->isRetiredMarketplaceUrl($url)
                    ));

                    DB::table('product_parser_items')->where('id', $item->id)->update([
                        'source_urls_json' => json_encode($urls, JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    private function isRetiredMarketplaceUrl(string $url): bool
    {
        $url = mb_strtolower($url);

        return str_contains($url, 'maximum.md')
            || str_contains($url, 'maxim.md')
            || str_contains($url, 'simpalsmedia.com');
    }

    public function down(): void
    {
        // Curated exact-SKU content and retired marketplace provenance are intentionally retained.
    }
};
