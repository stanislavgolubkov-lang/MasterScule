<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'verified_king_tony_misc_repair_2026_07_21';

    public function up(): void
    {
        $records = [
            'P2746' => [
                'category' => 'surubelnite-si-biti',
                'name_ru' => 'Набор отвёрток с храповым механизмом KING TONY P2746, 7 предметов, 1/4 дюйма',
                'name_ro' => 'Set de șurubelnițe cu clichet KING TONY P2746, 7 piese, 1/4 inch',
                'description_ru' => 'KING TONY P2746 — набор из 7 предметов с компактной храповой рукояткой под оснастку 1/4 дюйма.',
                'description_ro' => 'KING TONY P2746 este un set de 7 piese cu mâner compact cu clichet pentru accesorii de 1/4 inch.',
                'attributes' => ['Тип' => 'Набор отвёрток с храповым механизмом', 'Количество предметов' => '7', 'Посадочное место' => '1/4 inch', 'Механизм' => 'Храповый'],
            ],
            '92543MR' => [
                'category' => 'seturi-de-scule',
                'name_ru' => 'Набор инструментов механика KING TONY 92543MR, 43 предмета, в сумке',
                'name_ro' => 'Set de scule pentru mecanic KING TONY 92543MR, 43 piese, în geantă',
                'description_ru' => 'KING TONY 92543MR — комплект из 43 инструментов механика, поставляемый в переносной инструментальной сумке.',
                'description_ro' => 'KING TONY 92543MR este un set de 43 de scule pentru mecanic, livrat într-o geantă portabilă.',
                'attributes' => ['Тип' => 'Набор инструментов механика', 'Количество предметов' => '43', 'Комплектация' => 'Инструментальная сумка'],
            ],
            '2176DF' => $this->handle('Рукоятка-вороток', 'Mâner de antrenare', '2176DF'),
            '2177DF' => $this->handle('Рукоятка-вороток', 'Mâner de antrenare', '2177DF'),
            '2178DF' => $this->handle('Рукоятка привода', 'Mâner de antrenare', '2178DF', true),
            '2178DFUS' => $this->handle('Рукоятка привода', 'Mâner de antrenare', '2178DFUS', true),
            '2179DF' => $this->handle('Рукоятка привода', 'Mâner de antrenare', '2179DF', true),
            '30115MR' => [
                'category' => 'surubelnite-si-biti',
                'name_ru' => 'Стандартный набор отвёрток KING TONY 30115MR, 5 предметов',
                'name_ro' => 'Set standard de șurubelnițe KING TONY 30115MR, 5 piese',
                'description_ru' => 'KING TONY 30115MR — стандартный набор из пяти отвёрток для монтажных и ремонтных работ.',
                'description_ro' => 'KING TONY 30115MR este un set standard de cinci șurubelnițe pentru lucrări de montaj și reparații.',
                'attributes' => ['Тип' => 'Набор отвёрток', 'Количество предметов' => '5'],
            ],
            '32518MR02' => [
                'category' => 'surubelnite-si-biti',
                'name_ru' => 'Набор отвёрток со сменными стержнями KING TONY 32518MR02, 11 предметов',
                'name_ro' => 'Set de șurubelnițe cu tije interschimbabile KING TONY 32518MR02, 11 piese',
                'description_ru' => 'KING TONY 32518MR02 — набор из 11 предметов с рукояткой и сменными отвёрточными стержнями.',
                'description_ro' => 'KING TONY 32518MR02 este un set de 11 piese cu mâner și tije de șurubelniță interschimbabile.',
                'attributes' => ['Тип' => 'Набор отвёрток', 'Количество предметов' => '11', 'Механизм' => 'Сменные стержни'],
            ],
            '75RF09M' => [
                'category' => 'tarozi-filiere-filetare',
                'name_ru' => 'Напильник для восстановления резьбы KING TONY 75RF09M',
                'name_ro' => 'Pilă pentru repararea filetului KING TONY 75RF09M',
                'description_ru' => 'KING TONY 75RF09M предназначен для правки и восстановления повреждённых наружных резьбовых витков.',
                'description_ro' => 'KING TONY 75RF09M este destinat corectării și reparării spirelor deteriorate ale filetelor exterioare.',
                'attributes' => ['Тип' => 'Напильник для восстановления резьбы', 'Применение' => 'Восстановление наружной резьбы'],
            ],
            '37221-030' => $this->airRatchet('37221-030'),
            '37235-030' => $this->airRatchet('37235-030'),
            '9TD034MR' => $this->specialSocketSet('9TD034MR', 'Набор головок для снятия повреждённых гаек', 'Set de capete pentru piulițe deteriorate'),
            '9TD014MR' => $this->specialSocketSet('9TD014MR', 'Набор экстракторов роликовых шпилек', 'Set de extractoare pentru știfturi'),
            '11509SQ05' => [
                'category' => 'taiere-pilire-prelucrare',
                'name_ru' => 'Набор усиленных цифровых клейм KING TONY 11509SQ05',
                'name_ro' => 'Set ranforsat de poansoane cu cifre KING TONY 11509SQ05',
                'description_ru' => 'KING TONY 11509SQ05 — набор усиленных клейм для нанесения цифровой маркировки на заготовки.',
                'description_ro' => 'KING TONY 11509SQ05 este un set ranforsat de poansoane pentru marcarea numerică a pieselor.',
                'attributes' => ['Тип' => 'Набор цифровых клейм', 'Применение' => 'Цифровая маркировка'],
            ],
            '87A05' => [
                'category' => 'accesorii-universale',
                'name_ru' => 'Складной переносной ящик для инструментов KING TONY 87A05, 3 секции',
                'name_ro' => 'Cutie pliabilă portabilă pentru scule KING TONY 87A05, 3 secțiuni',
                'description_ru' => 'KING TONY 87A05 — переносной складной ящик для хранения инструмента с тремя секциями.',
                'description_ro' => 'KING TONY 87A05 este o cutie portabilă pliabilă pentru depozitarea sculelor, cu trei secțiuni.',
                'attributes' => ['Тип' => 'Складной ящик для инструментов', 'Количество секций' => '3'],
            ],
        ];

        $categories = DB::table('categories')->whereIn('slug', collect($records)->pluck('category')->unique())->pluck('id', 'slug');

        DB::transaction(function () use ($records, $categories): void {
            foreach ($records as $sku => $data) {
                $product = DB::table('products')->where('sku', $sku)->first();
                $targetCategoryId = $categories[$data['category']] ?? null;
                if (! $product || ! $targetCategoryId) {
                    continue;
                }

                $now = now();
                DB::table('products')->where('id', $product->id)->update([
                    'category_id' => $targetCategoryId,
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
                    'needs_category_review' => false,
                    'needs_content_review' => false,
                    'generated_content' => false,
                    'updated_at' => $now,
                ]);
                DB::table('category_product')->where('product_id', $product->id)->delete();
                DB::table('category_product')->insert([
                    'product_id' => $product->id,
                    'category_id' => $targetCategoryId,
                    'is_primary' => true,
                    'source' => $this->mode,
                    'confidence' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ((int) $product->category_id !== (int) $targetCategoryId) {
                    DB::table('product_category_decisions')->insert([
                        'product_id' => $product->id,
                        'previous_category_id' => $product->category_id,
                        'selected_category_id' => $targetCategoryId,
                        'taxonomy_version' => 'verified-2026-07-21',
                        'input_hash' => hash('sha256', $this->mode.'|'.$sku.'|'.$product->category_id.'|'.$targetCategoryId),
                        'mode' => $this->mode,
                        'status' => 'applied',
                        'classifier_confidence' => 1,
                        'verifier_confidence' => 1,
                        'evidence' => json_encode(['Exact SKU product type confirmed by the stored KING TONY product page.'], JSON_UNESCAPED_UNICODE),
                        'alternatives' => json_encode([]),
                        'validation_errors' => json_encode([]),
                        'applied_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                if ($product->source_parser_item_id) {
                    DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                        'category_id' => $targetCategoryId,
                        'detected_category_id' => $targetCategoryId,
                        'detected_category_path' => $data['category'],
                        'category_confidence_score' => 100,
                        'category_detection_method' => $this->mode,
                        'needs_category_review' => false,
                        'name_ru' => $data['name_ru'],
                        'name_ro' => $data['name_ro'],
                        'short_description_ru' => $data['description_ru'],
                        'short_description_ro' => $data['description_ro'],
                        'description_ru' => $data['description_ru'],
                        'description_ro' => $data['description_ro'],
                        'found_title' => $data['name_ru'],
                        'found_description' => $data['description_ru'],
                        'found_specs_json' => json_encode($data['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'needs_content_review' => false,
                        'generated_content' => false,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    private function handle(string $typeRu, string $typeRo, string $sku, bool $composite = false): array
    {
        $attributes = ['Тип' => $typeRu, 'Посадочный квадрат' => '1/4 inch'];
        if ($composite) {
            $attributes['Материал рукоятки'] = 'PP + TPR';
        }

        return [
            'category' => 'tubulare-si-clichete',
            'name_ru' => "{$typeRu} KING TONY {$sku}, привод 1/4 дюйма".($composite ? ', PP + TPR' : ''),
            'name_ro' => "{$typeRo} KING TONY {$sku}, antrenare 1/4 inch".($composite ? ', PP + TPR' : ''),
            'description_ru' => "KING TONY {$sku} — ручная рукоятка с посадочным квадратом 1/4 дюйма для работы с совместимыми торцевыми головками.".($composite ? ' Рукоятка выполнена из PP и TPR.' : ''),
            'description_ro' => "KING TONY {$sku} este un mâner manual cu pătrat de antrenare de 1/4 inch pentru capete tubulare compatibile.".($composite ? ' Mânerul este realizat din PP și TPR.' : ''),
            'attributes' => $attributes,
        ];
    }

    private function airRatchet(string $sku): array
    {
        return [
            'category' => 'clichete-pneumatice',
            'name_ru' => "Пневматическая трещотка KING TONY {$sku}, привод 1/4 дюйма",
            'name_ro' => "Clichet pneumatic KING TONY {$sku}, antrenare 1/4 inch",
            'description_ru' => "KING TONY {$sku} — пневматическая трещотка с посадочным квадратом 1/4 дюйма для резьбового крепежа.",
            'description_ro' => "KING TONY {$sku} este un clichet pneumatic cu pătrat de antrenare de 1/4 inch pentru elemente de fixare filetate.",
            'attributes' => ['Тип' => 'Пневматическая трещотка', 'Посадочный квадрат' => '1/4 inch'],
        ];
    }

    private function specialSocketSet(string $sku, string $typeRu, string $typeRo): array
    {
        return [
            'category' => 'extractoare-si-prese',
            'name_ru' => "{$typeRu} KING TONY {$sku}, 4 предмета, привод 1/2 дюйма",
            'name_ro' => "{$typeRo} KING TONY {$sku}, 4 piese, antrenare 1/2 inch",
            'description_ru' => "KING TONY {$sku} — специальный набор из четырёх головок с приводом 1/2 дюйма.",
            'description_ro' => "KING TONY {$sku} este un set special de patru capete cu antrenare de 1/2 inch.",
            'attributes' => ['Тип' => $typeRu, 'Количество предметов' => '4', 'Посадочный квадрат' => '1/2 inch'],
        ];
    }

    public function down(): void
    {
        // Curated content is not reverted to generic imported names and subgroup metadata.
    }
};
