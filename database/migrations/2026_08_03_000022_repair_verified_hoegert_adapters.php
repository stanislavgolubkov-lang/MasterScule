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
                $shared = [
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description_ru' => $shortRu,
                    'short_description_ro' => $shortRo,
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'needs_source_review' => false,
                    'needs_content_review' => false,
                    'needs_translation_review' => false,
                    'generated_content' => false,
                    'updated_at' => $now,
                ];

                DB::table('products')->where('id', $product->id)->update($shared + [
                    'name' => $content['name_ru'],
                    'short_description' => $shortRu,
                    'description' => $content['description_ru'],
                    'meta_description' => Str::limit($content['description_ru'], 150, ''),
                    'source_url' => $content['source_url'],
                    'source_domain' => $sourceDomain,
                    'source_type' => 'official_manufacturer',
                    'fallback_source_used' => false,
                    'source_reviewed_at' => $now,
                ]);

                $parserUpdates = $shared + [
                    'found_title' => $content['name_ru'],
                    'found_description' => $content['description_ru'],
                    'official_source_url' => $content['source_url'],
                    'official_source_domain' => $sourceDomain,
                    'official_source_confidence' => 100,
                    'fallback_source_url' => null,
                    'fallback_source_domain' => null,
                    'fallback_source_used' => false,
                    'source_match_confidence' => 100,
                    'content_source_type' => 'official_source',
                    'source_reviewed_at' => $now,
                ];

                $parserQuery = DB::table('product_parser_items');
                $product->source_parser_item_id
                    ? $parserQuery->where('id', $product->source_parser_item_id)->update($parserUpdates)
                    : $parserQuery->where('sku', $sku)->update($parserUpdates);
            }
        });
    }

    private function records(): array
    {
        $catalog = 'https://en.hoegert.com/wp-content/uploads/2025/06/CATALOGUE_HT_25_-DE_EN_FR_ES_HR_HU_RO-BG.pdf';

        return [
            'HT1A771' => [
                'name_ru' => 'Переходник для торцевых головок HOEGERT HT1A771, F3/8" × M1/4", 27 мм',
                'name_ro' => 'Adaptor pentru chei tubulare HOEGERT HT1A771, F3/8" × M1/4", 27 mm',
                'description_ru' => 'Переходник HOEGERT HT1A771 соединяет инструмент с квадратом 3/8" и торцевые головки 1/4". Длина — 27 мм. Изготовлен ковкой из хромованадиевой стали, имеет механизм фиксации головки и соответствует стандарту DIN 3121.',
                'description_ro' => 'Adaptorul HOEGERT HT1A771 conectează o sculă cu pătrat de 3/8" la chei tubulare de 1/4". Lungimea este de 27 mm. Este forjat din oțel crom-vanadiu, are mecanism de blocare a tubularei și respectă standardul DIN 3121.',
                'source_url' => $catalog,
            ],
            'HT1A772' => [
                'name_ru' => 'Переходник для торцевых головок HOEGERT HT1A772, F3/8" × M1/2", 35 мм',
                'name_ro' => 'Adaptor pentru chei tubulare HOEGERT HT1A772, F3/8" × M1/2", 35 mm',
                'description_ru' => 'Переходник HOEGERT HT1A772 соединяет инструмент с квадратом 3/8" и торцевые головки 1/2". Длина — 35 мм. Изготовлен ковкой из хромованадиевой стали, имеет механизм фиксации головки и соответствует стандарту DIN 3121.',
                'description_ro' => 'Adaptorul HOEGERT HT1A772 conectează o sculă cu pătrat de 3/8" la chei tubulare de 1/2". Lungimea este de 35 mm. Este forjat din oțel crom-vanadiu, are mecanism de blocare a tubularei și respectă standardul DIN 3121.',
                'source_url' => $catalog,
            ],
            'HT1A773' => [
                'name_ru' => 'Переходник для торцевых головок HOEGERT HT1A773, F1/2" × M3/8", 34 мм',
                'name_ro' => 'Adaptor pentru chei tubulare HOEGERT HT1A773, F1/2" × M3/8", 34 mm',
                'description_ru' => 'Переходник HOEGERT HT1A773 соединяет инструмент с квадратом 1/2" и торцевые головки 3/8". Длина — 34 мм. Изготовлен ковкой из хромованадиевой стали, имеет механизм фиксации головки и соответствует стандарту DIN 3121.',
                'description_ro' => 'Adaptorul HOEGERT HT1A773 conectează o sculă cu pătrat de 1/2" la chei tubulare de 3/8". Lungimea este de 34 mm. Este forjat din oțel crom-vanadiu, are mecanism de blocare a tubularei și respectă standardul DIN 3121.',
                'source_url' => $catalog,
            ],
            'HT1A775' => [
                'name_ru' => 'Переходник для торцевых головок HOEGERT HT1A775, F3/4" × M1/2", 51 мм',
                'name_ro' => 'Adaptor pentru chei tubulare HOEGERT HT1A775, F3/4" × M1/2", 51 mm',
                'description_ru' => 'Переходник HOEGERT HT1A775 соединяет инструмент с квадратом 3/4" и торцевые головки 1/2". Длина — 51 мм. Изготовлен ковкой из хромованадиевой стали, имеет механизм фиксации головки и соответствует стандарту DIN 3121.',
                'description_ro' => 'Adaptorul HOEGERT HT1A775 conectează o sculă cu pătrat de 3/4" la chei tubulare de 1/2". Lungimea este de 51 mm. Este forjat din oțel crom-vanadiu, are mecanism de blocare a tubularei și respectă standardul DIN 3121.',
                'source_url' => 'https://en.hoegert.com/product/socket-adapter-3-4-1-2-51-mm/',
            ],
            'HT1S203' => [
                'name_ru' => 'Отвёрточная рукоятка для головок HOEGERT HT1S203, 1/4", 150 мм',
                'name_ro' => 'Mâner tip șurubelniță pentru chei tubulare HOEGERT HT1S203, 1/4", 150 mm',
                'description_ru' => 'Отвёрточная рукоятка HOEGERT HT1S203 длиной 150 мм предназначена для торцевых головок 1/4". Стержень изготовлен из стали CrV, двухкомпонентная рукоятка обеспечивает удобный хват, а внутренний квадрат 1/4" в торце позволяет увеличить крутящий момент.',
                'description_ro' => 'Mânerul tip șurubelniță HOEGERT HT1S203, cu lungimea de 150 mm, este destinat cheilor tubulare de 1/4". Tija este realizată din oțel CrV, mânerul bicomponent oferă o priză comodă, iar pătratul interior de 1/4" din capăt permite mărirea cuplului.',
                'source_url' => $catalog,
            ],
        ];
    }

    public function down(): void
    {
        // Verified bilingual catalog content is intentionally retained.
    }
};
