<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'verified_misclassified_tools_repair_2026_07_21';

    public function up(): void
    {
        $categories = DB::table('categories')->whereIn('slug', [
            'biti-insertii-adaptoare',
            'scule-pentru-roti-vulcanizare',
            'chei-si-surubelnite',
            'surubelnite-si-biti',
            'tubulare-si-clichete',
            'scule-pentru-motor',
            'taiere-pilire-prelucrare',
        ])->pluck('id', 'slug');

        $records = $this->verifiedRecords();
        $products = DB::table('products')->whereIn('sku', array_keys($records))->get()->keyBy('sku');

        DB::transaction(function () use ($records, $products, $categories): void {
            foreach ($records as $sku => $content) {
                $product = $products->get($sku);
                $categoryId = $categories[$content['category']] ?? null;
                if (! $product || ! $categoryId) {
                    continue;
                }

                $this->updateProduct($product, $content, (int) $categoryId);
            }
        });
    }

    private function verifiedRecords(): array
    {
        $records = [
            '9BM2-01' => [
                'category' => 'scule-pentru-roti-vulcanizare',
                'name_ru' => 'Зажим для подкачки шин KING TONY 9BM2-01, наружная резьба 1/4 дюйма, для 9BM',
                'name_ro' => 'Clemă pentru umflarea anvelopelor KING TONY 9BM2-01, filet exterior 1/4 inch, pentru 9BM',
                'description_ru' => 'Зажим KING TONY 9BM2-01 предназначен для подкачки шин, имеет наружную резьбу 1/4 дюйма и совместим с оборудованием серии 9BM.',
                'description_ro' => 'Clema KING TONY 9BM2-01 este destinată umflării anvelopelor, are filet exterior de 1/4 inch și este compatibilă cu echipamentele din seria 9BM.',
                'attributes' => ['Тип' => 'Зажим для подкачки шин', 'Резьба' => '1/4 inch, exterior', 'Совместимость' => '9BM'],
            ],
            '3614-08R' => [
                'category' => 'chei-si-surubelnite',
                'name_ru' => 'Разводной ключ с двусторонними губками KING TONY 3614-08R',
                'name_ro' => 'Cheie reglabilă cu fălci reversibile KING TONY 3614-08R',
                'description_ru' => 'Разводной ключ KING TONY 3614-08R изготовлен из хром-ванадиевой стали. Двусторонние губки позволяют работать как с обычным крепежом, так и с деталями, требующими усиленного захвата; рукоятка выполнена из TPR.',
                'description_ro' => 'Cheia reglabilă KING TONY 3614-08R este fabricată din oțel crom-vanadiu. Fălcile reversibile permit lucrul atât cu elemente de fixare obișnuite, cât și cu piese care necesită o prindere puternică; mânerul este realizat din TPR.',
                'attributes' => ['Тип' => 'Разводной ключ с двусторонними губками', 'Материал' => 'Хром-ванадиевая сталь', 'Конструкция губок' => 'Двусторонние губки', 'Материал рукоятки' => 'TPR'],
            ],
            '32808MR' => [
                'category' => 'surubelnite-si-biti',
                'name_ru' => 'Набор телескопических отвёрток с трещоткой KING TONY 32808MR, 8 предметов, 45 зубцов',
                'name_ro' => 'Set de șurubelnițe telescopice cu clichet KING TONY 32808MR, 8 piese, 45 dinți',
                'description_ru' => 'KING TONY 32808MR — набор из восьми телескопических отвёрток с храповым механизмом на 45 зубцов.',
                'description_ro' => 'KING TONY 32808MR este un set de opt șurubelnițe telescopice cu mecanism cu clichet de 45 de dinți.',
                'attributes' => ['Тип' => 'Набор телескопических отвёрток с трещоткой', 'Количество предметов' => '8', 'Количество зубцов' => '45', 'Механизм' => 'Храповый'],
            ],
            '4506MR' => [
                'category' => 'chei-si-surubelnite',
                'name_ru' => 'Набор Т-образных баллонных ключей KING TONY 4506MR, 6 предметов, привод 1/2 дюйма',
                'name_ro' => 'Set de chei pentru roți cu mâner în T KING TONY 4506MR, 6 piese, antrenare 1/2 inch',
                'description_ru' => 'KING TONY 4506MR — набор из шести Т-образных баллонных ключей с приводом 1/2 дюйма.',
                'description_ro' => 'KING TONY 4506MR este un set de șase chei pentru roți cu mâner în T și antrenare de 1/2 inch.',
                'attributes' => ['Тип' => 'Набор Т-образных баллонных ключей', 'Количество предметов' => '6', 'Посадочный квадрат' => '1/2 inch'],
            ],
            '3631-10R' => [
                'category' => 'chei-si-surubelnite',
                'name_ru' => 'Разводной ключ с трещоткой KING TONY 3631-10R',
                'name_ro' => 'Cheie reglabilă cu clichet KING TONY 3631-10R',
                'description_ru' => 'Разводной ключ KING TONY 3631-10R оснащён храповым механизмом для работы с резьбовым крепежом.',
                'description_ro' => 'Cheia reglabilă KING TONY 3631-10R este prevăzută cu mecanism cu clichet pentru lucrul cu elemente de fixare filetate.',
                'attributes' => ['Тип' => 'Разводной ключ с трещоткой', 'Механизм' => 'Храповый'],
            ],
            '3616-10' => [
                'category' => 'chei-si-surubelnite',
                'name_ru' => 'Двусторонний саморегулирующийся ключ KING TONY 3616-10',
                'name_ro' => 'Cheie auto-reglabilă cu două fețe KING TONY 3616-10',
                'description_ru' => 'KING TONY 3616-10 — двусторонний ключ с саморегулирующимся механизмом захвата.',
                'description_ro' => 'KING TONY 3616-10 este o cheie cu două fețe și mecanism de prindere auto-reglabil.',
                'attributes' => ['Тип' => 'Двусторонний саморегулирующийся ключ', 'Механизм' => 'Саморегулирующийся', 'Исполнение' => 'Двусторонний'],
            ],
            '8816' => [
                'category' => 'tubulare-si-clichete',
                'name_ru' => 'Переходной адаптер KING TONY 8816, вход 1 дюйм (F), выход 3/4 дюйма (M)',
                'name_ro' => 'Adaptor de trecere KING TONY 8816, intrare 1 inch (F), ieșire 3/4 inch (M)',
                'description_ru' => 'Переходной адаптер KING TONY 8816 соединяет входной квадрат 1 дюйм с выходным квадратом 3/4 дюйма.',
                'description_ro' => 'Adaptorul de trecere KING TONY 8816 conectează pătratul de intrare de 1 inch la pătratul de ieșire de 3/4 inch.',
                'attributes' => ['Тип' => 'Переходной адаптер', 'Входной квадрат' => '1 inch (F)', 'Выходной квадрат' => '3/4 inch (M)'],
            ],
            '9DP2301P01' => [
                'category' => 'scule-pentru-motor',
                'name_ru' => 'Комплект для измерения компрессии дизельного двигателя KING TONY 9DP2301P01, 11 предметов',
                'name_ro' => 'Set pentru măsurarea compresiei motorului diesel KING TONY 9DP2301P01, 11 piese',
                'description_ru' => 'KING TONY 9DP2301P01 — комплект из 11 предметов для измерения компрессии дизельного двигателя.',
                'description_ro' => 'KING TONY 9DP2301P01 este un set de 11 piese pentru măsurarea compresiei motorului diesel.',
                'attributes' => ['Тип' => 'Комплект для измерения компрессии дизельного двигателя', 'Количество предметов' => '11', 'Применение' => 'Измерение компрессии дизельного двигателя'],
            ],
            '7977-07' => [
                'category' => 'taiere-pilire-prelucrare',
                'name_ru' => 'Универсальный резак KING TONY 7977-07, 7 дюймов',
                'name_ro' => 'Cutter universal KING TONY 7977-07, 7 inch',
                'description_ru' => 'Универсальный резак KING TONY 7977-07 имеет длину 7 дюймов и предназначен для ручных режущих работ.',
                'description_ro' => 'Cutterul universal KING TONY 7977-07 are lungimea de 7 inch și este destinat lucrărilor manuale de tăiere.',
                'attributes' => ['Тип' => 'Универсальный резак', 'Длина' => '7 inch'],
            ],
        ];

        foreach ([8, 10, 12, 13, 14, 17, 19] as $size) {
            $sku = '1185'.str_pad((string) $size, 2, '0', STR_PAD_LEFT).'M';
            $records[$sku] = [
                'category' => 'chei-si-surubelnite',
                'name_ru' => "Т-образный торцевой ключ KING TONY {$sku}, {$size} мм",
                'name_ro' => "Cheie tubulară în T KING TONY {$sku}, {$size} mm",
                'description_ru' => "Т-образный торцевой ключ KING TONY {$sku} имеет рабочий размер {$size} мм.",
                'description_ro' => "Cheia tubulară în T KING TONY {$sku} are dimensiunea de lucru de {$size} mm.",
                'attributes' => ['Тип' => 'Т-образный торцевой ключ', 'Размер' => $size.' mm'],
            ];
        }

        foreach ([[24, 27], [27, 32], [30, 32], [32, 33]] as [$first, $second]) {
            $sku = '1999'.$first.$second;
            $sizes = $first.' × '.$second;
            $records[$sku] = [
                'category' => 'chei-si-surubelnite',
                'name_ru' => "Двусторонний грузовой ключ KING TONY {$sku}, {$sizes} мм",
                'name_ro' => "Cheie tubulară dublă pentru camion KING TONY {$sku}, {$sizes} mm",
                'description_ru' => "Двусторонний грузовой ключ KING TONY {$sku} имеет рабочие размеры {$sizes} мм.",
                'description_ro' => "Cheia tubulară dublă pentru camion KING TONY {$sku} are dimensiunile de lucru {$sizes} mm.",
                'attributes' => ['Тип' => 'Двусторонний грузовой ключ', 'Размер' => $sizes.' mm', 'Исполнение' => 'Двусторонний'],
            ];
        }

        $records['199920R'] = [
            'category' => 'chei-si-surubelnite',
            'name_ru' => 'Рычаг для грузового ключа KING TONY 199920R, диаметр 19 мм',
            'name_ro' => 'Bară pentru cheie de camion KING TONY 199920R, diametru 19 mm',
            'description_ru' => 'Рычаг KING TONY 199920R диаметром 19 мм предназначен для совместимого двустороннего грузового ключа.',
            'description_ro' => 'Bara KING TONY 199920R, cu diametrul de 19 mm, este destinată unei chei tubulare duble compatibile pentru camion.',
            'attributes' => ['Тип' => 'Рычаг для грузового ключа', 'Диаметр' => '19 mm'],
        ];

        foreach ([1, 2, 3, 4] as $size) {
            $sku = '20310'.$size;
            $records[$sku] = [
                'category' => 'biti-insertii-adaptoare',
                'name_ru' => "Торцевая насадка-бита KING TONY {$sku}, PH{$size}, привод 1/4 дюйма, 37 мм",
                'name_ro' => "Cap tubular cu bit KING TONY {$sku}, PH{$size}, antrenare 1/4 inch, 37 mm",
                'description_ru' => "Торцевая насадка-бита KING TONY {$sku} имеет крестообразный профиль Phillips PH{$size}, привод 1/4 дюйма и длину 37 мм.",
                'description_ro' => "Capul tubular cu bit KING TONY {$sku} are profil cruciform Phillips PH{$size}, antrenare de 1/4 inch și lungime de 37 mm.",
                'attributes' => ['Тип' => 'Торцевая насадка-бита', 'Рабочий профиль' => 'Phillips', 'Размер' => 'PH'.$size, 'Посадочный квадрат' => '1/4 inch', 'Длина' => '37 mm'],
            ];
        }

        return $records;
    }

    private function updateProduct(object $product, array $content, int $categoryId): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        DB::table('products')->where('id', $product->id)->update([
            'category_id' => $categoryId,
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
            'needs_category_review' => false,
            'needs_content_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ]);

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

        if ((int) $product->category_id !== $categoryId) {
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
                'evidence' => json_encode(['Product type is explicit in the product name or stored official KING TONY source.']),
                'alternatives' => json_encode([]),
                'validation_errors' => json_encode([]),
                'applied_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! $product->source_parser_item_id) {
            return;
        }

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
            'category_id' => $categoryId,
            'detected_category_id' => $categoryId,
            'detected_category_path' => $content['category'],
            'category_confidence_score' => 100,
            'category_detection_method' => $this->mode,
            'needs_category_review' => false,
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
        // Curated SKU-specific content and category corrections are intentionally retained.
    }
};
