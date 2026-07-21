<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-jtc-sku-review';

    public function up(): void
    {
        $this->ensureCoolingCategory();
        $records = $this->records();
        $categoryIds = DB::table('categories')
            ->whereIn('slug', collect($records)->pluck('category')->unique()->all())
            ->pluck('id', 'slug');

        DB::transaction(function () use ($records, $categoryIds): void {
            foreach ($records as $sku => $content) {
                $product = DB::table('products')->where('sku', $sku)->first();
                if (! $product) {
                    continue;
                }

                $categoryId = $categoryIds->get($content['category']);
                $this->updateProduct($product, $content, $categoryId ? (int) $categoryId : null);
            }
        });
    }

    private function records(): array
    {
        return [
            'JTC-4145' => [
                'name_ru' => 'Съёмник и установщик ТНВД JTC-4145 для BMW N47/N57',
                'name_ro' => 'Extractor și montator pompă de înaltă presiune JTC-4145 pentru BMW N47/N57',
                'short_ru' => 'Инструмент JTC-4145 для демонтажа и установки ТНВД на дизельных двигателях BMW N47/N47S и N57/N57S.',
                'short_ro' => 'Scula JTC-4145 pentru demontarea și montarea pompei de înaltă presiune la motoarele diesel BMW N47/N47S și N57/N57S.',
                'description_ru' => 'JTC-4145 предназначен для демонтажа и установки топливного насоса высокого давления на дизельных двигателях BMW N47, N47S, N57 и N57S. Инструмент удерживает звёздочку и цепь при снятии насоса. Модель JTC-4145 снята с производства; актуальная замена производителя — JTC-6816. Номера специнструмента BMW: 11 8 740, 11 8 741 и 11 8 742.',
                'description_ro' => 'JTC-4145 este destinat demontării și montării pompei de combustibil de înaltă presiune la motoarele diesel BMW N47, N47S, N57 și N57S. Scula menține roata dințată și lanțul în poziție la scoaterea pompei. Modelul JTC-4145 a fost scos din producție; înlocuitorul actual al producătorului este JTC-6816. Numere sculă specială BMW: 11 8 740, 11 8 741 și 11 8 742.',
                'attributes' => [
                    'Тип' => 'Съёмник и установщик ТНВД',
                    'Назначение' => 'Демонтаж и установка насоса высокого давления',
                    'Марка автомобиля' => 'BMW',
                    'Тип двигателя' => 'Дизельный',
                    'Совместимые двигатели' => 'N47, N47S, N57, N57S',
                    'Номер специнструмента' => '11 8 740 / 11 8 741 / 11 8 742',
                    'Статус модели' => 'Снята с производства',
                    'Замена производителя' => 'JTC-6816',
                ],
                'category' => 'scule-pentru-motor',
                'vehicle_application' => 'BMW',
                'source_url' => 'https://specinstrument.ru/catalog/specinstrument/spetsinstrument_dlya_legkovykh_mashin/bmw/semnik_tnvd_bmw_dvig_n47_nov_art_jtc_6816/',
                'source_type' => 'verified_fallback',
                'source_verified' => false,
                'image_url' => null,
                'weight' => '0,075 kg',
                'dimensions' => '30 × 30 × 30 mm',
            ],
            'JTC-4181' => [
                'name_ru' => 'Головка для шкива распредвала JTC-4181, 1/2″, T100H, 78 мм, Mercedes-Benz',
                'name_ro' => 'Cap tubular pentru roata arborelui cu came JTC-4181, 1/2″, T100H, 78 mm, Mercedes-Benz',
                'short_ru' => 'Головка JTC-4181 с приводом 1/2″ и профилем T100H для корректировки положения шкива распредвала Mercedes-Benz.',
                'short_ro' => 'Cap tubular JTC-4181 cu antrenare de 1/2″ și profil T100H pentru corectarea poziției roții arborelui cu came Mercedes-Benz.',
                'description_ru' => 'Специальная головка JTC-4181 применяется для корректировки положения шкива распределительного вала на двигателях Mercedes-Benz M133, M157, M270, M271, M276 и M278. Размер — привод 1/2″, профиль T100H, длина 78 мм. Номер специнструмента Mercedes-Benz: 271589001000 / 11CW.',
                'description_ro' => 'Capul tubular special JTC-4181 se utilizează pentru corectarea poziției roții arborelui cu came la motoarele Mercedes-Benz M133, M157, M270, M271, M276 și M278. Dimensiune: antrenare 1/2″, profil T100H și lungime 78 mm. Număr sculă specială Mercedes-Benz: 271589001000 / 11CW.',
                'attributes' => [
                    'Тип' => 'Головка для шкива распределительного вала',
                    'Привод' => '1/2 inch',
                    'Рабочий профиль' => 'T100H',
                    'Длина' => '78 mm',
                    'Назначение' => 'Корректировка положения шкива распределительного вала',
                    'Совместимые двигатели' => 'Mercedes-Benz M133, M157, M270, M271, M276, M278',
                    'Номер специнструмента' => '271589001000 / 11CW',
                ],
                'category' => 'scule-pentru-motor',
                'vehicle_application' => 'Mercedes-Benz',
                'source_url' => 'https://eng.jtc.com.tw/product/?id=3865&mode=data',
                'source_type' => 'official_manufacturer',
                'source_verified' => true,
                'image_url' => 'https://eng.jtc.com.tw/archive/product/normal/NDE4MV8xMzA5MDcwNDE0MTM=.jpg',
                'weight' => '0,200 kg',
                'dimensions' => '25 × 25 × 78 mm',
            ],
            'JTC-4338' => [
                'name_ru' => 'Головка для тормозных колодок JTC-4338, 1/2″, H11 × 90 мм, Mercedes-Benz W166',
                'name_ro' => 'Cap tubular pentru plăcuțe de frână JTC-4338, 1/2″, H11 × 90 mm, Mercedes-Benz W166',
                'short_ru' => 'Специальная головка JTC-4338 с приводом 1/2″ и профилем H11 для обслуживания тормозных колодок Mercedes-Benz W166.',
                'short_ro' => 'Cap tubular special JTC-4338 cu antrenare de 1/2″ și profil H11 pentru întreținerea plăcuțelor de frână Mercedes-Benz W166.',
                'description_ru' => 'Головка JTC-4338 специально разработана для замены тормозных колодок на автомобилях Mercedes-Benz W166. Размер инструмента: привод 1/2″, рабочий профиль H11, длина 90 мм.',
                'description_ro' => 'Capul tubular JTC-4338 este proiectat special pentru înlocuirea plăcuțelor de frână la automobilele Mercedes-Benz W166. Dimensiunea sculei: antrenare 1/2″, profil de lucru H11 și lungime 90 mm.',
                'attributes' => [
                    'Тип' => 'Головка для обслуживания тормозных колодок',
                    'Привод' => '1/2 inch',
                    'Рабочий профиль' => 'H11',
                    'Длина' => '90 mm',
                    'Назначение' => 'Замена тормозных колодок',
                    'Совместимые модели' => 'Mercedes-Benz W166',
                ],
                'category' => 'scule-pentru-frane',
                'vehicle_application' => 'Mercedes-Benz',
                'source_url' => 'https://eng.jtc.com.tw/product/?id=4318&mode=data&top=2',
                'source_type' => 'official_manufacturer',
                'source_verified' => true,
                'image_url' => 'https://eng.jtc.com.tw/archive/product/normal/SlRDLTQzMzhfUF8xNDA3MTcwNjI2NDc=.jpg',
                'weight' => '0,155 kg',
                'dimensions' => '24 × 24 × 90 mm',
            ],
            'JTC-4729' => [
                'name_ru' => 'Съёмник муфты низкого давления кондиционера JTC-4729 для Mercedes-Benz',
                'name_ro' => 'Extractor pentru cupla de joasă presiune a climatizării JTC-4729 pentru Mercedes-Benz',
                'short_ru' => 'Специальный съёмник JTC-4729 для муфты низкого давления кондиционера Mercedes-Benz W221, W211, W204 и W219.',
                'short_ro' => 'Extractor special JTC-4729 pentru cupla de joasă presiune a climatizării la Mercedes-Benz W221, W211, W204 și W219.',
                'description_ru' => 'JTC-4729 специально предназначен для демонтажа соединительной муфты на линии низкого давления системы кондиционирования. Совместим с Mercedes-Benz W221, W211, W204 и W219. Номер специнструмента Mercedes-Benz: 211589006300.',
                'description_ro' => 'JTC-4729 este proiectat special pentru demontarea cuplei de pe circuitul de joasă presiune al instalației de climatizare. Este compatibil cu Mercedes-Benz W221, W211, W204 și W219. Număr sculă specială Mercedes-Benz: 211589006300.',
                'attributes' => [
                    'Тип' => 'Съёмник муфты низкого давления кондиционера',
                    'Система автомобиля' => 'Система кондиционирования',
                    'Назначение' => 'Демонтаж муфты низкого давления',
                    'Совместимые модели' => 'Mercedes-Benz W221, W211, W204, W219',
                    'Номер специнструмента' => '211589006300',
                ],
                'category' => 'scule-aer-conditionat-auto',
                'vehicle_application' => 'Mercedes-Benz',
                'source_url' => 'https://eng.jtc.com.tw/product/?id=1577&mode=data&top=2',
                'source_type' => 'official_manufacturer',
                'source_verified' => true,
                'image_url' => 'https://eng.jtc.com.tw/archive/product/normal/4729a1.jpg',
                'weight' => null,
                'dimensions' => null,
            ],
            'JTC-4822' => [
                'name_ru' => 'Съёмник хомутов с гибким тросом JTC-4822, 60 мм, Mercedes-Benz',
                'name_ro' => 'Extractor pentru coliere cu cablu flexibil JTC-4822, 60 mm, Mercedes-Benz',
                'short_ru' => 'Съёмник JTC-4822 с гибким тросом 600 мм для хомутов диаметром до 60 мм на патрубках системы охлаждения Mercedes-Benz.',
                'short_ro' => 'Extractor JTC-4822 cu cablu flexibil de 600 mm pentru coliere de până la 60 mm de la furtunurile sistemului de răcire Mercedes-Benz.',
                'description_ru' => 'JTC-4822 предназначен для снятия хомутов большого диаметра, в том числе с нижнего патрубка системы охлаждения Mercedes-Benz. Максимальный диаметр хомута — 60 мм, длина гибкого троса — 600 мм. Конструкция инструмента запатентована производителем.',
                'description_ro' => 'JTC-4822 este destinat demontării colierelor cu diametru mare, inclusiv de la furtunul inferior al sistemului de răcire Mercedes-Benz. Diametrul maxim al colierului este de 60 mm, iar cablul flexibil are lungimea de 600 mm. Construcția sculei este brevetată de producător.',
                'attributes' => [
                    'Тип' => 'Съёмник хомутов с гибким тросом',
                    'Назначение' => 'Хомуты нижнего патрубка системы охлаждения',
                    'Марка автомобиля' => 'Mercedes-Benz',
                    'Максимальный диаметр хомута' => '60 mm',
                    'Длина гибкого троса' => '600 mm',
                    'Патент' => 'Да',
                ],
                'category' => 'scule-sistem-racire-auto',
                'vehicle_application' => 'Mercedes-Benz',
                'source_url' => 'https://eng.jtc.com.tw/product/?id=1807&mode=data&top=2',
                'source_type' => 'official_manufacturer',
                'source_verified' => true,
                'image_url' => 'https://eng.jtc.com.tw/archive/product/normal/3e6faffc4007b00e8a8e9b2611a97c1e.jpg',
                'weight' => null,
                'dimensions' => null,
            ],
            'JW0832' => [
                'name_ru' => 'Набор для демонтажа свечей накаливания JW0832 для Mercedes-Benz CDI',
                'name_ro' => 'Set pentru demontarea bujiilor incandescente JW0832 pentru Mercedes-Benz CDI',
                'short_ru' => 'Набор JW0832 для демонтажа свечей накаливания на двигателях Mercedes-Benz OM611/612/613/628/646/647/648, кроме OM646 EVO.',
                'short_ro' => 'Set JW0832 pentru demontarea bujiilor incandescente la motoarele Mercedes-Benz OM611/612/613/628/646/647/648, cu excepția OM646 EVO.',
                'description_ru' => 'Набор JW0832 предназначен для демонтажа свечей накаливания на дизельных двигателях Mercedes-Benz OM611, OM612, OM613, OM628, OM646, OM647 и OM648. Набор не предназначен для двигателя OM646 EVO. Точный производитель и состав комплекта требуют подтверждения по первичному каталогу поставщика.',
                'description_ro' => 'Setul JW0832 este destinat demontării bujiilor incandescente la motoarele diesel Mercedes-Benz OM611, OM612, OM613, OM628, OM646, OM647 și OM648. Setul nu este destinat motorului OM646 EVO. Producătorul exact și componența setului trebuie confirmate din catalogul primar al furnizorului.',
                'attributes' => [
                    'Тип' => 'Набор для демонтажа свечей накаливания',
                    'Назначение' => 'Демонтаж свечей накаливания',
                    'Марка автомобиля' => 'Mercedes-Benz',
                    'Тип двигателя' => 'Дизельный',
                    'Совместимые двигатели' => 'OM611, OM612, OM613, OM628, OM646, OM647, OM648',
                    'Исключение' => 'OM646 EVO не поддерживается',
                ],
                'category' => 'scule-pentru-motor',
                'vehicle_application' => 'Mercedes-Benz',
                'source_url' => null,
                'source_type' => 'supplier_record',
                'source_verified' => false,
                'image_url' => null,
                'weight' => null,
                'dimensions' => null,
            ],
        ];
    }

    private function updateProduct(object $product, array $content, ?int $categoryId): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sourceDomain = $content['source_url'] ? parse_url($content['source_url'], PHP_URL_HOST) : null;
        $sourceUrls = array_values(array_filter([$content['source_url'], $content['image_url']]));
        $needsSourceReview = ! $content['source_verified'];

        $updates = [
            'name' => $content['name_ru'],
            'name_ru' => $content['name_ru'],
            'name_ro' => $content['name_ro'],
            'short_description' => $content['short_ru'],
            'short_description_ru' => $content['short_ru'],
            'short_description_ro' => $content['short_ro'],
            'description' => $content['description_ru'],
            'description_ru' => $content['description_ru'],
            'description_ro' => $content['description_ro'],
            'attributes' => $attributes,
            'weight' => $content['weight'],
            'dimensions' => $content['dimensions'],
            'vehicle_application' => $content['vehicle_application'],
            'parser_source_urls' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES),
            'source_url' => $content['source_url'],
            'source_domain' => $sourceDomain,
            'source_type' => $content['source_type'],
            'fallback_source_used' => $content['source_type'] === 'verified_fallback',
            'needs_source_review' => $needsSourceReview,
            'needs_image_review' => true,
            'needs_content_review' => false,
            'generated_content' => false,
            'source_reviewed_at' => $content['source_verified'] ? $now : null,
            'meta_title' => $content['name_ru'].' | MasterScule.md',
            'meta_description' => mb_substr($content['short_ru'], 0, 250),
            'needs_review' => true,
            'updated_at' => $now,
        ];

        if ($categoryId) {
            $updates['category_id'] = $categoryId;
            $updates['needs_category_review'] = false;
        }

        DB::table('products')->where('id', $product->id)->update($updates);

        if ($categoryId) {
            $this->syncCategory($product, $categoryId, $content['category'], $now);
        }

        if (! $product->source_parser_item_id) {
            return;
        }

        $isOfficial = $content['source_type'] === 'official_manufacturer';
        $isFallback = $content['source_type'] === 'verified_fallback';
        $parserUpdates = [
            'name_ru' => $content['name_ru'],
            'name_ro' => $content['name_ro'],
            'short_description_ru' => $content['short_ru'],
            'short_description_ro' => $content['short_ro'],
            'description_ru' => $content['description_ru'],
            'description_ro' => $content['description_ro'],
            'found_title' => $content['name_ru'],
            'found_description' => $content['description_ru'],
            'found_specs_json' => $attributes,
            'found_images_json' => json_encode(array_values(array_filter([$content['image_url']])), JSON_UNESCAPED_SLASHES),
            'selected_images_json' => json_encode(array_values(array_filter([$content['image_url']])), JSON_UNESCAPED_SLASHES),
            'processed_images_json' => json_encode([], JSON_UNESCAPED_SLASHES),
            'source_urls_json' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES),
            'vehicle_application' => $content['vehicle_application'],
            'official_source_url' => $isOfficial ? $content['source_url'] : null,
            'official_source_domain' => $isOfficial ? $sourceDomain : null,
            'official_source_confidence' => $isOfficial ? 100 : null,
            'fallback_source_url' => $isFallback ? $content['source_url'] : null,
            'fallback_source_domain' => $isFallback ? $sourceDomain : null,
            'fallback_source_used' => $isFallback,
            'source_match_confidence' => $isOfficial ? 100 : ($isFallback ? 95 : 0),
            'needs_source_review' => $needsSourceReview,
            'needs_image_review' => true,
            'needs_content_review' => false,
            'generated_content' => false,
            'content_source_type' => $isOfficial ? 'official_source' : ($isFallback ? 'fallback_reference' : 'supplier_record'),
            'image_source_type' => $content['image_url'] ? 'official_manufacturer' : null,
            'translation_source_type' => 'curated_translation',
            'source_reviewed_at' => $content['source_verified'] ? $now : null,
            'image_reviewed_at' => null,
            'translation_reviewed_at' => $now,
            'updated_at' => $now,
        ];

        if ($categoryId) {
            $parserUpdates = array_replace($parserUpdates, [
                'category_id' => $categoryId,
                'detected_category_id' => $categoryId,
                'detected_category_path' => $content['category'],
                'category_confidence_score' => 100,
                'category_detection_method' => $this->mode,
                'needs_category_review' => false,
            ]);
        }

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update($parserUpdates);

        if ($content['image_url']) {
            $this->syncOfficialImageCandidate((int) $product->source_parser_item_id, $content['image_url'], $now);
        }
    }

    private function syncOfficialImageCandidate(int $parserItemId, string $imageUrl, object $now): void
    {
        DB::table('product_parser_image_assets')
            ->where('parser_item_id', $parserItemId)
            ->update([
                'is_selected' => false,
                'is_main' => false,
                'updated_at' => $now,
            ]);

        DB::table('product_parser_image_assets')->updateOrInsert(
            [
                'parser_item_id' => $parserItemId,
                'source_url' => $imageUrl,
            ],
            [
                'source_domain' => parse_url($imageUrl, PHP_URL_HOST),
                'original_path' => null,
                'processed_path' => null,
                'preview_path' => null,
                'thumb_path' => null,
                'width' => null,
                'height' => null,
                'mime_type' => null,
                'status' => 'found',
                'is_selected' => true,
                'is_main' => true,
                'has_watermark' => false,
                'background_removed' => false,
                'background_removal_failed' => false,
                'needs_review' => false,
                'error_message' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function syncCategory(object $product, int $categoryId, string $categorySlug, object $now): void
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
            'taxonomy_version' => 'verified-2026-07-21',
            'input_hash' => hash('sha256', $this->mode.'|'.$product->sku.'|'.$product->category_id.'|'.$categoryId),
            'mode' => $this->mode,
            'status' => 'applied',
            'classifier_confidence' => 1,
            'verifier_confidence' => 1,
            'evidence' => json_encode(["Verified JTC product purpose identifies SKU {$product->sku}; selected category {$categorySlug}."], JSON_UNESCAPED_UNICODE),
            'alternatives' => json_encode([]),
            'validation_errors' => json_encode([]),
            'applied_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureCoolingCategory(): void
    {
        $parentId = DB::table('categories')->where('slug', 'scule-speciale-auto')->value('id');
        if (! $parentId) {
            return;
        }

        $now = now();
        $values = [
            'parent_id' => $parentId,
            'name' => 'Инструмент для системы охлаждения автомобиля',
            'name_ro' => 'Scule pentru sistemul de răcire auto',
            'description' => 'Специальные инструменты для патрубков, хомутов и других компонентов системы охлаждения автомобиля.',
            'description_ro' => 'Scule speciale pentru furtunuri, coliere și alte componente ale sistemului de răcire auto.',
            'is_active' => true,
            'is_assignable' => true,
            'is_menu_visible' => true,
            'source' => 'curated',
            'taxonomy_version' => 'verified-2026-07-21',
            'updated_at' => $now,
        ];

        if (DB::table('categories')->where('slug', 'scule-sistem-racire-auto')->exists()) {
            DB::table('categories')->where('slug', 'scule-sistem-racire-auto')->update($values);

            return;
        }

        DB::table('categories')->insert($values + [
            'slug' => 'scule-sistem-racire-auto',
            'sort_order' => 50,
            'created_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Curated JTC SKU content, source evidence, and category decisions are intentionally retained.
    }
};
