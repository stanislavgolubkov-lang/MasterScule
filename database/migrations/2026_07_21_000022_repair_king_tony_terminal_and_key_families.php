<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'verified_king_tony_terminal_key_repair_2026_07_21';

    public function up(): void
    {
        $categories = DB::table('categories')->whereIn('slug', [
            'clesti-electrician-si-cabluri',
            'biti-insertii-adaptoare',
        ])->pluck('id', 'slug');

        $records = [...$this->terminalReleaseRecords(), ...$this->keySetRecords()];
        $products = DB::table('products')->whereIn('sku', array_keys($records))->get()->keyBy('sku');

        DB::transaction(function () use ($categories, $records, $products): void {
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

    private function terminalReleaseRecords(): array
    {
        $variants = [
            '9DT11-1' => ['Круглый ABS', 'rotund ABS', '4,0'],
            '9DT11-2' => ['Круглый', 'rotund', '3,5'],
            '9DT11-3' => ['Круглый', 'rotund', '1,5'],
            '9DT11-4' => ['Maxi Power Timer', 'Maxi Power Timer', null],
            '9DT11-5' => ['Pit Pod / Faston', 'Pit Pod / Faston', '6,3'],
            '9DT11-6' => ['JPT / SPT', 'JPT / SPT', null],
            '9DT11-7' => ['Micro Timer II', 'Micro Timer II', null],
            '9DT11-8' => ['Secondary', 'Secondary', null],
            '9DT11-9' => ['Стандартный', 'standard', '2,72'],
            '9DT11-10' => ['Стандартный', 'standard', '1,48'],
            '9DT11-11' => ['Стандартный', 'standard', '1,89'],
            '9DT11-12' => ['Микростандартный', 'micro-standard', null],
        ];

        $records = [];
        foreach ($variants as $sku => [$variantRu, $variantRo, $size]) {
            $variantTextRu = match ($variantRu) {
                'Круглый ABS' => 'круглый ABS',
                'Круглый' => 'круглый',
                'Стандартный' => 'стандартный',
                'Микростандартный' => 'микростандартный',
                default => $variantRu,
            };
            $sizeRu = $size ? ", {$size} мм" : '';
            $sizeRo = $size ? ", {$size} mm" : '';
            $attributes = [
                'Тип' => 'Инструмент для извлечения автомобильных клемм',
                'Исполнение' => $variantRu,
                'Применение' => 'Автомобильные электрические разъёмы',
                'Совместимость' => 'Серия KING TONY 9DT11',
            ];
            if ($size) {
                $attributes['Размер'] = str_replace(',', '.', $size).' mm';
            }

            $records[$sku] = [
                'category' => 'clesti-electrician-si-cabluri',
                'name_ru' => "Съёмник автомобильных клемм KING TONY {$sku}, {$variantTextRu}{$sizeRu}",
                'name_ro' => "Extractor pentru terminale auto KING TONY {$sku}, {$variantRo}{$sizeRo}",
                'description_ru' => "Съёмник автомобильных клемм KING TONY {$sku}, исполнение {$variantTextRu}{$sizeRu}, является отдельным инструментом серии 9DT11. Предназначен для извлечения контактов из автомобильных электрических разъёмов.",
                'description_ro' => "Extractorul pentru terminale auto KING TONY {$sku}, execuție {$variantRo}{$sizeRo}, este o unealtă individuală din seria 9DT11. Este destinat extragerii contactelor din conectorii electrici auto.",
                'attributes' => $attributes,
                'needs_image_review' => true,
            ];
        }

        return $records;
    }

    private function keySetRecords(): array
    {
        return [
            '20109MR' => $this->hexSet(
                '20109MR',
                9,
                '1,5–10',
                true,
                true,
                false,
            ),
            '20208MR' => $this->hexSet(
                '20208MR',
                8,
                '2–10',
                false,
                true,
                false,
            ),
            '20209MR' => $this->hexSet(
                '20209MR',
                9,
                '1,5–10',
                false,
                true,
                false,
            ),
            '20209MRUS' => $this->hexSet(
                '20209MRUS',
                9,
                '1,5–10',
                false,
                true,
                true,
            ),
            '20219MRUS' => $this->hexSet(
                '20219MRUS',
                9,
                '1,5–10',
                false,
                false,
                true,
            ),
            '20308PR' => [
                'category' => 'biti-insertii-adaptoare',
                'name_ru' => 'Набор складных ключей TORX KING TONY 20308PR, T9–T40, 8 предметов',
                'name_ro' => 'Set de chei TORX pliabile KING TONY 20308PR, T9–T40, 8 piese',
                'description_ru' => 'Набор KING TONY 20308PR включает восемь складных ключей TORX размеров T9, T10, T15, T20, T25, T27, T30 и T40. Ключи изготовлены из легированной стали SNCM-V и имеют хромированное покрытие.',
                'description_ro' => 'Setul KING TONY 20308PR include opt chei TORX pliabile, cu dimensiunile T9, T10, T15, T20, T25, T27, T30 și T40. Cheile sunt fabricate din oțel aliat SNCM-V și au acoperire cromată.',
                'attributes' => [
                    'Тип' => 'Набор складных ключей TORX',
                    'Количество предметов' => '8',
                    'Рабочий профиль' => 'TORX',
                    'Размер' => 'T9, T10, T15, T20, T25, T27, T30, T40',
                    'Материал' => 'Легированная сталь SNCM-V',
                    'Покрытие' => 'Хромированное',
                    'Исполнение' => 'Складное',
                ],
                'needs_image_review' => false,
            ],
            '20419PR' => [
                'category' => 'biti-insertii-adaptoare',
                'name_ru' => 'Набор удлинённых ключей TORX с отверстием KING TONY 20419PR, T10–T50, 9 предметов',
                'name_ro' => 'Set de chei TORX lungi cu orificiu KING TONY 20419PR, T10–T50, 9 piese',
                'description_ru' => 'KING TONY 20419PR — набор из девяти экстрадлинных Г-образных ключей TORX с отверстием, диапазон размеров T10–T50. Ключи изготовлены из легированной стали SNCM-V и имеют хромированное покрытие.',
                'description_ro' => 'KING TONY 20419PR este un set de nouă chei TORX extra-lungi, în formă de L și cu orificiu, în intervalul T10–T50. Cheile sunt fabricate din oțel aliat SNCM-V și au acoperire cromată.',
                'attributes' => [
                    'Тип' => 'Набор удлинённых ключей TORX с отверстием',
                    'Количество предметов' => '9',
                    'Рабочий профиль' => 'TORX Tamper Resistant',
                    'Размер' => 'T10–T50',
                    'Материал' => 'Легированная сталь SNCM-V',
                    'Покрытие' => 'Хромированное',
                    'Исполнение' => 'Экстрадлинное, с отверстием',
                ],
                'needs_image_review' => false,
            ],
        ];
    }

    private function hexSet(
        string $sku,
        int $count,
        string $sizes,
        bool $ballEnd,
        bool $extraLong,
        bool $unison,
    ): array {
        $typeRu = $ballEnd
            ? 'Набор Г-образных шестигранных ключей с шаром'
            : 'Набор Г-образных шестигранных ключей';
        $typeRo = $ballEnd
            ? 'Set de chei hexagonale în L cu cap sferic'
            : 'Set de chei hexagonale în L';
        $executionRu = $extraLong ? 'Экстрадлинное' : 'Стандартное';
        $executionDescriptionRu = $extraLong ? 'экстрадлинных' : 'стандартных';
        $executionRo = $extraLong ? 'extra-lungă' : 'standard';
        $edition = $unison ? ' UNISON' : '';
        $ballRu = $ballEnd ? ' с шаровым наконечником' : '';
        $ballRo = $ballEnd ? ' cu cap sferic' : '';

        return [
            'category' => 'biti-insertii-adaptoare',
            'name_ru' => "{$typeRu} KING TONY {$sku}{$edition}, {$sizes} мм, {$count} предметов",
            'name_ro' => "{$typeRo} KING TONY {$sku}{$edition}, {$sizes} mm, {$count} piese",
            'description_ru' => "Набор KING TONY {$sku}{$edition} включает {$count} {$executionDescriptionRu} Г-образных шестигранных ключей{$ballRu} в диапазоне размеров {$sizes} мм. Ключи изготовлены из легированной стали SNCM-V и имеют хромированное покрытие.",
            'description_ro' => "Setul KING TONY {$sku}{$edition} include {$count} chei hexagonale în L, în execuție {$executionRo}{$ballRo}, cu dimensiuni de {$sizes} mm. Cheile sunt fabricate din oțel aliat SNCM-V și au acoperire cromată.",
            'attributes' => [
                'Тип' => $typeRu,
                'Количество предметов' => (string) $count,
                'Рабочий профиль' => 'HEX',
                'Размер' => str_replace(',', '.', $sizes).' mm',
                'Материал' => 'Легированная сталь SNCM-V',
                'Покрытие' => 'Хромированное',
                'Исполнение' => $executionRu,
            ],
            'needs_image_review' => false,
        ];
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
            'needs_image_review' => $content['needs_image_review'],
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
                'evidence' => json_encode(['Exact product type is present in the stored source and import row.']),
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
            'needs_image_review' => $content['needs_image_review'],
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
        // Curated KING TONY SKU-family content and category corrections are intentionally retained.
    }
};
