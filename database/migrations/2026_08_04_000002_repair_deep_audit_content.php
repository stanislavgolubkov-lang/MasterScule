<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();

            $replaceTitle = static function (string $sku, string $locale, string $newTitle) use ($now): void {
                $product = DB::table('products')->where('sku', $sku)->first();
                if (! $product) {
                    return;
                }

                $nameColumn = $locale === 'ru' ? 'name_ru' : 'name_ro';
                $oldTitle = trim((string) ($product->{$nameColumn} ?? ''));
                $updates = [$nameColumn => $newTitle, 'updated_at' => $now];

                $contentColumns = $locale === 'ru'
                    ? ['short_description', 'short_description_ru', 'description', 'description_ru', 'meta_title', 'meta_description']
                    : ['short_description_ro', 'description_ro'];

                if ($locale === 'ru') {
                    $updates['name'] = $newTitle;
                }

                if ($oldTitle !== '') {
                    foreach ($contentColumns as $column) {
                        $value = (string) ($product->{$column} ?? '');
                        if ($value !== '' && str_contains($value, $oldTitle)) {
                            $updates[$column] = str_replace($oldTitle, $newTitle, $value);
                        }
                    }
                }

                DB::table('products')->where('sku', $sku)->update($updates);
            };

            $setBilingualContent = static function (string $sku, array $content) use ($now): void {
                $content['name'] = $content['name_ru'];
                $content['short_description'] = $content['short_description_ru'];
                $content['description'] = $content['description_ru'];
                $content['updated_at'] = $now;

                DB::table('products')->where('sku', $sku)->update($content);
            };

            DB::table('products')
                ->where('status', 'published')
                ->where('sku', 'like', '10B0%')
                ->orderBy('id')
                ->get(['sku'])
                ->each(function (object $product) use ($setBilingualContent): void {
                    $sku = (string) $product->sku;
                    $setBilingualContent($sku, [
                        'name_ru' => "Кольцевой ударный ключ KING TONY {$sku}",
                        'name_ro' => "Cheie inelară de șoc KING TONY {$sku}",
                        'short_description_ru' => "Кольцевой ударный ключ KING TONY {$sku} с фосфатированным покрытием.",
                        'short_description_ro' => "Cheie inelară de șoc KING TONY {$sku}, cu finisaj fosfatat.",
                        'description_ru' => "Кольцевой ударный ключ KING TONY {$sku} предназначен для работы с резьбовыми соединениями при помощи ударного инструмента. Имеет фосфатированное покрытие и соответствует стандарту DIN 7444.",
                        'description_ro' => "Cheia inelară de șoc KING TONY {$sku} este destinată acționării îmbinărilor filetate cu ajutorul unui instrument de lovire. Are finisaj fosfatat și respectă standardul DIN 7444.",
                    ]);
                });

            $verifiedContent = [
                '1214MRN01' => [
                    'name_ru' => 'Набор комбинированных ключей KING TONY 1214MRN01, 10–32 мм, 14 предметов',
                    'name_ro' => 'Set de chei combinate KING TONY 1214MRN01, 10–32 mm, 14 piese',
                    'short_description_ru' => 'Набор из 14 комбинированных ключей KING TONY 1214MRN01 размером 10–32 мм.',
                    'short_description_ro' => 'Set de 14 chei combinate KING TONY 1214MRN01, în dimensiuni de 10–32 mm.',
                    'description_ru' => 'KING TONY 1214MRN01 — набор из 14 комбинированных ключей размером от 10 до 32 мм для монтажных и ремонтных работ.',
                    'description_ro' => 'KING TONY 1214MRN01 este un set de 14 chei combinate, cu dimensiuni de la 10 la 32 mm, destinat lucrărilor de montaj și reparație.',
                ],
                '6214-05' => [
                    'name_ru' => 'Мини-кусачки диагональные KING TONY 6214-05',
                    'name_ro' => 'Clește diagonal mini KING TONY 6214-05',
                    'short_description_ru' => 'Компактные диагональные кусачки KING TONY 6214-05 с двухкомпонентными рукоятками.',
                    'short_description_ro' => 'Clește diagonal compact KING TONY 6214-05, cu mânere bicomponente.',
                    'description_ru' => 'Мини-кусачки KING TONY 6214-05 имеют полированные рабочие головки и рукоятки из полипропилена и термопластичной резины.',
                    'description_ro' => 'Cleștele diagonal mini KING TONY 6214-05 are capete de lucru lustruite și mânere din polipropilenă și cauciuc termoplastic.',
                ],
                '6214-45' => [
                    'name_ru' => 'Мини-кусачки диагональные KING TONY 6214-45',
                    'name_ro' => 'Clește diagonal mini KING TONY 6214-45',
                    'short_description_ru' => 'Компактные диагональные кусачки KING TONY 6214-45 с двухкомпонентными рукоятками.',
                    'short_description_ro' => 'Clește diagonal compact KING TONY 6214-45, cu mânere bicomponente.',
                    'description_ru' => 'Мини-кусачки KING TONY 6214-45 имеют полированные рабочие головки и рукоятки из полипропилена и термопластичной резины.',
                    'description_ro' => 'Cleștele diagonal mini KING TONY 6214-45 are capete de lucru lustruite și mânere din polipropilenă și cauciuc termoplastic.',
                ],
                '6315-09' => [
                    'name_ru' => 'Миниатюрные длинногубцы KING TONY 6315-09',
                    'name_ro' => 'Clește miniatural cu fălci lungi KING TONY 6315-09',
                    'short_description_ru' => 'Миниатюрные длинногубцы KING TONY 6315-09 для точных работ.',
                    'short_description_ro' => 'Clește miniatural cu fălci lungi KING TONY 6315-09 pentru lucrări de precizie.',
                    'description_ru' => 'Длинногубцы KING TONY 6315-09 предназначены для точных работ. Губки изготовлены из пружинной легированной стали, рукоятки — из хромомолибденовой легированной стали. Соответствуют ASME B107.500-2010.',
                    'description_ro' => 'Cleștele KING TONY 6315-09 este destinat lucrărilor de precizie. Fălcile sunt fabricate din oțel aliat pentru arcuri, iar mânerele din oțel aliat crom-molibden. Respectă ASME B107.500-2010.',
                ],
                '6462-40P' => [
                    'name_ru' => 'Вороток с шарниром KING TONY 6462-40P, привод 3/4 дюйма',
                    'name_ro' => 'Mâner articulat KING TONY 6462-40P, antrenare 3/4 inch',
                    'short_description_ru' => 'Шарнирный вороток KING TONY 6462-40P с приводом 3/4 дюйма и углом поворота 180°.',
                    'short_description_ro' => 'Mâner articulat KING TONY 6462-40P, cu antrenare de 3/4 inch și unghi de 180°.',
                    'description_ru' => 'Вороток KING TONY 6462-40P оснащён приводом 3/4 дюйма и шарнирной головкой с диапазоном поворота 180°. Соответствует стандарту DIN 3122.',
                    'description_ro' => 'Mânerul articulat KING TONY 6462-40P are antrenare de 3/4 inch și cap articulat cu unghi de lucru de 180°. Respectă standardul DIN 3122.',
                ],
                '6517-08C' => [
                    'name_ru' => 'Переставные клещи с быстрой фиксацией KING TONY 6517-08C',
                    'name_ro' => 'Clește reglabil cu blocare rapidă KING TONY 6517-08C',
                    'short_description_ru' => 'Переставные клещи KING TONY 6517-08C с быстрой фиксацией и ПВХ-рукоятками.',
                    'short_description_ro' => 'Clește reglabil KING TONY 6517-08C cu blocare rapidă și mânere din PVC.',
                    'description_ru' => 'Переставные клещи KING TONY 6517-08C оснащены механизмом быстрой фиксации и рукоятками с ПВХ-покрытием. Соответствуют DIN ISO 8976.',
                    'description_ro' => 'Cleștele reglabil KING TONY 6517-08C este prevăzut cu mecanism de blocare rapidă și mânere acoperite cu PVC. Respectă DIN ISO 8976.',
                ],
                '6615-11' => [
                    'name_ru' => 'Зажимные клещи C-образные KING TONY 6615-11, 280 мм',
                    'name_ro' => 'Clește de blocare tip C KING TONY 6615-11, 280 mm',
                    'short_description_ru' => 'C-образные зажимные клещи KING TONY 6615-11 длиной 280 мм.',
                    'short_description_ro' => 'Clește de blocare tip C KING TONY 6615-11, lungime 280 mm.',
                    'description_ru' => 'C-образные зажимные клещи KING TONY 6615-11 предназначены для надёжного захвата и удержания деталей. Длина инструмента составляет 280 мм.',
                    'description_ro' => 'Cleștele de blocare tip C KING TONY 6615-11 este destinat prinderii și menținerii sigure a pieselor. Lungimea instrumentului este de 280 mm.',
                ],
                '6625-18' => [
                    'name_ru' => 'Зажимные клещи C-образные с подвижной губкой KING TONY 6625-18',
                    'name_ro' => 'Clește de blocare tip C cu falcă mobilă KING TONY 6625-18',
                    'short_description_ru' => 'C-образные зажимные клещи KING TONY 6625-18 с подвижной губкой.',
                    'short_description_ro' => 'Clește de blocare tip C KING TONY 6625-18, cu falcă mobilă.',
                    'description_ru' => 'Зажимные клещи KING TONY 6625-18 имеют C-образную конструкцию и подвижную губку для надёжной фиксации деталей различной формы.',
                    'description_ro' => 'Cleștele de blocare KING TONY 6625-18 are construcție tip C și falcă mobilă pentru fixarea sigură a pieselor de diferite forme.',
                ],
                '6921-06A' => [
                    'name_ru' => 'Кусачки для пластика KING TONY 6921-06A',
                    'name_ro' => 'Clește pentru tăierea plasticului KING TONY 6921-06A',
                    'short_description_ru' => 'Кусачки KING TONY 6921-06A с полированными рабочими головками для резки пластика.',
                    'short_description_ro' => 'Clește KING TONY 6921-06A cu capete lustruite, destinat tăierii plasticului.',
                    'description_ru' => 'Кусачки KING TONY 6921-06A предназначены для резки пластика и имеют полированные рабочие головки. Для сохранения ресурса инструмента не следует использовать их для резки стальной проволоки.',
                    'description_ro' => 'Cleștele KING TONY 6921-06A este destinat tăierii plasticului și are capete de lucru lustruite. Pentru a păstra durata de utilizare, nu trebuie folosit la tăierea sârmei de oțel.',
                ],
                '8730' => [
                    'name_ru' => 'Держатель торцевой головки для рейки KING TONY 8730, 3/8 дюйма',
                    'name_ro' => 'Clemă pentru șină de tubulare KING TONY 8730, 3/8 inch',
                    'short_description_ru' => 'Держатель KING TONY 8730 для крепления головки 3/8 дюйма на рейке.',
                    'short_description_ro' => 'Clemă KING TONY 8730 pentru fixarea unei tubulare de 3/8 inch pe șină.',
                    'description_ru' => 'Держатель KING TONY 8730 предназначен для крепления торцевой головки с приводом 3/8 дюйма на совместимой рейке для головок.',
                    'description_ro' => 'Clema KING TONY 8730 este destinată fixării unei tubulare cu antrenare de 3/8 inch pe o șină compatibilă pentru tubulare.',
                ],
                '8740' => [
                    'name_ru' => 'Держатель торцевой головки для рейки KING TONY 8740, 1/2 дюйма',
                    'name_ro' => 'Clemă pentru șină de tubulare KING TONY 8740, 1/2 inch',
                    'short_description_ru' => 'Держатель KING TONY 8740 для крепления головки 1/2 дюйма на рейке.',
                    'short_description_ro' => 'Clemă KING TONY 8740 pentru fixarea unei tubulare de 1/2 inch pe șină.',
                    'description_ru' => 'Держатель KING TONY 8740 предназначен для крепления торцевой головки с приводом 1/2 дюйма на рейке длиной от 160 до 560 мм.',
                    'description_ro' => 'Clema KING TONY 8740 este destinată fixării unei tubulare cu antrenare de 1/2 inch pe o șină cu lungimea de la 160 la 560 mm.',
                ],
                '87432-31' => [
                    'name_ru' => 'Комплект разделителей для ящика KING TONY 87432-31',
                    'name_ro' => 'Set de separatoare pentru sertar KING TONY 87432-31',
                    'short_description_ru' => 'Комплект разделителей KING TONY 87432-31 для организации пространства ящика.',
                    'short_description_ro' => 'Set de separatoare KING TONY 87432-31 pentru organizarea sertarului.',
                    'description_ru' => 'Комплект KING TONY 87432-31 предназначен для организации пространства инструментального ящика. Включает основание, два длинных и два коротких разделителя.',
                    'description_ro' => 'Setul KING TONY 87432-31 este destinat organizării unui sertar pentru scule. Include o bază, două separatoare lungi și două separatoare scurte.',
                ],
                '87432-31-B' => [
                    'name_ru' => 'Комплект разделителей для ящика KING TONY 87432-31-B',
                    'name_ro' => 'Set de separatoare pentru sertar KING TONY 87432-31-B',
                    'short_description_ru' => 'Комплект разделителей KING TONY 87432-31-B для организации пространства ящика.',
                    'short_description_ro' => 'Set de separatoare KING TONY 87432-31-B pentru organizarea sertarului.',
                    'description_ru' => 'Комплект KING TONY 87432-31-B предназначен для организации пространства инструментального ящика. Включает основание, два длинных и два коротких разделителя.',
                    'description_ro' => 'Setul KING TONY 87432-31-B este destinat organizării unui sertar pentru scule. Include o bază, două separatoare lungi și două separatoare scurte.',
                ],
            ];

            foreach ($verifiedContent as $sku => $content) {
                $setBilingualContent($sku, $content);
            }

            $roTitles = [
                '9AE6-10815' => 'Extractor pentru filtru de ulei KING TONY 9AE6-10815, 108 mm, 15 muchii, pentru Volvo',
                '9AE6-7476' => 'Extractor pentru filtru de ulei KING TONY 9AE6-7476, 74–76 mm, 15 muchii',
                '9AE6-7815' => 'Extractor pentru filtru de ulei KING TONY 9AE6-7815, 78 mm, 15 muchii',
                '9AE6-9618' => 'Extractor pentru filtru de ulei KING TONY 9AE6-9618, 96 mm, 18 muchii',
                'DG-585502' => 'Polizor unghiular cu acumulator M7 DG-585502, 18 V, două acumulatoare de 5 Ah',
                'DHG-101A' => 'Pistol cu aer cald cu acumulator M7 DHG-101A, 18 V, două acumulatoare de 5 Ah',
                'DRH-101A' => 'Ciocan rotopercutor cu acumulator M7 DRH-101A, 18 V, două acumulatoare de 5 Ah',
                'DRS-102A' => 'Ferăstrău sabie cu acumulator M7 DRS-102A, 18 V, două acumulatoare de 5 Ah',
                'JTC-1021' => 'Extractor pentru filtru de ulei JTC-1021, 67 mm, 14 muchii',
                'JTC-1114' => 'Extractor pentru filtru de ulei JTC-1114, 65 mm, 14 muchii',
                'JTC-1235' => 'Extractor pentru filtru de ulei JTC-1235, 74 mm, 14 muchii',
                'JTC-1403' => 'Extractor pentru filtru de ulei JTC-1403, 79 mm, 15 muchii',
                'JTC-1515' => 'Extractor pentru filtru de ulei JTC-1515, 82 mm, 15 muchii',
                'JTC-1521' => 'Extractor pentru filtru de ulei JTC-1521, 74 mm, 15 muchii',
                'JTC-1522' => 'Extractor pentru filtru de ulei JTC-1522, 87 mm, 16 muchii',
                'JTC-4104' => 'Extractor pentru filtru de ulei JTC-4104, 92 mm, 15 muchii',
                'JTC-4140' => 'Cap tubular pentru reglarea arborelui cu came JTC-4140, Audi A6/Q5 TFSI 4V 2,0 l, T10352/1',
                'JTC-4160A' => 'Extractor pentru filtru de ulei JTC-4160A, 88,8 mm, 15 muchii',
                'JTC-4497' => 'Set universal pentru înlocuirea ambreiajului autoreglabil JTC-4497',
                'JTC-4611' => 'Extractor pentru filtru de ulei JTC-4611, 75 mm, 15 muchii',
                'JTC-4667' => 'Extractor pentru filtru de ulei JTC-4667, 72,5 mm, 14 muchii',
                'JTC-4695' => 'Extractor pentru filtru de ulei JTC-4695, 84 mm, 14 muchii, pentru MB OM642 CDL',
                'JTC-4753' => 'Extractor pentru articulații sferice JTC-4753, deschidere 39 mm, adâncime 95 mm',
                'JTC-4859A' => 'Extractor din aluminiu pentru filtru de ulei JTC-4859A, 64,5 mm, 14 muchii',
                'JTC-4862' => 'Set pentru demontarea pompei de apă JTC-4862, pentru VW T5 și Touareg 2,5D',
                'JTC-4904A' => 'Extractor pentru filtru de ulei JTC-4904A, 64,5 mm, 14 muchii',
                'JTC-5243' => 'Extractor pentru articulații sferice JTC-5243, deschidere 39 mm, adâncime 60 mm',
                'JTC-5267' => 'Cap tubular pentru butucul punții spate JTC-5267, 6 muchii, 145 mm, lungime 160 mm, pentru MAN TGA',
                'JTC-5325' => 'Pistol de curățare sub presiune JTC-5325, 1 l, 180 l/min, TORNADOR',
                'JTC-6740' => 'Extractor pentru filtru de ulei JTC-6740, 93 mm, 45 muchii',
                'JTC-6761' => 'Extractor pentru filtru de ulei JTC-6761, 66,5 mm, 14 muchii',
                'JTC-6762' => 'Extractor pentru filtru de ulei JTC-6762, 72,55 mm, 14 muchii',
                'JTC-6763' => 'Extractor pentru filtru de ulei JTC-6763, 73,7 mm, 14 muchii, pentru Toyota Prado',
                'JTC-6813' => 'Extractor pentru filtru de ulei JTC-6813, 86,1 mm, 16 muchii, pentru Volvo din 2015',
                'QB-0808M' => 'Mașină pneumatică pentru sistemul MBX M7 QB-0808M, cu set de accesorii',
                'QB-9316' => 'Talpă de schimb M7 QB-9316, 6 găuri, 152 mm, prindere cu scai',
                'QB-9327' => 'Talpă de schimb M7 QB-9327, 15 găuri, 152 mm, prindere cu scai',
                'SC-221C' => 'Ciocan pneumatic M7 SC-221C, 175 mm, cu 5 dălți',
                'SC-222C' => 'Ciocan pneumatic M7 SC-222C, 225 mm, cu 5 dălți',
                'SC-415' => 'Set de 5 dălți rotunde M7 SC-415, 175 mm, pentru SC-211 și SC-212',
                'SC-425' => 'Set de 5 dălți cu tijă hexagonală M7 SC-425, 175 mm, pentru SC-221 și SC-222',
                'SX-2101' => 'Pistol pneumatic de curățare sub presiune M7 SX-2101, 1 l, 5500 rot/min, TORNADOR',
                'DB-1850P' => 'Acumulator LiHD M7 DB-1850P, 18 V, 5 Ah',
            ];

            foreach ($roTitles as $sku => $title) {
                $replaceTitle($sku, 'ro', $title);
            }

            $ruTitles = [
                '934-010MRV-B' => 'Синяя инструментальная тележка KING TONY 934-010MRV-B, 7 ящиков, 286 предметов',
                '9TP11' => 'Защитная накидка на крыло KING TONY 9TP11, 1050 × 650 мм, синяя, магнитное крепление',
                '9TP22' => 'Защитная накидка на крыло KING TONY 9TP22, 1050 × 650 мм, синяя, магниты и присоски',
                'QC-512' => 'Мини-фрезер M7 QC-512 для обработки металлической кромки',
                'DB-1850P' => 'Аккумулятор LiHD M7 DB-1850P, 18 В, 5 А·ч',
            ];

            foreach ($ruTitles as $sku => $title) {
                $replaceTitle($sku, 'ru', $title);
            }

            foreach (['name_ro', 'short_description_ro', 'description_ro'] as $column) {
                DB::table('products')
                    ->where($column, 'like', '%JTC JTC-%')
                    ->update([
                        $column => DB::raw("REPLACE({$column}, 'JTC JTC-', 'JTC-')"),
                        'updated_at' => $now,
                    ]);
            }

            $catalog = require lang_path('ru/catalog.php');
            $categoryNames = $catalog['categories'] ?? [];
            foreach ($categoryNames as $slug => $name) {
                if (! is_string($name) || preg_match('/\p{Cyrillic}/u', $name) !== 1) {
                    continue;
                }

                DB::table('categories')
                    ->where('slug', $slug)
                    ->whereColumn('name', 'name_ro')
                    ->update(['name' => $name, 'updated_at' => $now]);
            }
        });
    }

    public function down(): void
    {
        // Verified catalog corrections are intentionally not reverted.
    }
};
