<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $records = array_replace($this->squadRecords(), $this->lightingRecords());
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

    private function squadRecords(): array
    {
        return [
            '87SQ3-13' => [
                'name_ru' => 'Перфорированная панель KING TONY 87SQ3-13 SQUAD, 640 × 540 мм',
                'name_ro' => 'Panou perforat KING TONY 87SQ3-13 SQUAD, 640 × 540 mm',
                'description_ru' => 'Перфорированная панель KING TONY 87SQ3-13 размером 640 × 540 мм устанавливается как задняя панель тележки и совместима с тележками серии 87SQ31.',
                'description_ro' => 'Panoul perforat KING TONY 87SQ3-13, cu dimensiunile 640 × 540 mm, se montează în partea din spate a căruciorului și este compatibil cu seria 87SQ31.',
                'attributes' => ['Тип' => 'Перфорированная панель', 'Габаритные размеры' => '640 × 540 mm', 'Совместимость' => 'Тележки серии 87SQ31'],
            ],
            '87SQ3-22' => [
                'name_ru' => 'Держатель бутылок KING TONY 87SQ3-22 SQUAD, 320 × 101 × 74 мм',
                'name_ro' => 'Suport pentru sticle KING TONY 87SQ3-22 SQUAD, 320 × 101 × 74 mm',
                'description_ru' => 'Держатель KING TONY 87SQ3-22 предназначен для размещения бутылок на тележках SQUAD. Габаритные размеры — 320 × 101 × 74 мм.',
                'description_ro' => 'Suportul KING TONY 87SQ3-22 este destinat amplasării sticlelor pe cărucioarele SQUAD. Dimensiunile sunt 320 × 101 × 74 mm.',
                'attributes' => ['Тип' => 'Держатель бутылок', 'Габаритные размеры' => '320 × 101 × 74 mm', 'Совместимость' => 'Тележки KING TONY SQUAD'],
            ],
            '87SQ3-23' => [
                'name_ru' => 'Держатель электро- и пневмоинструмента KING TONY 87SQ3-23 SQUAD',
                'name_ro' => 'Suport pentru scule electrice și pneumatice KING TONY 87SQ3-23 SQUAD',
                'description_ru' => 'Держатель KING TONY 87SQ3-23 размером 285 × 172 × 71 мм имеет три паза для инструментов, шлангов или ключей и три крючка для одежды, перчаток или проводов. Предназначен для тележек SQUAD.',
                'description_ro' => 'Suportul KING TONY 87SQ3-23, cu dimensiunile 285 × 172 × 71 mm, are trei fante pentru scule, furtunuri sau chei și trei cârlige pentru haine, mănuși ori cabluri. Este destinat cărucioarelor SQUAD.',
                'attributes' => ['Тип' => 'Держатель электроинструмента', 'Габаритные размеры' => '285 × 172 × 71 mm', 'Количество пазов' => '3', 'Количество крючков' => '3', 'Совместимость' => 'Тележки KING TONY SQUAD'],
            ],
            '87SQ3-31' => [
                'name_ru' => 'Поворотный держатель ноутбука или планшета KING TONY 87SQ3-31 SQUAD',
                'name_ro' => 'Suport pivotant pentru laptop sau tabletă KING TONY 87SQ3-31 SQUAD',
                'description_ru' => 'Держатель KING TONY 87SQ3-31 размером 378 × 285 × 79 мм предназначен для ноутбука или планшета. Платформа поворачивается на 360° и фиксируется под углами 20°, 45° или 60°; снизу расположен ящик для принадлежностей.',
                'description_ro' => 'Suportul KING TONY 87SQ3-31, cu dimensiunile 378 × 285 × 79 mm, este destinat unui laptop sau unei tablete. Platforma se rotește la 360° și se reglează la 20°, 45° ori 60°; dedesubt se află un sertar pentru accesorii.',
                'attributes' => ['Тип' => 'Держатель ноутбука или планшета', 'Габаритные размеры' => '378 × 285 × 79 mm', 'Угол поворота' => '360°', 'Углы регулировки' => '20°, 45°, 60°', 'Совместимость' => 'Тележки KING TONY SQUAD'],
            ],
            '87SQ3-32' => [
                'name_ru' => 'Держатель телефона KING TONY 87SQ3-32 SQUAD, 200 × 52 мм',
                'name_ro' => 'Suport pentru telefon KING TONY 87SQ3-32 SQUAD, 200 × 52 mm',
                'description_ru' => 'Держатель телефона KING TONY 87SQ3-32 размером 200 × 52 мм предназначен для установки на совместимую перфорированную панель SQUAD.',
                'description_ro' => 'Suportul pentru telefon KING TONY 87SQ3-32, cu dimensiunile 200 × 52 mm, este destinat montării pe un panou perforat SQUAD compatibil.',
                'attributes' => ['Тип' => 'Держатель телефона', 'Габаритные размеры' => '200 × 52 mm', 'Совместимость' => 'Перфорированная панель SQUAD'],
            ],
            '87SQ3-41' => [
                'name_ru' => 'Навесная корзина KING TONY 87SQ3-41 SQUAD, 5 л',
                'name_ro' => 'Coș suspendat KING TONY 87SQ3-41 SQUAD, 5 l',
                'description_ru' => 'Навесная корзина KING TONY 87SQ3-41 объёмом 5 л крепится сбоку тележки SQUAD. Габаритные размеры — 275 × 150 × 136 мм.',
                'description_ro' => 'Coșul suspendat KING TONY 87SQ3-41, cu volumul de 5 l, se fixează lateral pe căruciorul SQUAD. Dimensiunile sunt 275 × 150 × 136 mm.',
                'attributes' => ['Тип' => 'Навесная корзина', 'Объём' => '5 l', 'Габаритные размеры' => '275 × 150 × 136 mm', 'Совместимость' => 'Тележки KING TONY SQUAD'],
            ],
            '87SQ3-42' => [
                'name_ru' => 'Мусорная корзина KING TONY 87SQ3-42 SQUAD, 25 л',
                'name_ro' => 'Coș de gunoi KING TONY 87SQ3-42 SQUAD, 25 l',
                'description_ru' => 'Мусорная корзина KING TONY 87SQ3-42 объёмом 25 л устанавливается сбоку тележки SQUAD и может использоваться вместе с держателем бумажных рулонов. Габаритные размеры — 363 × 232 × 411 мм.',
                'description_ro' => 'Coșul de gunoi KING TONY 87SQ3-42, cu volumul de 25 l, se montează lateral pe căruciorul SQUAD și poate fi utilizat împreună cu suportul pentru role de hârtie. Dimensiunile sunt 363 × 232 × 411 mm.',
                'attributes' => ['Тип' => 'Мусорная корзина', 'Объём' => '25 l', 'Габаритные размеры' => '363 × 232 × 411 mm', 'Совместимость' => 'Тележки KING TONY SQUAD'],
            ],
            '87SQ3-51' => [
                'name_ru' => 'Держатель бумажных рулонов KING TONY 87SQ3-51 SQUAD, до 280 мм',
                'name_ro' => 'Suport pentru role de hârtie KING TONY 87SQ3-51 SQUAD, până la 280 mm',
                'description_ru' => 'Держатель KING TONY 87SQ3-51 рассчитан на бумажные рулоны шириной до 280 мм. Может использоваться отдельно или устанавливаться над мусорной корзиной KING TONY 87SQ3-42.',
                'description_ro' => 'Suportul KING TONY 87SQ3-51 este destinat rolelor de hârtie cu lățimea de până la 280 mm. Poate fi folosit separat sau montat deasupra coșului KING TONY 87SQ3-42.',
                'attributes' => ['Тип' => 'Держатель бумажных рулонов', 'Максимальная ширина рулона' => '280 mm', 'Совместимость' => 'Корзина KING TONY 87SQ3-42'],
            ],
        ];
    }

    private function lightingRecords(): array
    {
        return [
            '79814' => [
                'name_ru' => 'Светодиодный фонарь KING TONY 79814, 1 Вт, 30 лм',
                'name_ro' => 'Lanternă LED KING TONY 79814, 1 W, 30 lm',
                'description_ru' => 'Компактный светодиодный фонарь KING TONY 79814 мощностью 1 Вт создаёт световой поток 30 лм и предназначен для локального освещения рабочей зоны.',
                'description_ro' => 'Lanterna LED compactă KING TONY 79814 are puterea de 1 W, fluxul luminos de 30 lm și este destinată iluminării locale a zonei de lucru.',
                'attributes' => ['Тип' => 'Светодиодный фонарь', 'Источник света' => 'LED', 'Мощность' => '1 W', 'Световой поток' => '30 lm'],
            ],
            '9TA33A' => [
                'name_ru' => 'Магнитный подкапотный светильник KING TONY 9TA33A, COB LED, 10 Вт',
                'name_ro' => 'Lampă magnetică pentru compartimentul motor KING TONY 9TA33A, COB LED, 10 W',
                'description_ru' => 'Подкапотный светильник KING TONY 9TA33A оснащён источником COB LED мощностью 10 Вт, магнитным креплением и литий-ионным аккумулятором напряжением 7,4 В.',
                'description_ro' => 'Lampa pentru compartimentul motor KING TONY 9TA33A este echipată cu o sursă COB LED de 10 W, fixare magnetică și acumulator litiu-ion de 7,4 V.',
                'attributes' => ['Тип' => 'Магнитный подкапотный светильник', 'Источник света' => 'COB LED', 'Мощность' => '10 W', 'Источник питания' => 'Литий-ионный аккумулятор', 'Напряжение аккумулятора' => '7.4 V', 'Фиксация' => 'Магнитная'],
            ],
            '9TA34A' => [
                'name_ru' => 'Аккумуляторный подкапотный светильник KING TONY 9TA34A, 20 Вт, 2000 лм',
                'name_ro' => 'Lampă reîncărcabilă pentru compartimentul motor KING TONY 9TA34A, 20 W, 2000 lm',
                'description_ru' => 'Подкапотный светильник KING TONY 9TA34A с источником SMD LED мощностью 20 Вт создаёт световой поток 2000 лм при цветовой температуре 6500 K. Литий-ионный аккумулятор 3,6 В ёмкостью 4800 мА·ч заряжается через USB Type-C примерно за 4 часа; степени защиты — IP20 и IK07.',
                'description_ro' => 'Lampa pentru compartimentul motor KING TONY 9TA34A, cu sursă SMD LED de 20 W, oferă un flux de 2000 lm la temperatura de culoare de 6500 K. Acumulatorul litiu-ion de 3,6 V și 4800 mAh se încarcă prin USB Type-C în aproximativ 4 ore; gradele de protecție sunt IP20 și IK07.',
                'attributes' => ['Тип' => 'Аккумуляторный подкапотный светильник', 'Источник света' => 'SMD LED', 'Мощность' => '20 W', 'Световой поток' => '2000 lm', 'Цветовая температура' => '6500 K', 'Источник питания' => 'Литий-ионный аккумулятор', 'Напряжение аккумулятора' => '3.6 V', 'Ёмкость аккумулятора' => '4800 mAh', 'Разъём зарядки' => 'USB Type-C', 'Время зарядки' => '4 h', 'Степень защиты' => 'IP20', 'Ударопрочность' => 'IK07'],
            ],
            '9TA421A' => [
                'name_ru' => 'Проводной рабочий прожектор KING TONY 9TA421A, 30 Вт, 3000 лм',
                'name_ro' => 'Proiector de lucru cu cablu KING TONY 9TA421A, 30 W, 3000 lm',
                'description_ru' => 'Рабочий прожектор KING TONY 9TA421A с источником SMD LED мощностью 30 Вт создаёт световой поток 3000 лм при 6500 K. Корпус поворачивается на 360°, кабель H07RN-F имеет длину 1,8 м; предусмотрены режимы 50% и 100%, защита IP54 и ударопрочность IK08.',
                'description_ro' => 'Proiectorul de lucru KING TONY 9TA421A, cu sursă SMD LED de 30 W, oferă 3000 lm la 6500 K. Corpul se rotește la 360°, cablul H07RN-F are lungimea de 1,8 m; sunt disponibile modurile 50% și 100%, protecția IP54 și rezistența IK08.',
                'attributes' => ['Тип' => 'Проводной рабочий прожектор', 'Источник света' => 'SMD LED', 'Мощность' => '30 W', 'Световой поток' => '3000 lm', 'Цветовая температура' => '6500 K', 'Источник питания' => 'Сеть', 'Длина кабеля' => '1.8 m', 'Угол поворота' => '360°', 'Степень защиты' => 'IP54', 'Ударопрочность' => 'IK08', 'Совместимость' => 'Штатив 87162'],
            ],
            '9TA42A-87162' => [
                'name_ru' => 'Рабочий прожектор KING TONY 9TA42A со штативом 87162, 30 Вт, 3000 лм',
                'name_ro' => 'Proiector de lucru KING TONY 9TA42A cu trepied 87162, 30 W, 3000 lm',
                'description_ru' => 'Комплект KING TONY 9TA42A-87162 состоит из светодиодного рабочего прожектора мощностью 30 Вт со световым потоком 3000 лм и совместимого штатива 87162.',
                'description_ro' => 'Setul KING TONY 9TA42A-87162 include un proiector de lucru LED de 30 W, cu flux luminos de 3000 lm, și trepiedul compatibil 87162.',
                'attributes' => ['Тип' => 'Рабочий прожектор на штативе', 'Источник света' => 'LED', 'Мощность' => '30 W', 'Световой поток' => '3000 lm', 'Исполнение' => 'Штатив'],
            ],
            '9TA52A' => [
                'name_ru' => 'Аккумуляторный налобный фонарь KING TONY 9TA52A, 3 Вт, 280 лм',
                'name_ro' => 'Lanternă frontală reîncărcabilă KING TONY 9TA52A, 3 W, 280 lm',
                'description_ru' => 'Аккумуляторный налобный фонарь KING TONY 9TA52A оснащён светодиодным источником мощностью 3 Вт и создаёт световой поток до 280 лм.',
                'description_ro' => 'Lanterna frontală reîncărcabilă KING TONY 9TA52A este echipată cu o sursă LED de 3 W și oferă un flux luminos de până la 280 lm.',
                'attributes' => ['Тип' => 'Аккумуляторный налобный фонарь', 'Источник света' => 'LED', 'Мощность' => '3 W', 'Световой поток' => '280 lm', 'Источник питания' => 'Аккумулятор'],
            ],
            '9TA53' => [
                'name_ru' => 'Налобный фонарь с двумя лучами KING TONY 9TA53, 4 Вт, 400 лм',
                'name_ro' => 'Lanternă frontală cu două fascicule KING TONY 9TA53, 4 W, 400 lm',
                'description_ru' => 'Налобный фонарь KING TONY 9TA53 мощностью 4 Вт создаёт световой поток до 400 лм при 6500 K. Оснащён датчиком движения с дистанцией 10 см и регулировкой наклона на 60°; питание — батареи AAA, степени защиты — IP65 и IK07.',
                'description_ro' => 'Lanterna frontală KING TONY 9TA53 de 4 W oferă un flux de până la 400 lm la 6500 K. Este prevăzută cu senzor de mișcare la 10 cm și reglare la 60°; alimentarea se face cu baterii AAA, iar gradele de protecție sunt IP65 și IK07.',
                'attributes' => ['Тип' => 'Налобный фонарь с двумя лучами', 'Источник света' => 'LED', 'Мощность' => '4 W', 'Световой поток' => '400 lm', 'Цветовая температура' => '6500 K', 'Источник питания' => 'Батареи AAA', 'Угол регулировки' => '60°', 'Дистанция датчика' => '10 cm', 'Степень защиты' => 'IP65', 'Ударопрочность' => 'IK07'],
            ],
            '9TA56' => [
                'name_ru' => 'Шапка со светодиодным фонарём KING TONY 9TA56, 1,7 Вт, 170 лм',
                'name_ro' => 'Căciulă cu lanternă LED KING TONY 9TA56, 1,7 W, 170 lm',
                'description_ru' => 'Шапка KING TONY 9TA56 оснащена встроенным светодиодным фонарём мощностью 1,7 Вт со световым потоком до 170 лм. Питание обеспечивает литий-ионный аккумулятор 3,7 В ёмкостью 250 мА·ч.',
                'description_ro' => 'Căciula KING TONY 9TA56 este echipată cu o lanternă LED integrată de 1,7 W, cu flux luminos de până la 170 lm. Alimentarea este asigurată de un acumulator litiu-ion de 3,7 V și 250 mAh.',
                'attributes' => ['Тип' => 'Шапка со светодиодным фонарём', 'Источник света' => 'LED', 'Мощность' => '1.7 W', 'Световой поток' => '170 lm', 'Источник питания' => 'Литий-ионный аккумулятор', 'Напряжение аккумулятора' => '3.7 V', 'Ёмкость аккумулятора' => '250 mAh'],
            ],
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
            'needs_content_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ]);

        if (! $product->source_parser_item_id) {
            return;
        }

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
            'name_ru' => $content['name_ru'],
            'name_ro' => $content['name_ro'],
            'short_description_ru' => $content['description_ru'],
            'short_description_ro' => $content['description_ro'],
            'description_ru' => $content['description_ru'],
            'description_ro' => $content['description_ro'],
            'found_title' => $content['name_ru'],
            'found_description' => $content['description_ru'],
            'found_specs_json' => $attributes,
            'needs_content_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Curated KING TONY SQUAD and lighting content is intentionally retained.
    }
};
