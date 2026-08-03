<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-gys-induction-family-2026-08-03';

    public function up(): void
    {
        DB::transaction(function (): void {
            $records = $this->records();
            $products = DB::table('products')->whereIn('sku', array_keys($records))->get()->keyBy('sku');
            $categoryId = DB::table('categories')->where('slug', 'sudura-richtuire-vopsire')->value('id');

            foreach ($records as $sku => $content) {
                if ($product = $products->get($sku)) {
                    $this->updateProduct($product, $content, $categoryId);
                }
            }
        });
    }

    private function records(): array
    {
        $accessoryCatalog = 'https://documents.kramp.com/056879GYS_EN.pdf';

        return [
            '053366' => $this->record(
                'Индуктор для откручивания GYS 053366 для GYSDUCTION AUTO',
                'Inductor pentru deblocare GYS 053366 pentru GYSDUCTION AUTO',
                'Индуктор GYS 053366 предназначен для интенсивного локального нагрева заржавевших или заклинивших гаек и болтов с системой GYSDUCTION AUTO. Его также применяют для удаления герметика с кузовных элементов, нагрева крупных соединителей и формовки металла.',
                'Inductorul GYS 053366 este destinat încălzirii locale intense a piulițelor și șuruburilor ruginite sau gripate cu sistemul GYSDUCTION AUTO. Poate fi utilizat și pentru îndepărtarea materialului de etanșare de pe elementele caroseriei, încălzirea conectorilor mari și formarea metalului.',
                [
                    'Тип' => 'Индуктор для откручивания',
                    'Совместимость' => 'GYSDUCTION AUTO',
                    'Назначение' => 'Ослабление закисших гаек и болтов',
                    'Применение' => 'Снятие герметика / формовка металла',
                ],
                'https://www.groupe-mlv-france.fr/debosselage-decollage-par-induction/72-degrippage-pour-gysduction-auto.html',
                'exact_sku_distributor_product_page',
            ),
            '053458' => $this->record(
                'Ферритовый сердечник GYS 053458 B2 для индуктора POWERDUCTION',
                'Miez de ferită GYS 053458 B2 pentru inductor POWERDUCTION',
                'GYS 053458 — сменный ферритовый сердечник B2 для индуктора POWERDUCTION S180/B2. Деталь концентрирует магнитный поток в рабочей зоне индуктора и заменяется отдельно при износе или повреждении.',
                'GYS 053458 este un miez de ferită B2 de schimb pentru inductorul POWERDUCTION S180/B2. Piesa concentrează fluxul magnetic în zona de lucru a inductorului și poate fi înlocuită separat în caz de uzură sau deteriorare.',
                [
                    'Тип' => 'Ферритовый сердечник',
                    'Модель индуктора' => 'B2',
                    'Совместимость' => 'POWERDUCTION S180/B2',
                    'Назначение' => 'Замена феррита индуктора',
                    'Применение' => 'Концентрированный нагрев металла',
                ],
                $accessoryCatalog,
                'exact_sku_manufacturer_catalog_mirror',
            ),
            '056992' => $this->record(
                'Индукционный нагреватель GYS 056992 POWERDUCTION 37LG, 3,7 кВт',
                'Încălzitor prin inducție GYS 056992 POWERDUCTION 37LG, 3,7 kW',
                'GYS POWERDUCTION 37LG (056992) — переносной индукционный нагреватель мощностью 3,7 кВт для быстрого нагрева стали и алюминия без открытого пламени. Жидкостное охлаждение с баком 1,5 л позволяет продолжительно работать при демонтаже закисших деталей, элементов рулевого управления, свечей накаливания и выхлопных систем.',
                'GYS POWERDUCTION 37LG (056992) este un încălzitor portabil prin inducție de 3,7 kW pentru încălzirea rapidă a oțelului și aluminiului fără flacără deschisă. Răcirea cu lichid și rezervorul de 1,5 l permit lucrul prelungit la demontarea pieselor gripate, elementelor de direcție, bujiilor incandescente și sistemelor de evacuare.',
                [
                    'Тип' => 'Индукционный нагреватель',
                    'Напряжение' => '230 V, 1~',
                    'Мощность' => '3700 W',
                    'Потребляемый ток' => '16 A',
                    'Частота индукции' => '20–30 kHz',
                    'Шаг регулировки мощности' => '250 W',
                    'Система охлаждения' => 'Жидкостная',
                    'Объём бака' => '1.5 l',
                    'Длина кабеля индуктора' => '2 m',
                    'Глубина нагрева' => 'до 6 mm',
                    'Комплектация' => 'Индуктор S90',
                    'Степень защиты' => 'IP21',
                ],
                'https://documents.kramp.com/056992GYS_FR.pdf',
                'exact_sku_manufacturer_datasheet_mirror',
                '15 kg',
                '285 × 450 × 250 mm',
            ),
            '058583' => $this->record(
                'Индукционный нагреватель GYS 058583 POWERDUCTION 39LG C20/B1, 3,7 кВт',
                'Încălzitor prin inducție GYS 058583 POWERDUCTION 39LG C20/B1, 3,7 kW',
                'GYS POWERDUCTION 39LG (058583) — мобильный индукционный нагреватель мощностью 3,7 кВт с жидкостным охлаждением и индуктором C20/B1. Он предназначен для нагрева стали и алюминия, демонтажа закисших механических деталей и кузовных работ; трёхметровый кабель облегчает работу вокруг автомобиля.',
                'GYS POWERDUCTION 39LG (058583) este un încălzitor mobil prin inducție de 3,7 kW, cu răcire cu lichid și inductor C20/B1. Este destinat încălzirii oțelului și aluminiului, demontării pieselor mecanice gripate și lucrărilor de caroserie; cablul de trei metri facilitează lucrul în jurul vehiculului.',
                [
                    'Тип' => 'Индукционный нагреватель',
                    'Напряжение' => '230 V, 1~',
                    'Мощность' => '3700 W',
                    'Потребляемый ток' => '16 A',
                    'Частота индукции' => '20–50 kHz',
                    'Шаг регулировки мощности' => '250 W',
                    'Система охлаждения' => 'Жидкостная',
                    'Объём бака' => '7 l',
                    'Длина кабеля индуктора' => '3 m',
                    'Глубина нагрева' => 'до 6 mm',
                    'Комплектация' => 'Индуктор C20/B1',
                    'Степень защиты' => 'IP21',
                ],
                'https://www.skb.ch/wp-content/uploads/2025/07/058583.pdf',
                'exact_sku_manufacturer_datasheet_mirror',
                '50 kg',
                '700 × 530 × 370 mm',
            ),
            '059269' => $this->record(
                'Прямой индуктор GYS 059269 POWERDUCTION S180',
                'Inductor drept GYS 059269 POWERDUCTION S180',
                'GYS 059269 — сменный прямой индуктор S180 для локального нагрева плоских металлических участков и постановки рихтовочных линий. Рабочая головка размером 38 × 20 × 44 мм подключается к совместимым системам POWERDUCTION.',
                'GYS 059269 este un inductor drept S180 de schimb pentru încălzirea locală a zonelor metalice plane și realizarea liniilor de îndreptare. Capul de lucru de 38 × 20 × 44 mm se conectează la sistemele POWERDUCTION compatibile.',
                [
                    'Тип' => 'Прямой индуктор',
                    'Модель индуктора' => 'S180',
                    'Тип соединения' => 'S',
                    'Совместимость' => 'POWERDUCTION',
                    'Назначение' => 'Концентрированный нагрев металла',
                ],
                $accessoryCatalog,
                'exact_sku_manufacturer_catalog_mirror',
                dimensions: '38 × 20 × 44 mm',
            ),
            '062504' => $this->record(
                'Индукционный нагреватель GYS 062504 POWERDUCTION 10R, 1,2 кВт',
                'Încălzitor prin inducție GYS 062504 POWERDUCTION 10R, 1,2 kW',
                'GYS POWERDUCTION 10R (062504) — компактный индукционный нагреватель мощностью 1,2 кВт для быстрого ослабления закисших гаек, болтов и других металлических деталей. Комплект включает пять сменных индукторов: спирали Ø 18, 24 и 30 мм, гибкий плетёный и жёсткий прямой провод длиной 80 см.',
                'GYS POWERDUCTION 10R (062504) este un încălzitor compact prin inducție de 1,2 kW pentru deblocarea rapidă a piulițelor, șuruburilor și altor piese metalice gripate. Setul include cinci inductoare interschimbabile: spirale de Ø 18, 24 și 30 mm, un conductor flexibil împletit și unul drept rigid de 80 cm.',
                [
                    'Тип' => 'Индукционный нагреватель',
                    'Напряжение' => '230 V, 1~',
                    'Мощность' => '1200 W',
                    'Потребляемый ток' => '16 A',
                    'Частота индукции' => '20–30 kHz',
                    'Время нагрева' => 'M10: 10 s',
                    'Комплектация' => '5 сменных индукторов',
                    'Степень защиты' => 'IP21',
                ],
                'https://manualzz.com/doc/55395076/gys-powerduction-10r-datasheet',
                'exact_sku_manufacturer_datasheet_mirror',
                '3.5 kg',
                '230 × 140 × 100 mm',
            ),
            '064485' => $this->record(
                'Адаптер индуктора GYS 064485 POWERDUCTION 28S',
                'Adaptor pentru inductor GYS 064485 POWERDUCTION 28S',
                'GYS 064485 — адаптер 28S для подключения сменных индукторов к совместимым системам POWERDUCTION. Он используется как соединительный элемент между кабелем аппарата и индукционными головками B-типа.',
                'GYS 064485 este un adaptor 28S pentru conectarea inductoarelor interschimbabile la sistemele POWERDUCTION compatibile. Este utilizat ca element de legătură între cablul aparatului și capetele de inducție de tip B.',
                [
                    'Тип' => 'Адаптер индуктора',
                    'Модель индуктора' => '28S',
                    'Совместимость' => 'POWERDUCTION',
                    'Назначение' => 'Подключение сменных индукторов',
                    'Тип соединения' => 'B-type',
                ],
                $accessoryCatalog,
                'exact_sku_manufacturer_catalog_mirror',
            ),
            '067875' => $this->record(
                'Ферритовый сердечник GYS 067875 B3 для индуктора POWERDUCTION',
                'Miez de ferită GYS 067875 B3 pentru inductor POWERDUCTION',
                'GYS 067875 — сменный ферритовый сердечник B3 для индуктора POWERDUCTION S180/B3W. Он устанавливается в узкую рабочую головку и заменяется отдельно при износе.',
                'GYS 067875 este un miez de ferită B3 de schimb pentru inductorul POWERDUCTION S180/B3W. Se montează în capul de lucru îngust și poate fi înlocuit separat în caz de uzură.',
                [
                    'Тип' => 'Ферритовый сердечник',
                    'Модель индуктора' => 'B3',
                    'Совместимость' => 'POWERDUCTION S180/B3W',
                    'Назначение' => 'Замена феррита индуктора',
                    'Применение' => 'Концентрированный нагрев металла',
                ],
                $accessoryCatalog,
                'exact_sku_manufacturer_catalog_mirror',
            ),
            '067899' => $this->record(
                'Прямой индуктор GYS 067899 POWERDUCTION S180/B3W',
                'Inductor drept GYS 067899 POWERDUCTION S180/B3W',
                'GYS 067899 — узкий прямой индуктор S180/B3W размером 33 × 29 × 90 мм для локального нагрева в труднодоступных местах. Он работает со сменным ферритом B3 и подключается через адаптер 28S или 32S к совместимым аппаратам POWERDUCTION.',
                'GYS 067899 este un inductor drept îngust S180/B3W, de 33 × 29 × 90 mm, pentru încălzire locală în zone greu accesibile. Funcționează cu ferita B3 interschimbabilă și se conectează prin adaptorul 28S sau 32S la aparatele POWERDUCTION compatibile.',
                [
                    'Тип' => 'Прямой индуктор',
                    'Модель индуктора' => 'S180/B3W',
                    'Совместимость' => 'POWERDUCTION',
                    'Тип соединения' => '28S / 32S',
                    'Комплектация' => 'Сменный феррит B3',
                    'Назначение' => 'Концентрированный нагрев металла',
                ],
                $accessoryCatalog,
                'exact_sku_manufacturer_catalog_mirror',
                dimensions: '33 × 29 × 90 mm',
            ),
            '070646' => $this->record(
                'Петлевой индуктор GYS 070646 POWERDUCTION S180/D55',
                'Inductor tip buclă GYS 070646 POWERDUCTION S180/D55',
                'GYS 070646 — петлевой индуктор S180/D55 с рабочим диаметром 55 мм для равномерного нагрева цилиндрических деталей. Он относится к сменной серии S180/D и используется с совместимыми адаптерами и аппаратами POWERDUCTION.',
                'GYS 070646 este un inductor tip buclă S180/D55 cu diametrul de lucru de 55 mm pentru încălzirea uniformă a pieselor cilindrice. Face parte din seria interschimbabilă S180/D și se utilizează cu adaptoare și aparate POWERDUCTION compatibile.',
                [
                    'Тип' => 'Петлевой индуктор',
                    'Модель индуктора' => 'S180/D55',
                    'Диаметр петли' => '55 mm',
                    'Совместимость' => 'POWERDUCTION',
                    'Назначение' => 'Нагрев цилиндрических деталей',
                ],
                $accessoryCatalog,
                'exact_sku_manufacturer_catalog_mirror',
            ),
            '072527' => $this->record(
                'Защитный корпус феррита GYS 072527 B3 для POWERDUCTION',
                'Carcasă de protecție pentru ferită GYS 072527 B3 pentru POWERDUCTION',
                'GYS 072527 — сменный защитный корпус для феррита B3 индуктора POWERDUCTION S180/B3W. Корпус предохраняет хрупкий ферритовый элемент от механических повреждений во время работы и хранения.',
                'GYS 072527 este o carcasă de protecție de schimb pentru ferita B3 a inductorului POWERDUCTION S180/B3W. Carcasa protejează elementul fragil din ferită împotriva deteriorării mecanice în timpul utilizării și depozitării.',
                [
                    'Тип' => 'Защитный корпус феррита',
                    'Модель индуктора' => 'B3',
                    'Совместимость' => 'POWERDUCTION S180/B3W',
                    'Назначение' => 'Защита феррита от повреждений',
                ],
                $accessoryCatalog,
                'exact_sku_manufacturer_catalog_mirror',
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
            'content_source_type' => $content['referenceType'],
            'translation_source_type' => 'curated_translation',
            'translation_reviewed_at' => $now,
            'category_id' => $categoryId,
            'detected_category_id' => $categoryId,
            'detected_category_path' => 'sudura-richtuire-vopsire',
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
