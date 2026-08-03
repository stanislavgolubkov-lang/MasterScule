<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-gys-plasma-consumables-2026-08-03';

    public function up(): void
    {
        DB::transaction(function (): void {
            $records = $this->records();
            $products = DB::table('products')->whereIn('sku', array_keys($records))->get()->keyBy('sku');
            $categoryId = DB::table('categories')->where('slug', 'accesorii-si-consumabile')->value('id');

            foreach ($records as $sku => $content) {
                if ($product = $products->get($sku)) {
                    $this->updateProduct($product, $content, $categoryId);
                }
            }

            $this->repairPrimaryCategoryLink('024205');
        });
    }

    private function records(): array
    {
        $referenceUrl = 'https://lakkspesialisten.no/content/uploads/2026/01/GYS%20040236.pdf';

        return [
            '040168/1' => $this->record(
                'Плазменный электрод GYS 040168/1 для горелок TPT25/MT35K/TPT40, 1 шт.',
                'Electrod pentru plasmă GYS 040168/1 pentru pistoale TPT25/MT35K/TPT40, 1 buc.',
                'GYS 040168/1 — один сменный электрод для плазменных горелок TPT25, MT35K и TPT40, применяемых с совместимыми аппаратами CUTTER. Электрод участвует в создании плазменной дуги и заменяется при износе. Производитель поставляет артикул 040168 упаковкой из 10 штук; эта карточка относится к одной штуке.',
                'GYS 040168/1 este un electrod de schimb pentru pistoalele de plasmă TPT25, MT35K și TPT40 utilizate cu aparatele CUTTER compatibile. Electrodul participă la crearea arcului de plasmă și se înlocuiește când este uzat. Producătorul livrează codul 040168 în cutii de 10 bucăți; această fișă corespunde unei singure bucăți.',
                [
                    'Тип' => 'Плазменный электрод',
                    'Артикул производителя' => '040168',
                    'Единица продажи' => '1 шт.',
                    'Заводская упаковка' => '10 шт.',
                    'Совместимость' => 'TPT25 / MT35K / TPT40 / S25K / S35K / S45',
                    'Назначение' => 'Создание плазменной дуги',
                ],
                $referenceUrl,
            ),
            '040175/1' => $this->record(
                'Диффузор плазменной горелки GYS 040175/1 для TPT25/MT35K/TPT40, 1 шт.',
                'Difuzor pentru pistolet de plasmă GYS 040175/1 pentru TPT25/MT35K/TPT40, 1 buc.',
                'GYS 040175/1 — один сменный диффузор для плазменных горелок TPT25, MT35K и TPT40. Он распределяет воздушный поток вокруг электрода и режущего наконечника. Производитель поставляет артикул 040175 упаковкой из 2 штук; эта карточка относится к одной штуке.',
                'GYS 040175/1 este un difuzor de schimb pentru pistoalele de plasmă TPT25, MT35K și TPT40. Distribuie fluxul de aer în jurul electrodului și al duzei de tăiere. Producătorul livrează codul 040175 în cutii de 2 bucăți; această fișă corespunde unei singure bucăți.',
                [
                    'Тип' => 'Диффузор плазменной горелки',
                    'Артикул производителя' => '040175',
                    'Единица продажи' => '1 шт.',
                    'Заводская упаковка' => '2 шт.',
                    'Совместимость' => 'TPT25 / MT35K / TPT40 / S25K / S35K / S45',
                    'Назначение' => 'Распределение воздушного потока',
                ],
                $referenceUrl,
            ),
            '040212/1' => $this->record(
                'Режущий наконечник плазменной горелки GYS 040212/1, Ø 0,8 мм, 1 шт.',
                'Duză de tăiere pentru pistolet de plasmă GYS 040212/1, Ø 0,8 mm, 1 buc.',
                'GYS 040212/1 — один режущий наконечник с отверстием Ø 0,8 мм для плазменных горелок TPT25, MT35K и TPT40. Наконечник формирует воздушно-плазменную струю и подлежит замене, когда отверстие теряет правильную круглую форму. Производитель поставляет артикул 040212 упаковкой из 10 штук.',
                'GYS 040212/1 este o duză de tăiere cu orificiu de Ø 0,8 mm pentru pistoalele de plasmă TPT25, MT35K și TPT40. Duza formează jetul de aer-plasmă și trebuie înlocuită când orificiul nu mai este perfect rotund. Producătorul livrează codul 040212 în cutii de 10 bucăți.',
                [
                    'Тип' => 'Наконечник плазменной горелки',
                    'Артикул производителя' => '040212',
                    'Единица продажи' => '1 шт.',
                    'Заводская упаковка' => '10 шт.',
                    'Совместимость' => 'TPT25 / MT35K / TPT40 / CUTTER 31FV / CUTTER 40FV',
                    'Диаметр отверстия' => '0.8 mm',
                    'Назначение' => 'Формирование плазменной струи',
                ],
                $referenceUrl,
            ),
            '040236/1' => $this->record(
                'Наружное сопло плазменной горелки GYS 040236/1 для CUTTER 31FV/40FV, 1 шт.',
                'Duză exterioară pentru pistolet de plasmă GYS 040236/1 pentru CUTTER 31FV/40FV, 1 buc.',
                'GYS 040236/1 — одно наружное сопло плазменной горелки для аппаратов CUTTER 31FV и CUTTER 40FV. Это внешний сменный элемент горелки, устанавливаемый вместе с электродом, диффузором и режущим наконечником. Производитель поставляет артикул 040236 упаковкой из 4 штук; эта карточка относится к одной штуке.',
                'GYS 040236/1 este o duză exterioară pentru pistoletul de plasmă al aparatelor CUTTER 31FV și CUTTER 40FV. Este elementul exterior interschimbabil al pistoletului, montat împreună cu electrodul, difuzorul și duza de tăiere. Producătorul livrează codul 040236 în cutii de 4 bucăți; această fișă corespunde unei singure bucăți.',
                [
                    'Тип' => 'Сопло плазменной горелки',
                    'Артикул производителя' => '040236',
                    'Единица продажи' => '1 шт.',
                    'Заводская упаковка' => '4 шт.',
                    'Совместимость' => 'CUTTER 31FV / CUTTER 40FV',
                    'Назначение' => 'Наружный элемент плазменной горелки',
                ],
                $referenceUrl,
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
    ): array {
        return compact('nameRu', 'nameRo', 'descriptionRu', 'descriptionRo', 'attributes', 'referenceUrl');
    }

    private function updateProduct(object $product, array $content, ?int $categoryId): void
    {
        $now = now();
        $sourceDomain = parse_url($content['referenceUrl'], PHP_URL_HOST);
        $sourceType = 'exact_sku_manufacturer_datasheet_mirror';
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
            'category_id' => $categoryId,
            'parser_source_urls' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES),
            'source_url' => $content['referenceUrl'],
            'source_domain' => $sourceDomain,
            'source_type' => $sourceType,
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'needs_content_review' => false,
            'generated_content' => false,
            'meta_title' => $content['nameRu'].' | MasterScule.md',
            'meta_description' => mb_substr($content['descriptionRu'], 0, 250),
            'updated_at' => $now,
        ]);

        if ($categoryId) {
            $this->syncCategory($product, $categoryId, $now);
        }

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
            'official_source_url' => null,
            'official_source_domain' => null,
            'official_source_confidence' => null,
            'fallback_source_url' => null,
            'fallback_source_domain' => null,
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'needs_content_review' => false,
            'generated_content' => false,
            'content_source_type' => $sourceType,
            'translation_source_type' => 'curated_translation',
            'translation_reviewed_at' => $now,
            'category_id' => $categoryId,
            'detected_category_id' => $categoryId,
            'detected_category_path' => 'accesorii-si-consumabile',
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
                'snippet' => 'GYS plasma consumable publication matched by exact manufacturer reference.',
                'source_type' => $sourceType,
                'confidence_score' => 95,
                'raw_data_json' => json_encode([
                    'sku' => $product->sku,
                    'manufacturer_reference' => str_replace('/1', '', $product->sku),
                    'brand' => 'GYS',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function appendReferenceUrl(?string $json, string $referenceUrl): array
    {
        $urls = json_decode($json ?: '[]', true);
        $urls = is_array($urls) ? $urls : [];
        $urls[] = $referenceUrl;

        return array_values(array_unique(array_filter($urls, 'is_string')));
    }

    private function syncCategory(object $product, int $categoryId, object $now): void
    {
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

        if ((int) $product->category_id === $categoryId) {
            return;
        }

        DB::table('product_category_decisions')->insert([
            'product_id' => $product->id,
            'previous_category_id' => $product->category_id,
            'selected_category_id' => $categoryId,
            'taxonomy_version' => 'verified-2026-08-03',
            'input_hash' => hash('sha256', $this->mode.'|'.$product->sku.'|'.$product->category_id.'|'.$categoryId),
            'mode' => $this->mode,
            'status' => 'applied',
            'classifier_confidence' => 1,
            'verifier_confidence' => 1,
            'evidence' => json_encode(["Exact GYS SKU {$product->sku} is a plasma consumable."], JSON_UNESCAPED_UNICODE),
            'alternatives' => json_encode([]),
            'validation_errors' => json_encode([]),
            'applied_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function repairPrimaryCategoryLink(string $sku): void
    {
        $product = DB::table('products')->where('sku', $sku)->first(['id', 'category_id']);

        if (! $product?->category_id) {
            return;
        }

        $now = now();
        DB::table('category_product')->where('product_id', $product->id)->delete();
        DB::table('category_product')->insert([
            'product_id' => $product->id,
            'category_id' => $product->category_id,
            'is_primary' => true,
            'source' => $this->mode,
            'confidence' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Curated exact-SKU content is intentionally retained.
    }
};
