<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->content() as $sku => $data) {
            $product = DB::table('products')->where('sku', $sku)->first(['id']);
            if (! $product) {
                continue;
            }

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
                'meta_title' => Str::limit($data['name_ru'].' | MasterScule', 255, ''),
                'meta_description' => Str::limit($data['short_ru'], 155, ''),
                'attributes' => json_encode($data['attributes'] + ['Артикул производителя' => $sku], JSON_UNESCAPED_UNICODE),
                'needs_translation_review' => false,
                'needs_content_review' => false,
                'generated_content' => false,
                'updated_at' => $now,
            ]);
            DB::table('product_parser_items')->where('sku', $sku)->update([
                'name_ru' => $data['name_ru'],
                'name_ro' => $data['name_ro'],
                'short_description_ru' => $data['short_ru'],
                'short_description_ro' => $data['short_ro'],
                'description_ru' => $data['description_ru'],
                'description_ro' => $data['description_ro'],
                'found_specs_json' => json_encode($data['attributes'] + ['Артикул производителя' => $sku], JSON_UNESCAPED_UNICODE),
                'needs_translation_review' => false,
                'needs_content_review' => false,
                'generated_content' => false,
                'translation_source_type' => 'verified_manual_translation',
                'translation_reviewed_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function content(): array
    {
        return [
            '088795' => $this->item(
                'Портативный компрессор GYS NOMAD AIR с Powerbank и фонарём',
                'Compresor portabil GYS NOMAD AIR cu Powerbank și lanternă',
                'Портативный компрессор GYS NOMAD AIR, SKU 088795, объединяет воздушный компрессор, внешний аккумулятор и светодиодный фонарь. Компрессор развивает давление до 10 бар при производительности 30 л/мин; встроенная батарея LiCoO2 ёмкостью 2,5 А·ч заряжается примерно за 2 ч 15 мин и питает USB-выход 5 В / 2,1 А.',
                'Compresorul portabil GYS NOMAD AIR, SKU 088795, combină un compresor de aer, o baterie externă și o lanternă LED. Compresorul dezvoltă până la 10 bar la un debit de 30 l/min; bateria LiCoO2 de 2,5 Ah se încarcă în aproximativ 2 h 15 min și alimentează ieșirea USB de 5 V / 2,1 A.',
                ['Тип' => 'Портативный компрессор, Powerbank и фонарь', 'Тип аккумулятора' => 'LiCoO2', 'Ёмкость аккумулятора' => '2,5 А·ч', 'Максимальное давление' => '10 бар', 'Производительность' => '30 л/мин', 'Время зарядки' => '2 ч 15 мин', 'USB-выход' => '5 В / 2,1 А', 'Вес' => '0,61 кг']),
            '052499' => $this->item(
                'Адаптер GYS для подключения RINGMATIC к зажиму или пистолету',
                'Adaptor GYS pentru conectarea RINGMATIC la clemă sau pistol',
                'Адаптер GYS, SKU 052499, предназначен для подключения устройства RINGMATIC к кузовному зажиму или сварочному пистолету. Совместим с оборудованием RINGMATIC, MANULINER и MANUSPOT.',
                'Adaptorul GYS, SKU 052499, este destinat conectării dispozitivului RINGMATIC la o clemă de caroserie sau la un pistol de sudură. Compatibil cu echipamentele RINGMATIC, MANULINER și MANUSPOT.',
                ['Тип' => 'Соединительный адаптер', 'Совместимость' => 'RINGMATIC / MANULINER / MANUSPOT', 'Назначение' => 'Подключение к зажиму или сварочному пистолету']),
            '08582' => $this->item(
                'Комплект пробойников GYS для отверстий под датчики, 16 размеров',
                'Set de perforatoare GYS pentru orificii de senzori, 16 dimensiuni',
                'Комплект GYS, SKU 08582, предназначен для точного пробивания отверстий под парковочные датчики в пластиковых бамперах без повреждения лакокрасочного покрытия. В набор входят сверло Ø10 мм, ручной инструмент с трещоткой и 16 пробойников диаметром 18–38,9 мм.',
                'Setul GYS, SKU 08582, este destinat perforării precise a orificiilor pentru senzorii de parcare în barele de protecție din plastic, fără deteriorarea vopselei. Include burghiu Ø10 mm, unealtă manuală cu clichet și 16 perforatoare cu diametre între 18 și 38,9 mm.',
                ['Тип' => 'Комплект пробойников для бампера', 'Количество пробойников' => '16', 'Диаметры' => '18 / 18,2 / 18,4 / 18,6 / 24 / 26 / 26,5 / 26,7 / 27,4 / 28,1 / 29,3 / 32 / 32,5 / 34,4 / 37,4 / 38,9 мм', 'Сверло' => 'Ø10 мм', 'Привод' => 'Ручной инструмент с трещоткой']),
            '080188' => $this->item(
                'Станция для удаления вмятин GYS GYSPOT ALU 66 с тележкой',
                'Stație de îndreptare GYS GYSPOT ALU 66 cu cărucior',
                'Станция GYS GYSPOT ALU 66, SKU 080188, предназначена для рихтовки алюминиевых кузовных деталей. Аппарат приваривает шпильки, с помощью которых удаляют вмятины на дверях, капотах и других панелях без демонтажа внутренней обшивки. Комплект поставляется с универсальной тележкой 800.',
                'Stația GYS GYSPOT ALU 66, SKU 080188, este destinată îndreptării elementelor de caroserie din aluminiu. Aparatul sudează știfturi cu ajutorul cărora se elimină adânciturile de pe uși, capote și alte panouri fără demontarea căptușelii interioare. Setul include căruciorul universal 800.',
                ['Тип' => 'Станция для удаления вмятин', 'Обрабатываемый материал' => 'Алюминий', 'Технология' => 'Приварка шпилек', 'Комплектация' => 'GYSPOT ALU 66 + универсальная тележка 800']),
            'TRK0210B' => $this->item(
                'Длинный гидравлический цилиндр Torin TRK0210B, 10 т',
                'Cilindru hidraulic lung Torin TRK0210B, 10 t',
                'Длинный гидравлический цилиндр Torin TRK0210B развивает усилие до 10 т. Ход штока составляет 135 мм, общая длина цилиндра — 358 мм, масса — 5 кг.',
                'Cilindrul hidraulic lung Torin TRK0210B dezvoltă o forță de până la 10 t. Cursa tijei este de 135 mm, lungimea totală a cilindrului este de 358 mm, iar masa este de 5 kg.',
                ['Тип' => 'Длинный гидравлический цилиндр', 'Максимальное усилие' => '10 т', 'Ход штока' => '135 мм', 'Длина цилиндра' => '358 мм', 'Вес' => '5 кг']),
            'TRK0204B' => $this->item(
                'Длинный гидравлический цилиндр Torin TRK0204B, 4 т',
                'Cilindru hidraulic lung Torin TRK0204B, 4 t',
                'Длинный гидравлический цилиндр Torin TRK0204B развивает усилие до 4 т. Ход штока составляет 125 мм, общая длина цилиндра — 270 мм.',
                'Cilindrul hidraulic lung Torin TRK0204B dezvoltă o forță de până la 4 t. Cursa tijei este de 125 mm, iar lungimea totală a cilindrului este de 270 mm.',
                ['Тип' => 'Длинный гидравлический цилиндр', 'Максимальное усилие' => '4 т', 'Ход штока' => '125 мм', 'Длина цилиндра' => '270 мм']),
            'TRK0210A' => $this->item(
                'Короткий гидравлический цилиндр Torin TRK0210A, 10 т',
                'Cilindru hidraulic scurt Torin TRK0210A, 10 t',
                'Компактный гидравлический цилиндр Torin TRK0210A развивает усилие до 10 т. Ход штока составляет 54 мм, общая длина цилиндра — 118 мм.',
                'Cilindrul hidraulic compact Torin TRK0210A dezvoltă o forță de până la 10 t. Cursa tijei este de 54 mm, iar lungimea totală a cilindrului este de 118 mm.',
                ['Тип' => 'Короткий гидравлический цилиндр', 'Максимальное усилие' => '10 т', 'Ход штока' => '54 мм', 'Длина цилиндра' => '118 мм']),
            'TE10001' => $this->item(
                'Гидравлический трансмиссионный домкрат Torin TE10001, 1 т',
                'Cric hidraulic pentru transmisii Torin TE10001, 1 t',
                'Гидравлический трансмиссионный домкрат Torin TE10001 рассчитан на груз до 1000 кг. Высота подхвата составляет 210 мм, максимальная высота подъёма — 780 мм. Габариты 950 × 520 × 220 мм, масса 75 кг.',
                'Cricul hidraulic pentru transmisii Torin TE10001 este proiectat pentru sarcini de până la 1000 kg. Înălțimea minimă este de 210 mm, iar înălțimea maximă de ridicare este de 780 mm. Dimensiuni 950 × 520 × 220 mm, masă 75 kg.',
                ['Тип' => 'Гидравлический трансмиссионный домкрат', 'Грузоподъёмность' => '1000 кг', 'Минимальная высота' => '210 мм', 'Максимальная высота' => '780 мм', 'Габариты' => '950 × 520 × 220 мм', 'Вес' => '75 кг']),
            'TDP12003' => $this->item(
                'Электрический подъёмный стол Torin TDP12003 для батарей электромобилей, 1,2 т',
                'Masă electrică de ridicare Torin TDP12003 pentru baterii EV, 1,2 t',
                'Электрический ножничный подъёмный стол Torin TDP12003 предназначен для снятия и установки тяговых батарей, двигателей и редукторов электромобилей. Грузоподъёмность 1200 кг, диапазон высоты 650–1840 мм, платформа 1290 + 110 × 770 мм с регулировкой ±40 мм в четырёх направлениях. Электропривод 220 В, 0,75 кВт.',
                'Masa electrică de ridicare tip foarfecă Torin TDP12003 este destinată demontării și montării bateriilor de tracțiune, motoarelor și reductoarelor vehiculelor electrice. Capacitate 1200 kg, interval de înălțime 650–1840 mm, platformă 1290 + 110 × 770 mm reglabilă cu ±40 mm în patru direcții. Unitate electrică 220 V, 0,75 kW.',
                ['Тип' => 'Электрический ножничный подъёмный стол', 'Грузоподъёмность' => '1200 кг', 'Минимальная высота' => '650 мм', 'Максимальная высота' => '1840 мм', 'Размер платформы' => '1290 + 110 × 770 мм', 'Регулировка платформы' => '±40 мм в четырёх направлениях', 'Электропитание' => '220 В / 0,75 кВт', 'Вес нетто' => '438 кг']),
            'TH810002' => $this->item(
                'Двухцилиндровый низкопрофильный бутылочный домкрат Torin TH810002, 10 т',
                'Cric hidraulic tip butelie cu doi cilindri Torin TH810002, 10 t',
                'Низкопрофильный бутылочный домкрат Torin TH810002 грузоподъёмностью 10 т имеет два цилиндра и сварную герметичную конструкцию. Внутренний предохранительный клапан, двойные уплотнения насоса и шток с химическим никелевым покрытием снижают риск утечки и коррозии. Диапазон высоты 125–350 мм, масса 8 кг.',
                'Cricul hidraulic tip butelie Torin TH810002, cu profil jos și capacitate de 10 t, are doi cilindri și construcție sudată etanșă. Supapa internă de siguranță, garniturile duble ale pompei și pistonul nichelat chimic reduc riscul de scurgeri și coroziune. Interval de înălțime 125–350 mm, masă 8 kg.',
                ['Тип' => 'Двухцилиндровый бутылочный домкрат', 'Грузоподъёмность' => '10 т', 'Количество цилиндров' => '2', 'Минимальная высота' => '125 мм', 'Максимальная высота' => '350 мм', 'Вес' => '8 кг']),
            'TRHS-A1020C' => $this->item(
                'Компрессометр Torin TRHS-A1020C для дизельных двигателей',
                'Compresmetru Torin TRHS-A1020C pentru motoare diesel',
                'Компрессометр Torin TRHS-A1020C предназначен для диагностики дизельных двигателей. Манометр до 40 бар защищён резиновым кожухом; комплект включает адаптеры свечей накаливания 8, 10, 12, 14 и 18 мм, адаптеры форсунок M20, M22 и M24, быстросъёмное соединение и угловой адаптер 90°.',
                'Compresmetrul Torin TRHS-A1020C este destinat diagnosticării motoarelor diesel. Manometrul de până la 40 bar este protejat cu cauciuc; setul include adaptoare pentru bujii incandescente de 8, 10, 12, 14 și 18 mm, adaptoare pentru injectoare M20, M22 și M24, cuplă rapidă și adaptor la 90°.',
                ['Тип' => 'Компрессометр для дизельных двигателей', 'Диапазон манометра' => '0–40 бар', 'Адаптеры свечей' => '8 / 10 / 12 / 14 / 18 мм', 'Адаптеры форсунок' => 'M20 / M22 / M24', 'Соединение' => 'Быстросъёмное', 'Угловой адаптер' => '90°']),
        ];
    }

    private function item(string $nameRu, string $nameRo, string $descriptionRu, string $descriptionRo, array $attributes): array
    {
        return [
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_ru' => Str::limit($descriptionRu, 220, ''),
            'short_ro' => Str::limit($descriptionRo, 220, ''),
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'attributes' => $attributes,
        ];
    }

    public function down(): void
    {
        // Verified localization is intentionally retained.
    }
};
