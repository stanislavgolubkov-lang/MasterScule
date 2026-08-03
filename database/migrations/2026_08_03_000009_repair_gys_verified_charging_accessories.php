<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-gys-charging-accessories-2026-08-03';

    public function up(): void
    {
        DB::transaction(function (): void {
            $records = $this->records();
            $products = DB::table('products')->whereIn('sku', array_keys($records))->get()->keyBy('sku');
            $categoryId = DB::table('categories')->where('slug', 'baterii-incarcatoare')->value('id');

            foreach ($records as $sku => $content) {
                if ($product = $products->get($sku)) {
                    $this->updateProduct($product, $content, $categoryId);
                }
            }
        });
    }

    private function records(): array
    {
        return [
            '026094' => $this->record(
                'Кабели с зажимами GYS 026094 для GYSFLASH 18.12 PL, 1,9 м',
                'Cabluri cu cleme GYS 026094 pentru GYSFLASH 18.12 PL, 1,9 m',
                'GYS 026094 — комплект зарядных кабелей длиной 1,9 м с красным и чёрным зажимами и разъёмом A1. Комплект предназначен для подключения зарядного устройства GYSFLASH 18.12 PL к аккумулятору.',
                'GYS 026094 este un set de cabluri de încărcare de 1,9 m, cu cleme roșie și neagră și conector A1. Setul este destinat conectării încărcătorului GYSFLASH 18.12 PL la baterie.',
                [
                    'Тип' => 'Комплект кабелей с зажимами',
                    'Артикул производителя' => '026094',
                    'Совместимость' => 'GYSFLASH 18.12 PL',
                    'Длина кабеля' => '1.9 m',
                    'Разъём зарядки' => 'A1',
                    'Назначение' => 'Подключение зарядного устройства к аккумулятору',
                ],
                'https://www.shop.niteh.com/media/GYS_GYSFLASH_18.12_PL_-_026926.pdf',
                'manufacturer_datasheet_mirror',
                imageReviewed: true,
            ),
            '053519' => $this->record(
                'Адаптер прикуривателя GYS 053519 для зарядных устройств GYSFLASH',
                'Adaptor pentru priza auto GYS 053519 pentru încărcătoare GYSFLASH',
                'GYS 053519 — кабель-адаптер с предохранителем 10 А для подключения совместимого зарядного устройства к 12-вольтовой розетке автомобиля. Каталоги GYS указывают его как аксессуар для GYSFLASH 4A/7A и GYSTECH 3800.',
                'GYS 053519 este un cablu adaptor cu siguranță de 10 A pentru conectarea unui încărcător compatibil la priza auto de 12 V. Cataloagele GYS îl indică drept accesoriu pentru GYSFLASH 4A/7A și GYSTECH 3800.',
                [
                    'Тип' => 'Адаптер для автомобильного прикуривателя',
                    'Артикул производителя' => '053519',
                    'Совместимость' => 'GYSFLASH 4A / GYSFLASH 7A / GYSTECH 3800',
                    'Напряжение' => '12 V',
                    'Предохранитель' => '10 A',
                    'Назначение' => 'Подключение зарядного устройства через автомобильную розетку',
                ],
                'https://www.te.com.sg/downloads/catalogues/gys/charger.pdf',
                'manufacturer_catalog_mirror',
                imageReviewed: true,
                discardUrls: ['https://tristool.md/uploaded_files/053137.jpg'],
            ),
            '054677' => $this->record(
                'Сетевой зарядный блок GYS 054677 для GYSPACK AUTO/400/AIR/600/PRO',
                'Bloc de încărcare GYS 054677 pentru GYSPACK AUTO/400/AIR/600/PRO',
                'GYS 054677 — сетевой зарядный блок с европейской вилкой для внутренних 12-вольтовых аккумуляторов пусковых устройств GYSPACK AUTO, 400, AIR, 600 и PRO. Блок принимает 100–240 В, выдаёт зарядное напряжение 16 В и ток до 1,2 А.',
                'GYS 054677 este un bloc de încărcare cu fișă europeană pentru bateriile interne de 12 V ale boosterelor GYSPACK AUTO, 400, AIR, 600 și PRO. Blocul acceptă 100–240 V și furnizează o tensiune de încărcare de 16 V, cu un curent de până la 1,2 A.',
                [
                    'Тип' => 'Сетевой зарядный блок',
                    'Артикул производителя' => '054677',
                    'Совместимость' => 'GYSPACK AUTO / 400 / AIR / 600 / PRO',
                    'Напряжение' => '100–240 V AC',
                    'Напряжение зарядки' => '16 V',
                    'Зарядный ток' => '1.2 A',
                    'Мощность' => '16 W',
                    'Назначение' => 'Зарядка внутреннего аккумулятора GYSPACK',
                ],
                'https://gys-ukraine.com/uk/production/chargeur-euro-230v-12v-gyspack-auto-400-air-600-pro-2/',
                'manufacturer_regional_page',
                imageReviewed: true,
            ),
            '087132' => $this->record(
                'Сетевое зарядное устройство GYS 087132 USB-C, 67 Вт',
                'Încărcător de rețea GYS 087132 USB-C, 67 W',
                'GYS 087132 — сетевое зарядное устройство USB-C мощностью до 67 Вт для быстрой зарядки совместимых смартфонов, планшетов и пусковых устройств. Поддерживаются выходные режимы 5/9/12/15 В при 3 А и 20 В при 3,35 А; блок подходит для NOMAD POWER PRO 901 FC.',
                'GYS 087132 este un încărcător de rețea USB-C de până la 67 W pentru încărcarea rapidă a smartphone-urilor, tabletelor și boosterelor compatibile. Oferă modurile de ieșire 5/9/12/15 V la 3 A și 20 V la 3,35 A și este compatibil cu NOMAD POWER PRO 901 FC.',
                [
                    'Тип' => 'Сетевое зарядное устройство USB-C',
                    'Артикул производителя' => '087132',
                    'Совместимость' => 'NOMAD POWER PRO 901 FC / устройства USB-C',
                    'Напряжение' => '100–240 V AC',
                    'Выходы питания' => 'USB-C: 5 / 9 / 12 / 15 V, 3 A; 20 V, 3.35 A',
                    'Мощность' => '67 W',
                    'Функции' => 'Быстрая зарядка',
                ],
                'https://www.comptoirdespros.com/media/FT_Gys_087132.pdf',
                'manufacturer_datasheet_mirror',
                imageReviewed: true,
            ),
            '53139' => $this->record(
                'Внутренний аккумулятор GYS 53139 для GYSPACK, 12 В, 18 А·ч',
                'Baterie internă GYS 53139 pentru GYSPACK, 12 V, 18 Ah',
                'GYS 53139 — герметичный свинцово-кислотный аккумулятор 6FM-18 напряжением 12 В и ёмкостью 18 А·ч. Он применяется как сменная внутренняя батарея в пусковых устройствах GYSPACK AUTO, GYSPACK 400 и GYSPACK AIR.',
                'GYS 53139 este o baterie etanșă cu plumb-acid 6FM-18, de 12 V și 18 Ah. Se utilizează ca baterie internă de schimb pentru boosterele GYSPACK AUTO, GYSPACK 400 și GYSPACK AIR.',
                [
                    'Тип' => 'Внутренний аккумулятор для пускового устройства',
                    'Артикул производителя' => '53139',
                    'Тип внутреннего аккумулятора' => 'Герметичный свинцово-кислотный',
                    'Напряжение аккумулятора' => '12 V',
                    'Ёмкость аккумулятора' => '18 Ah',
                    'Совместимость' => 'GYSPACK AUTO / GYSPACK 400 / GYSPACK AIR',
                ],
                'https://www.gys.com.ua/pdf/gys-chargers.pdf',
                'manufacturer_catalog_mirror',
                imageReviewed: false,
            ),
            '53151' => $this->record(
                'Внутренний аккумулятор GYS 53151 для GYSPACK, 12 В, 22 А·ч',
                'Baterie internă GYS 53151 pentru GYSPACK, 12 V, 22 Ah',
                'GYS 53151 — герметичный свинцово-кислотный аккумулятор 6FM-22 напряжением 12 В и ёмкостью 22 А·ч. Он предназначен для замены внутренней батареи в пусковых устройствах GYSPACK 600 и GYSPACK PRO.',
                'GYS 53151 este o baterie etanșă cu plumb-acid 6FM-22, de 12 V și 22 Ah. Este destinată înlocuirii bateriei interne în boosterele GYSPACK 600 și GYSPACK PRO.',
                [
                    'Тип' => 'Внутренний аккумулятор для пускового устройства',
                    'Артикул производителя' => '53151',
                    'Тип внутреннего аккумулятора' => 'Герметичный свинцово-кислотный',
                    'Напряжение аккумулятора' => '12 V',
                    'Ёмкость аккумулятора' => '22 Ah',
                    'Совместимость' => 'GYSPACK 600 / GYSPACK PRO',
                ],
                'https://www.comptoirdespros.com/media/MU_Gys_026155.pdf',
                'manufacturer_manual_mirror',
                imageReviewed: false,
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
        string $referenceType,
        bool $imageReviewed,
        array $discardUrls = [],
    ): array {
        return compact(
            'nameRu', 'nameRo', 'descriptionRu', 'descriptionRo', 'attributes',
            'referenceUrl', 'referenceType', 'imageReviewed', 'discardUrls'
        );
    }

    private function updateProduct(object $product, array $content, ?int $categoryId): void
    {
        $now = now();
        $sourceDomain = parse_url($content['referenceUrl'], PHP_URL_HOST);
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sourceUrls = $this->appendReferenceUrl(
            $product->parser_source_urls ?? null,
            $content['referenceUrl'],
            $content['discardUrls']
        );

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
            'source_type' => $content['referenceType'],
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'needs_content_review' => false,
            'needs_translation_review' => false,
            'needs_category_review' => false,
            'needs_image_review' => ! $content['imageReviewed'],
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
        $parserSourceUrls = $this->appendReferenceUrl(
            $parserItem?->source_urls_json,
            $content['referenceUrl'],
            $content['discardUrls']
        );

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
            'official_source_url' => $content['referenceUrl'],
            'official_source_domain' => $sourceDomain,
            'official_source_confidence' => 95,
            'fallback_source_url' => null,
            'fallback_source_domain' => null,
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'needs_content_review' => false,
            'needs_translation_review' => false,
            'translation_source_type' => 'curated_translation',
            'translation_reviewed_at' => $now,
            'needs_image_review' => ! $content['imageReviewed'],
            'image_reviewed_at' => $content['imageReviewed'] ? $now : null,
            'generated_content' => false,
            'content_source_type' => $content['referenceType'],
            'category_id' => $categoryId,
            'detected_category_id' => $categoryId,
            'detected_category_path' => 'baterii-incarcatoare',
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
                'snippet' => 'GYS charging accessory publication matched by exact SKU.',
                'source_type' => $content['referenceType'],
                'confidence_score' => 95,
                'raw_data_json' => json_encode(['sku' => $product->sku, 'brand' => 'GYS'], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function appendReferenceUrl(?string $json, string $referenceUrl, array $discardUrls): array
    {
        $urls = json_decode($json ?: '[]', true);
        $urls = is_array($urls) ? $urls : [];
        $urls = array_filter($urls, fn ($url) => is_string($url) && ! in_array($url, $discardUrls, true));
        $urls[] = $referenceUrl;

        return array_values(array_unique($urls));
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
            'evidence' => json_encode(["Exact GYS SKU {$product->sku} belongs to the battery charging family."], JSON_UNESCAPED_UNICODE),
            'alternatives' => json_encode([]),
            'validation_errors' => json_encode([]),
            'applied_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Curated exact-SKU content is intentionally retained.
    }
};
