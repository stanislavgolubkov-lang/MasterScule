<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-gys-battery-testers-2026-08-03';

    public function up(): void
    {
        DB::transaction(function (): void {
            $records = $this->records();
            $products = DB::table('products')->whereIn('sku', array_keys($records))->get()->keyBy('sku');
            $categoryId = DB::table('categories')->where('slug', 'multimetre-testere')->value('id');

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
            '024168' => $this->record(
                'Электронный тестер аккумуляторов GYS 024168 NBT 200, 12 В',
                'Tester electronic pentru baterii GYS 024168 NBT 200, 12 V',
                'GYS NBT 200 (024168) проверяет 12-вольтовый аккумулятор, напряжение при запуске двигателя и работу генератора. Прибор рассчитан на свинцово-кислотные батареи 20–150 А·ч, измеряет напряжение от 7 до 15 В и выдаёт результат примерно за одну секунду.',
                'GYS NBT 200 (024168) verifică bateria de 12 V, tensiunea la pornirea motorului și funcționarea alternatorului. Aparatul este destinat bateriilor cu plumb-acid de 20–150 Ah, măsoară tensiunea între 7 și 15 V și afișează rezultatul în aproximativ o secundă.',
                [
                    'Тип' => 'Электронный тестер аккумуляторов',
                    'Напряжение аккумулятора' => '12 V',
                    'Диапазон ёмкости аккумулятора' => '20–150 Ah',
                    'Диапазон измерения напряжения' => '7–15 V',
                    'Проверяемые системы' => 'Аккумулятор / стартер / генератор',
                    'Поддерживаемые аккумуляторы' => 'VRLA / GEL / AGM / жидкостные',
                    'Стандарты пускового тока' => 'EN / DIN / SAE / IEC / CA-MCA',
                    'Диапазон EN' => '185–1125 CCA',
                    'Время анализа' => '1 s',
                    'Длина кабеля' => '50 cm',
                ],
                'https://optimotive.co.uk/pdfs/datasheets/024168.pdf',
                'exact_sku_manufacturer_datasheet_mirror',
                '0.25 kg',
                '70 × 15 × 120 mm',
            ),
            '024175' => $this->record(
                'Электронный тестер аккумуляторов GYS 024175 DBT 300, 12 В',
                'Tester electronic pentru baterii GYS 024175 DBT 300, 12 V',
                'GYS DBT 300 (024175) предназначен для проверки 12-вольтовых аккумуляторов, стартеров и генераторов. Он работает со свинцово-кислотными батареями 4–150 А·ч, измеряет напряжение от 4,5 до 16 В и показывает доступный пусковой ток, уровень заряда и состояние батареи.',
                'GYS DBT 300 (024175) este destinat verificării bateriilor, demaroarelor și alternatoarelor de 12 V. Funcționează cu baterii cu plumb-acid de 4–150 Ah, măsoară tensiunea între 4,5 și 16 V și indică puterea de pornire disponibilă, nivelul de încărcare și starea bateriei.',
                [
                    'Тип' => 'Электронный тестер аккумуляторов',
                    'Напряжение аккумулятора' => '12 V',
                    'Диапазон ёмкости аккумулятора' => '4–150 Ah',
                    'Диапазон измерения напряжения' => '4.5–16 V',
                    'Проверяемые системы' => 'Аккумулятор / стартер / генератор',
                    'Поддерживаемые аккумуляторы' => 'VRLA / GEL / AGM / жидкостные',
                    'Стандарты пускового тока' => 'EN / DIN / SAE / IEC / CA-MCA',
                    'Диапазон EN' => '185–1125 CCA',
                    'Время анализа' => '1 s',
                    'Длина кабеля' => '50 cm',
                ],
                'https://m.media-amazon.com/images/I/B1jI3D6V6YL.pdf',
                'exact_sku_manufacturer_datasheet_mirror',
                '0.25 kg',
                '120 × 79 × 227 mm',
            ),
            '024182' => $this->record(
                'Электронный тестер аккумуляторов GYS 024182 DBT 400, 6/12 В',
                'Tester electronic pentru baterii GYS 024182 DBT 400, 6/12 V',
                'GYS DBT 400 (024182) проверяет аккумуляторы 6 и 12 В, а также системы запуска и зарядки 12/24 В. Он рассчитан на батареи 7–230 А·ч, измеряет напряжение от 1,5 до 30 В, определяет внутреннее сопротивление и поддерживает обычные, герметичные и Start/Stop аккумуляторы.',
                'GYS DBT 400 (024182) verifică baterii de 6 și 12 V, precum și sistemele de pornire și încărcare de 12/24 V. Este destinat bateriilor de 7–230 Ah, măsoară tensiunea între 1,5 și 30 V, determină rezistența internă și acceptă baterii convenționale, etanșe și Start/Stop.',
                [
                    'Тип' => 'Электронный тестер аккумуляторов',
                    'Напряжение аккумулятора' => '6 / 12 V',
                    'Диапазон ёмкости аккумулятора' => '7–230 Ah',
                    'Диапазон измерения напряжения' => '1.5–30 V',
                    'Проверяемые системы' => 'Аккумулятор / стартер / генератор',
                    'Поддерживаемые аккумуляторы' => 'VRLA / GEL / AGM / EFB / жидкостные',
                    'Стандарты пускового тока' => 'EN / DIN / SAE / JIS / IEC / CA-MCA',
                    'Диапазон EN' => '40–2100 CCA',
                    'Время анализа' => '1 s',
                    'Источник питания' => '6 × AA (LR6)',
                    'Длина кабеля' => '180 cm',
                ],
                'https://handleidingen.acculaders.nl/wp-content/uploads/2020/12/productsheet-gys-dbt-400.pdf',
                'exact_sku_manufacturer_datasheet_mirror',
                '0.8 kg',
                '190 × 50 × 115 mm',
            ),
            '024205' => $this->record(
                'Профессиональный тестер аккумуляторов GYS 024205 PBT 600 с принтером',
                'Tester profesional pentru baterii GYS 024205 PBT 600 cu imprimantă',
                'GYS PBT 600 (024205) — профессиональный тестер 12-вольтовых аккумуляторов со встроенным термопринтером. Он анализирует батареи 30–220 А·ч, системы запуска и зарядки 12/24 В, измеряет напряжение от 6 до 30 В и печатает отчёт с результатами проверки.',
                'GYS PBT 600 (024205) este un tester profesional pentru baterii de 12 V, cu imprimantă termică integrată. Analizează baterii de 30–220 Ah, sistemele de pornire și încărcare de 12/24 V, măsoară tensiunea între 6 și 30 V și tipărește raportul verificării.',
                [
                    'Тип' => 'Профессиональный тестер аккумуляторов с принтером',
                    'Напряжение аккумулятора' => '12 V',
                    'Диапазон ёмкости аккумулятора' => '30–220 Ah',
                    'Диапазон измерения напряжения' => '6–30 V',
                    'Проверяемые системы' => 'Аккумулятор / стартер / генератор',
                    'Поддерживаемые аккумуляторы' => 'VRLA / GEL / AGM / EFB / жидкостные',
                    'Стандарты пускового тока' => 'EN / DIN / SAE / JIS / IEC / CA-MCA',
                    'Встроенный принтер' => 'Термопринтер без чернил',
                    'Время анализа' => '1 s',
                    'Источник питания' => '1 × 9 V',
                    'Длина кабеля' => '1.2 m',
                ],
                'https://www.skb.ch/wp-content/uploads/2024/07/GYS_Charger_72dpi_2025.pdf',
                'exact_sku_manufacturer_catalog_mirror',
                '1.1 kg',
            ),
            '052994' => $this->record(
                'Инфракрасный термометр GYS 052994, −50…+380 °C',
                'Termometru cu infraroșu GYS 052994, −50…+380 °C',
                'GYS 052994 — бесконтактный инфракрасный термометр с лазерным наведением для контроля температуры металла при предварительном и последующем нагреве или термообработке. Диапазон измерения составляет от −50 до +380 °C, время отклика — 500 мс, оптическое разрешение — 12:1.',
                'GYS 052994 este un termometru cu infraroșu fără contact, cu ghidare laser, pentru controlul temperaturii metalului la preîncălzire, postîncălzire sau tratament termic. Intervalul de măsurare este de la −50 la +380 °C, timpul de răspuns este de 500 ms, iar rezoluția optică este de 12:1.',
                [
                    'Тип' => 'Инфракрасный термометр',
                    'Температурный диапазон' => '−50…+380 °C',
                    'Точность' => '±1.5 °C или ±1.5%',
                    'Разрешение' => '0.1 °C',
                    'Время отклика' => '500 ms',
                    'Спектральный диапазон' => '8–14 µm',
                    'Коэффициент излучения' => '0.95',
                    'Оптическое разрешение' => '12:1',
                    'Источник питания' => '1 × 9 V',
                ],
                'https://www.gysweldingusa.com/product/infrared-thermometer/',
                'manufacturer_product_page',
                '0.188 kg',
                '175 × 100 × 50 mm',
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
        ?string $weight = null,
        ?string $dimensions = null,
    ): array {
        return compact(
            'nameRu', 'nameRo', 'descriptionRu', 'descriptionRo', 'attributes',
            'referenceUrl', 'referenceType', 'weight', 'dimensions'
        );
    }

    private function updateProduct(object $product, array $content, ?int $categoryId): void
    {
        $now = now();
        $sourceDomain = parse_url($content['referenceUrl'], PHP_URL_HOST);
        $isManufacturerSource = str_starts_with($content['referenceType'], 'manufacturer_');
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
            'weight' => $content['weight'],
            'dimensions' => $content['dimensions'],
            'category_id' => $categoryId,
            'parser_source_urls' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES),
            'source_url' => $content['referenceUrl'],
            'source_domain' => $sourceDomain,
            'source_type' => $content['referenceType'],
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'needs_content_review' => false,
            'generated_content' => false,
            'meta_title' => $content['nameRu'].' | MasterScule.md',
            'meta_description' => mb_substr($content['descriptionRu'], 0, 250),
            'updated_at' => $now,
        ]);

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
            'official_source_url' => $isManufacturerSource ? $content['referenceUrl'] : null,
            'official_source_domain' => $isManufacturerSource ? $sourceDomain : null,
            'official_source_confidence' => $isManufacturerSource ? 95 : null,
            'fallback_source_url' => null,
            'fallback_source_domain' => null,
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'needs_content_review' => false,
            'generated_content' => false,
            'content_source_type' => $content['referenceType'],
            'translation_source_type' => 'curated_translation',
            'translation_reviewed_at' => $now,
            'category_id' => $categoryId,
            'detected_category_id' => $categoryId,
            'detected_category_path' => 'multimetre-testere',
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
                'snippet' => 'GYS publication matched by exact SKU.',
                'source_type' => $content['referenceType'],
                'confidence_score' => 95,
                'raw_data_json' => json_encode(['sku' => $product->sku, 'brand' => 'GYS'], JSON_UNESCAPED_UNICODE),
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

    public function down(): void
    {
        // Curated exact-SKU content is intentionally retained.
    }
};
