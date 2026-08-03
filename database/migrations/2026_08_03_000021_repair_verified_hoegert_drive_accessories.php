<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach ($this->records() as $sku => $content) {
                $product = DB::table('products')->where('sku', $sku)->first();
                if (! $product) {
                    continue;
                }

                $now = now();
                $sourceDomain = (string) parse_url($content['source_url'], PHP_URL_HOST);
                $shortRu = Str::limit($content['description_ru'], 240, '');
                $shortRo = Str::limit($content['description_ro'], 240, '');

                DB::table('products')->where('id', $product->id)->update([
                    'name' => $content['name_ru'],
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description' => $shortRu,
                    'short_description_ru' => $shortRu,
                    'short_description_ro' => $shortRo,
                    'description' => $content['description_ru'],
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'meta_description' => Str::limit($content['description_ru'], 150, ''),
                    'source_url' => $content['source_url'],
                    'source_domain' => $sourceDomain,
                    'source_type' => 'official_manufacturer',
                    'fallback_source_used' => false,
                    'needs_source_review' => false,
                    'needs_content_review' => false,
                    'needs_translation_review' => false,
                    'generated_content' => false,
                    'source_reviewed_at' => $now,
                    'updated_at' => $now,
                ]);

                $parserUpdates = [
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description_ru' => $shortRu,
                    'short_description_ro' => $shortRo,
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'found_title' => $content['name_ru'],
                    'found_description' => $content['description_ru'],
                    'official_source_url' => $content['source_url'],
                    'official_source_domain' => $sourceDomain,
                    'official_source_confidence' => 100,
                    'fallback_source_url' => null,
                    'fallback_source_domain' => null,
                    'fallback_source_used' => false,
                    'source_match_confidence' => 100,
                    'needs_source_review' => false,
                    'needs_content_review' => false,
                    'needs_translation_review' => false,
                    'generated_content' => false,
                    'content_source_type' => 'official_source',
                    'source_reviewed_at' => $now,
                    'updated_at' => $now,
                ];

                if ($product->source_parser_item_id) {
                    DB::table('product_parser_items')
                        ->where('id', $product->source_parser_item_id)
                        ->update($parserUpdates);
                } else {
                    DB::table('product_parser_items')->where('sku', $sku)->update($parserUpdates);
                }
            }
        });
    }

    private function records(): array
    {
        return [
            'HT1A792' => [
                'name_ru' => 'L-образный вороток HOEGERT HT1A792, 1/2", 254 мм, CrV',
                'name_ro' => 'Mâner de prelungire tip L HOEGERT HT1A792, 1/2", 254 mm, CrV',
                'description_ru' => 'L-образный вороток HOEGERT HT1A792 с присоединительным квадратом 1/2" и длиной 254 мм изготовлен из высококачественной легированной стали. Кованая конструкция устойчива к деформации и соответствует стандарту DIN 3122.',
                'description_ro' => 'Mânerul de prelungire tip L HOEGERT HT1A792, cu pătrat de antrenare de 1/2" și lungime de 254 mm, este fabricat din oțel aliat de calitate superioară. Construcția forjată este rezistentă la deformare și respectă standardul DIN 3122.',
                'source_url' => 'https://en.hoegert.com/product/1-2-socket-l-shape-extension-handle-wrench-254-mm-long-crv/',
            ],
            'HT1A764' => [
                'name_ru' => 'Шарнирный вороток HOEGERT HT1A764, 1/2", 457 мм',
                'name_ro' => 'Mâner articulat HOEGERT HT1A764, 1/2", 457 mm',
                'description_ru' => 'Шарнирный вороток HOEGERT HT1A764 с присоединительным квадратом 1/2" имеет длину 457 мм. Головка из закалённой стали поворачивается на 180°, а рукоятка из хромованадиевой стали рассчитана на максимальный крутящий момент 510 Н·м.',
                'description_ro' => 'Mânerul articulat HOEGERT HT1A764, cu pătrat de antrenare de 1/2", are lungimea de 457 mm. Capul din oțel călit pivotează la 180°, iar mânerul din oțel crom-vanadiu este proiectat pentru un cuplu maxim de 510 N·m.',
                'source_url' => 'https://en.hoegert.com/product/1-2-flexible-handle-457-mm/',
            ],
            'HT1A755' => [
                'name_ru' => 'Карданный шарнир HOEGERT HT1A755, 1/4", CrV',
                'name_ro' => 'Articulație universală HOEGERT HT1A755, 1/4", CrV',
                'description_ru' => 'Карданный шарнир HOEGERT HT1A755 предназначен для торцевых головок с присоединительным квадратом 1/4" и позволяет работать в труднодоступных местах. Изготовлен ковкой из хромованадиевой стали, устойчив к деформации и соответствует стандарту DIN 3123.',
                'description_ro' => 'Articulația universală HOEGERT HT1A755 este destinată cheilor tubulare cu pătrat de antrenare de 1/4" și permite lucrul în locuri greu accesibile. Este forjată din oțel crom-vanadiu, rezistentă la deformare și respectă standardul DIN 3123.',
                'source_url' => 'https://en.hoegert.com/product/universal-joint-for-socket-1-4-crv/',
            ],
            'HT4R328' => [
                'name_ru' => 'Ударный карданный шарнир HOEGERT HT4R328, 1/2", CrMo',
                'name_ro' => 'Articulație cardanică de impact HOEGERT HT4R328, 1/2", CrMo',
                'description_ru' => 'Ударный карданный шарнир HOEGERT HT4R328 с присоединительным квадратом 1/2" изготовлен ковкой из высококачественной хромомолибденовой стали. Он устойчив к интенсивным нагрузкам и предназначен для профессионального применения с ударным инструментом.',
                'description_ro' => 'Articulația cardanică de impact HOEGERT HT4R328, cu pătrat de antrenare de 1/2", este forjată din oțel crom-molibden de calitate superioară. Este rezistentă la sarcini intense și destinată utilizării profesionale cu scule de impact.',
                'source_url' => 'https://en.hoegert.com/product/1-2-ball-and-socket-joint-crmo/',
            ],
            'HT1S445' => [
                'name_ru' => 'Набор адаптеров для торцевых головок HOEGERT HT1S445, 1/4", 3/8", 1/2"',
                'name_ro' => 'Set de adaptoare pentru chei tubulare HOEGERT HT1S445, 1/4", 3/8", 1/2"',
                'description_ru' => 'Набор HOEGERT HT1S445 содержит три адаптера с шестигранным хвостовиком 1/4" для торцевых головок 1/4", 3/8" и 1/2". Адаптеры позволяют использовать шуруповёрт для операций с торцевыми головками соответствующего размера.',
                'description_ro' => 'Setul HOEGERT HT1S445 conține trei adaptoare cu tijă hexagonală de 1/4" pentru chei tubulare de 1/4", 3/8" și 1/2". Adaptoarele permit utilizarea unei mașini de înșurubat pentru operații cu chei tubulare de dimensiunea corespunzătoare.',
                'source_url' => 'https://en.hoegert.com/?p=2003',
            ],
        ];
    }

    public function down(): void
    {
        // Verified bilingual catalog content is intentionally retained.
    }
};
