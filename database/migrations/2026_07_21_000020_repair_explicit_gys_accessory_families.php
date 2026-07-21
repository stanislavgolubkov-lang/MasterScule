<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $records = $this->records();
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

    private function records(): array
    {
        $records = [
            '047044' => [
                'name_ru' => 'Набор магнитных угольников для сварки GYS 047044, 2 штуки',
                'name_ro' => 'Set de echere magnetice pentru sudură GYS 047044, 2 bucăți',
                'description_ru' => 'GYS 047044 — набор из двух магнитных угольников для фиксации деталей во время сварочных работ.',
                'description_ro' => 'GYS 047044 este un set de două echere magnetice pentru fixarea pieselor în timpul lucrărilor de sudură.',
                'attributes' => ['Тип' => 'Магнитный угольник для сварки', 'Количество предметов' => '2', 'Применение' => 'Сварочные работы'],
            ],
            '044197' => [
                'name_ru' => 'Регулируемый магнитный угольник для сварки GYS 044197',
                'name_ro' => 'Echer magnetic reglabil pentru sudură GYS 044197',
                'description_ru' => 'Регулируемый магнитный угольник GYS 044197 предназначен для фиксации деталей при сварочных работах.',
                'description_ro' => 'Echerul magnetic reglabil GYS 044197 este destinat fixării pieselor în timpul lucrărilor de sudură.',
                'attributes' => ['Тип' => 'Регулируемый магнитный угольник для сварки', 'Исполнение' => 'Регулируемый', 'Применение' => 'Сварочные работы'],
            ],
            '044203' => [
                'name_ru' => 'Магнитный угольник для сварки GYS 044203',
                'name_ro' => 'Echer magnetic pentru sudură GYS 044203',
                'description_ru' => 'Магнитный угольник GYS 044203 предназначен для фиксации деталей при сварочных работах.',
                'description_ro' => 'Echerul magnetic GYS 044203 este destinat fixării pieselor în timpul lucrărilor de sudură.',
                'attributes' => ['Тип' => 'Магнитный угольник для сварки', 'Применение' => 'Сварочные работы'],
            ],
            '043107' => [
                'name_ru' => 'Зажим массы GYS 043107, 150–200 А, сечение кабеля 35 мм²',
                'name_ro' => 'Clemă de masă GYS 043107, 150–200 A, secțiunea cablului 35 mm²',
                'description_ru' => 'Зажим массы GYS 043107 рассчитан на диапазон тока 150–200 А и кабель сечением 35 мм².',
                'description_ro' => 'Clema de masă GYS 043107 este destinată intervalului de curent 150–200 A și cablului cu secțiunea de 35 mm².',
                'attributes' => ['Тип' => 'Зажим массы', 'Диапазон тока' => '150–200 A', 'Сечение кабеля' => '35 mm²'],
            ],
            '086609' => [
                'name_ru' => 'Катушка стальной сварочной проволоки GYS 086609, Ø 0,8 мм, 0,9 кг, катушка Ø 100 мм',
                'name_ro' => 'Bobină de sârmă de oțel pentru sudură GYS 086609, Ø 0,8 mm, 0,9 kg, bobină Ø 100 mm',
                'description_ru' => 'GYS 086609 — катушка стальной сварочной проволоки диаметром 0,8 мм и массой 0,9 кг; диаметр катушки составляет 100 мм.',
                'description_ro' => 'GYS 086609 este o bobină de sârmă de oțel pentru sudură cu diametrul de 0,8 mm și masa de 0,9 kg; diametrul bobinei este de 100 mm.',
                'attributes' => ['Тип' => 'Катушка сварочной проволоки', 'Материал проволоки' => 'Сталь', 'Диаметр проволоки' => '0.8 mm', 'Диаметр катушки' => '100 mm', 'Вес' => '0.9 kg'],
            ],
            '044159' => $this->slagTool('044159', 'Молоток для удаления шлака', 'Ciocan pentru îndepărtarea zgurii', ['Материал' => 'Кованая сталь']),
            '044166' => $this->slagTool('044166', 'Щётка-молоток для удаления шлака', 'Perie-ciocan pentru îndepărtarea zgurii'),
            '044128' => $this->slagTool('044128', 'Молоток для удаления шлака', 'Ciocan pentru îndepărtarea zgurii', ['Вес' => '425 g']),
            '044135' => $this->slagTool('044135', 'Молоток для удаления шлака', 'Ciocan pentru îndepărtarea zgurii', ['Вес' => '465 g']),
            '044227' => $this->wireBrush('044227', 'Дерево', 3),
            '044241' => $this->wireBrush('044241', 'Пластик', 4),
            '054868' => $this->inductionWire('054868', 'Жёсткий прямой провод', 'Conductor drept rigid', ['Длина' => '80 cm']),
            '054813' => $this->inductionWire('054813', 'Плетёный провод для индуктора', 'Conductor împletit pentru inductor', ['Длина' => '800 mm']),
            '054806' => $this->inductionWire('054806', 'Спиральный провод для индуктора', 'Conductor spiralat pentru inductor', ['Диаметр' => '18 mm']),
            '054790' => $this->inductionWire('054790', 'Спиральный провод для индуктора', 'Conductor spiralat pentru inductor', ['Диаметр' => '24 mm']),
            '055469' => $this->inductionWire('055469', 'Спиральный провод для индуктора', 'Conductor spiralat pentru inductor', ['Диаметр' => '30 mm']),
            '066304' => $this->plasticRods('066304', '4 mm', true),
            '067622' => $this->plasticRods('067622', '6 mm'),
            '066298' => $this->plasticRods('066298', '8 mm'),
            '053502' => $this->clamp('053502', 'Бронзовый зажим', 'Clemă din bronz', 200, 'Красный', ['Материал' => 'Бронза']),
            '053403' => $this->clamp('053403', 'Бронзовый зажим', 'Clemă din bronz', 200, 'Чёрный', ['Материал' => 'Бронза']),
            '053816' => $this->clamp('053816', 'Изолированный зажим', 'Clemă izolată', 850, 'Красный'),
            '053793' => $this->clamp('053793', 'Изолированный зажим', 'Clemă izolată', 850, 'Чёрный'),
            '053779' => $this->clamp('053779', 'Изолированный зажим', 'Clemă izolată', 600, 'Красный'),
            '053786' => $this->clamp('053786', 'Изолированный зажим', 'Clemă izolată', 600, 'Чёрный'),
            '087194' => $this->tigFiller('087194', 'Алюминиевая присадка TIG', 'Baghetă de adaos TIG din aluminiu', 'AlMg 5', 'Алюминий'),
            '087033' => $this->tigFiller('087033', 'Стальная присадка TIG', 'Baghetă de adaos TIG din oțel', 'SG2', 'Нелегированная сталь'),
            '045354' => [
                'name_ru' => 'Вольфрамовые электроды GYS 045354 WL15, Ø 2,4 × 150 мм, 10 штук',
                'name_ro' => 'Electrozi de tungsten GYS 045354 WL15, Ø 2,4 × 150 mm, 10 bucăți',
                'description_ru' => 'GYS 045354 — комплект из 10 вольфрамовых электродов марки WL15 диаметром 2,4 мм и длиной 150 мм.',
                'description_ro' => 'GYS 045354 este un set de 10 electrozi de tungsten marca WL15, cu diametrul de 2,4 mm și lungimea de 150 mm.',
                'attributes' => ['Тип' => 'Вольфрамовый электрод', 'Марка электрода' => 'WL15', 'Диаметр' => '2.4 mm', 'Длина' => '150 mm', 'Количество предметов' => '10'],
            ],
            '041059_1' => [
                'name_ru' => 'Контактный наконечник GYS 041059_1, Ø 0,8 мм, M6, для алюминиевой проволоки, 1 штука',
                'name_ro' => 'Duză de contact GYS 041059_1, Ø 0,8 mm, M6, pentru sârmă de aluminiu, 1 bucată',
                'description_ru' => 'Контактный наконечник GYS 041059_1 с резьбой M6 предназначен для алюминиевой проволоки диаметром 0,8 мм; в комплекте одна штука.',
                'description_ro' => 'Duza de contact GYS 041059_1 cu filet M6 este destinată sârmei de aluminiu cu diametrul de 0,8 mm; setul conține o bucată.',
                'attributes' => ['Тип' => 'Контактный наконечник', 'Диаметр проволоки' => '0.8 mm', 'Резьба' => 'M6', 'Применение' => 'Алюминий', 'Количество предметов' => '1'],
            ],
            '060753' => [
                'name_ru' => 'Плазменная горелка GYS 060753 IPT40, длина 4 м',
                'name_ro' => 'Pistolet pentru tăiere cu plasmă GYS 060753 IPT40, lungime 4 m',
                'description_ru' => 'Плазменная горелка GYS 060753 модели IPT40 имеет кабель длиной 4 м.',
                'description_ro' => 'Pistoletul pentru tăiere cu plasmă GYS 060753, model IPT40, are cablul cu lungimea de 4 m.',
                'attributes' => ['Тип' => 'Плазменная горелка', 'Совместимость' => 'IPT40', 'Длина кабеля' => '4 m'],
            ],
            '041592' => [
                'name_ru' => 'Направляющий канал GYS 041592 для стальной проволоки Ø 0,6–0,8 мм, длина 3 м',
                'name_ro' => 'Ghidaj GYS 041592 pentru sârmă de oțel Ø 0,6–0,8 mm, lungime 3 m',
                'description_ru' => 'Направляющий канал GYS 041592 длиной 3 м предназначен для стальной сварочной проволоки диаметром 0,6–0,8 мм.',
                'description_ro' => 'Ghidajul GYS 041592, cu lungimea de 3 m, este destinat sârmei de oțel pentru sudură cu diametrul de 0,6–0,8 mm.',
                'attributes' => ['Тип' => 'Направляющий канал для проволоки', 'Длина' => '3 m', 'Применение' => 'Стальная проволока', 'Диапазон диаметра проволоки' => '0.6–0.8 mm'],
            ],
            '053106' => [
                'name_ru' => 'Зажимы для зарядного устройства GYS 053106, 40 А, 2 штуки',
                'name_ro' => 'Cleme pentru încărcător GYS 053106, 40 A, 2 bucăți',
                'description_ru' => 'GYS 053106 — комплект из двух зажимов для зарядного устройства, рассчитанных на ток 40 А.',
                'description_ro' => 'GYS 053106 este un set de două cleme pentru încărcător, cu curent nominal de 40 A.',
                'attributes' => ['Тип' => 'Зажимы для зарядного устройства', 'Номинальный ток' => '40 A', 'Количество предметов' => '2'],
            ],
        ];

        return $records;
    }

    private function slagTool(string $sku, string $typeRu, string $typeRo, array $extra = []): array
    {
        $weight = $extra['Вес'] ?? null;
        $weightRu = $weight ? str_replace(' g', ' г', $weight) : null;

        return [
            'name_ru' => "{$typeRu} GYS {$sku}".($weightRu ? ', '.$weightRu : ''),
            'name_ro' => "{$typeRo} GYS {$sku}".($weight ? ', '.$weight : ''),
            'description_ru' => "{$typeRu} GYS {$sku} предназначен для удаления шлака после сварочных работ.",
            'description_ro' => "{$typeRo} GYS {$sku} este destinat îndepărtării zgurii după lucrările de sudură.",
            'attributes' => ['Тип' => $typeRu, 'Применение' => 'Удаление шлака', ...$extra],
        ];
    }

    private function wireBrush(string $sku, string $material, int $rows): array
    {
        return [
            'name_ru' => "Проволочная щётка GYS {$sku}, {$rows} стальных ряда, основание — ".mb_strtolower($material),
            'name_ro' => "Perie de sârmă GYS {$sku}, {$rows} rânduri din oțel, suport din ".($material === 'Дерево' ? 'lemn' : 'plastic'),
            'description_ru' => "Проволочная щётка GYS {$sku} имеет {$rows} ряда стальной проволоки и основание из ".mb_strtolower($material).'.',
            'description_ro' => "Peria de sârmă GYS {$sku} are {$rows} rânduri din sârmă de oțel și suport din ".($material === 'Дерево' ? 'lemn.' : 'plastic.'),
            'attributes' => ['Тип' => 'Проволочная щётка', 'Материал' => $material, 'Количество рядов' => (string) $rows],
        ];
    }

    private function inductionWire(string $sku, string $typeRu, string $typeRo, array $dimensions): array
    {
        $dimensionRu = isset($dimensions['Длина']) ? 'длина '.$this->russianUnits($dimensions['Длина']) : 'диаметр '.$this->russianUnits($dimensions['Диаметр']);
        $dimensionRo = isset($dimensions['Длина']) ? 'lungime '.$dimensions['Длина'] : 'diametru '.$dimensions['Диаметр'];

        return [
            'name_ru' => "{$typeRu} GYS {$sku}, {$dimensionRu}",
            'name_ro' => "{$typeRo} GYS {$sku}, {$dimensionRo}",
            'description_ru' => "{$typeRu} GYS {$sku} используется как принадлежность для индукционного нагрева; {$dimensionRu}.",
            'description_ro' => "{$typeRo} GYS {$sku} este utilizat ca accesoriu pentru încălzirea prin inducție; {$dimensionRo}.",
            'attributes' => ['Тип' => $typeRu, 'Применение' => 'Индукционный нагрев', ...$dimensions],
        ];
    }

    private function plasticRods(string $sku, string $size, bool $triangular = false): array
    {
        $sizeRu = $this->russianUnits($size);
        $profileRu = $triangular ? ', треугольный профиль' : '';
        $profileRo = $triangular ? ', profil triunghiular' : '';
        $attributes = ['Тип' => 'Пластиковые ремонтные стержни', 'Материал' => 'PP/EPDM', 'Длина' => '440 mm', 'Размер' => $size, 'Количество предметов' => '10'];
        if ($triangular) {
            $attributes['Исполнение'] = 'Треугольный профиль';
        }

        return [
            'name_ru' => "Пластиковые ремонтные стержни GYS {$sku}, PP/EPDM, 440 × {$sizeRu}{$profileRu}, 10 штук",
            'name_ro' => "Tije din plastic pentru reparații GYS {$sku}, PP/EPDM, 440 × {$size}{$profileRo}, 10 bucăți",
            'description_ru' => "GYS {$sku} — комплект из 10 пластиковых ремонтных стержней PP/EPDM длиной 440 мм и размером {$sizeRu}{$profileRu}.",
            'description_ro' => "GYS {$sku} este un set de 10 tije din plastic PP/EPDM pentru reparații, cu lungimea de 440 mm și dimensiunea de {$size}{$profileRo}.",
            'attributes' => $attributes,
        ];
    }

    private function clamp(string $sku, string $typeRu, string $typeRo, int $current, string $color, array $extra = []): array
    {
        $colorRo = $color === 'Красный' ? 'roșie' : 'neagră';

        return [
            'name_ru' => "{$typeRu} GYS {$sku}, {$current} А, ".mb_strtolower($color),
            'name_ro' => "{$typeRo} GYS {$sku}, {$current} A, {$colorRo}",
            'description_ru' => "{$typeRu} GYS {$sku} рассчитан на номинальный ток {$current} А; цвет — ".mb_strtolower($color).'.',
            'description_ro' => "{$typeRo} GYS {$sku} este destinată curentului nominal de {$current} A; culoare — {$colorRo}.",
            'attributes' => ['Тип' => $typeRu, 'Номинальный ток' => $current.' A', 'Цвет' => $color, ...$extra],
        ];
    }

    private function tigFiller(string $sku, string $typeRu, string $typeRo, string $grade, string $material): array
    {
        return [
            'name_ru' => "{$typeRu} GYS {$sku} {$grade}, Ø 2,4 мм, длина 1 м, 5 кг",
            'name_ro' => "{$typeRo} GYS {$sku} {$grade}, Ø 2,4 mm, lungime 1 m, 5 kg",
            'description_ru' => "{$typeRu} GYS {$sku} марки {$grade} поставляется стержнями длиной 1 м и диаметром 2,4 мм, масса упаковки — 5 кг.",
            'description_ro' => "{$typeRo} GYS {$sku}, marca {$grade}, este livrată sub formă de tije cu lungimea de 1 m și diametrul de 2,4 mm; masa ambalajului este de 5 kg.",
            'attributes' => ['Тип' => $typeRu, 'Материал' => $material, 'Марка электрода' => $grade, 'Диаметр' => '2.4 mm', 'Длина' => '1 m', 'Вес' => '5 kg'],
        ];
    }

    private function russianUnits(string $value): string
    {
        return str_replace([' mm', ' cm', ' g'], [' мм', ' см', ' г'], $value);
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
        // Curated GYS SKU-family content is intentionally retained.
    }
};
