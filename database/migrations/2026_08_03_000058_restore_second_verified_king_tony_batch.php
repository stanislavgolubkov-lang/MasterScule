<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $records = [
            '4795-24' => $this->record(
                'Т-образный вороток с карданом KING TONY 4795-24, 1/2″, 600 мм',
                'Mâner în T cu articulație cardanică KING TONY 4795-24, 1/2″, 600 mm',
                'Т-образный вороток KING TONY 4795-24 с карданным шарниром 90° предназначен для торцевых головок с приводом 1/2″. Стержень изготовлен из хромованадиевой стали, общая длина — 600 мм, масса — около 700 г.',
                'Mânerul în T KING TONY 4795-24, cu articulație cardanică la 90°, este destinat capetelor tubulare cu antrenare de 1/2″. Tija este fabricată din oțel crom-vanadiu, lungimea totală este de 600 mm, iar greutatea de aproximativ 700 g.',
                ['Тип' => 'Т-образный вороток с карданом', 'Привод' => '1/2″', 'Угол шарнира' => '90°', 'Длина' => '600 мм', 'Вес' => 'около 700 г', 'Материал' => 'Хромованадиевая сталь'],
                '/images/catalog-reviewed/king-tony-recovery-2-sourced/4795',
                '4795',
                'https://www.kingtony.com/upload/products/4795.png',
                'https://kingtony-online.ru/catalog/product/king_tony_4795-24/',
                'verified_exact_distributor',
                98,
            ),
            '4795-36' => $this->record(
                'Т-образный вороток с карданом KING TONY 4795-36, 1/2″, 900 мм',
                'Mâner în T cu articulație cardanică KING TONY 4795-36, 1/2″, 900 mm',
                'Т-образный вороток KING TONY 4795-36 с карданным шарниром 90° предназначен для торцевых головок с приводом 1/2″. Вороток изготовлен из хромованадиевой стали и хромирован; общая длина — 900 мм, ширина Т-образной рукоятки — 200 мм, масса — 1000 г.',
                'Mânerul în T KING TONY 4795-36, cu articulație cardanică la 90°, este destinat capetelor tubulare cu antrenare de 1/2″. Este fabricat din oțel crom-vanadiu și cromat; lungimea totală este de 900 mm, lățimea mânerului în T de 200 mm, iar greutatea de 1000 g.',
                ['Тип' => 'Т-образный вороток с карданом', 'Привод' => '1/2″', 'Угол шарнира' => '90°', 'Длина' => '900 мм', 'Ширина рукоятки' => '200 мм', 'Вес' => '1000 г', 'Материал' => 'Хромованадиевая сталь', 'Покрытие' => 'Хромированное'],
                '/images/catalog-reviewed/king-tony-recovery-2-sourced/4795',
                '4795',
                'https://www.kingtony.com/upload/products/4795.png',
                'https://www.kingtony.com/upload/file/pdf/2024/348.pdf',
                'official_manufacturer',
                100,
            ),
            '3795-18' => $this->record(
                'Т-образный вороток с карданом KING TONY 3795-18, 3/8″, 455 мм',
                'Mâner în T cu articulație cardanică KING TONY 3795-18, 3/8″, 455 mm',
                'Т-образный вороток KING TONY 3795-18 с карданным шарниром 90° предназначен для торцевых головок с приводом 3/8″. Вороток изготовлен из хромованадиевой стали; общая длина — 455 мм, ширина Т-образной рукоятки — 200 мм, масса — 425 г.',
                'Mânerul în T KING TONY 3795-18, cu articulație cardanică la 90°, este destinat capetelor tubulare cu antrenare de 3/8″. Este fabricat din oțel crom-vanadiu; lungimea totală este de 455 mm, lățimea mânerului în T de 200 mm, iar greutatea de 425 g.',
                ['Тип' => 'Т-образный вороток с карданом', 'Привод' => '3/8″', 'Угол шарнира' => '90°', 'Длина' => '455 мм', 'Ширина рукоятки' => '200 мм', 'Вес' => '425 г', 'Материал' => 'Хромованадиевая сталь'],
                '/images/catalog-reviewed/king-tony-recovery-2-sourced/3795',
                '3795',
                'https://www.kingtony.com/upload/products/3795.png',
                'https://www.kingtony.com/upload/file/pdf/2024/348.pdf',
                'official_manufacturer',
                100,
            ),
            '577302D1' => $this->record(
                'Скользящий Т-образный вороток 2-в-1 KING TONY 577302D1, 1/4″',
                'Mâner glisant în T 2-în-1 KING TONY 577302D1, 1/4″',
                'Скользящий Т-образный вороток KING TONY 577302D1 работает с торцевыми головками 1/4″ и шестигранными битами 1/4″. Алюминиевая втулка облегчает быстрое вращение. Длина стержня — 250 мм, длина поперечной рукоятки — 150 мм, масса — 218 г, максимальный крутящий момент — 100 Н·м.',
                'Mânerul glisant în T KING TONY 577302D1 poate fi utilizat cu capete tubulare de 1/4″ și biți hexagonali de 1/4″. Manșonul din aluminiu permite rotirea rapidă. Lungimea tijei este de 250 mm, lungimea mânerului transversal de 150 mm, greutatea de 218 g, iar cuplul maxim de 100 N·m.',
                ['Тип' => 'Скользящий Т-образный вороток 2-в-1', 'Привод для головок' => '1/4″', 'Хвостовик для бит' => '1/4″ HEX', 'Длина стержня' => '250 мм', 'Длина рукоятки' => '150 мм', 'Вес' => '218 г', 'Максимальный момент' => '100 Н·м', 'Втулка' => 'Алюминиевая'],
                '/images/catalog-reviewed/king-tony-recovery-2-sourced/577302',
                '577302',
                'https://www.kingtony.com/upload/products/577302.png',
                'https://www.kingtony.com/e_catalog_rock/files/basic-html/page11.html',
                'official_manufacturer',
                100,
            ),
            '4755-10G' => $this->record(
                'Реверсивная трещотка KING TONY 4755-10G, 72 зуба, 1/2″, 10″',
                'Clichet reversibil KING TONY 4755-10G, 72 de dinți, 1/2″, 10″',
                'Реверсивная трещотка KING TONY 4755-10G с круглой головкой рассчитана на привод 1/2″. Механизм имеет 72 зуба и рабочий угол 5°. Инструмент длиной 10″ имеет полированную поверхность и резиновую рукоятку; масса — 1,26 фунта (около 572 г).',
                'Clichetul reversibil KING TONY 4755-10G cu cap rotund este destinat antrenării de 1/2″. Mecanismul are 72 de dinți și un unghi de lucru de 5°. Scula de 10″ are suprafață lustruită și mâner din cauciuc; greutatea este de 1,26 lb (aproximativ 572 g).',
                ['Тип' => 'Реверсивная трещотка с круглой головкой', 'Привод' => '1/2″', 'Количество зубьев' => '72', 'Рабочий угол' => '5°', 'Длина' => '10″', 'Вес' => 'около 572 г', 'Рукоятка' => 'Резиновая', 'Отделка' => 'Полированная'],
                '/images/catalog-reviewed/king-tony-page-crops/4755-10g',
                '4755-10g',
                'https://www.kingtony.com/e_catalog_ktpro/files/mobile/33.jpg',
                'https://www.kingtony.com/e_catalog_ktpro/files/basic-html/page33.html',
                'official_manufacturer',
                100,
            ),
            '68HB-10' => $this->record(
                'Щипцы KING TONY 68HB-10 для внутренних стопорных колец, изогнутые, 241 мм',
                'Clește curbat KING TONY 68HB-10 pentru siguranțe interioare, 241 mm',
                'Изогнутые щипцы KING TONY 68HB-10 европейского типа предназначены для внутренних стопорных колец диаметром 40–100 мм. Общая длина — 9-1/2″ / 241 мм, диаметр наконечников — 2,3 мм, масса — 317 г. Рукоятки покрыты PVC; исполнение соответствует DIN 5254 и DIN 5256.',
                'Cleștele curbat KING TONY 68HB-10, de tip european, este destinat siguranțelor interioare cu diametrul de 40–100 mm. Lungimea totală este de 9-1/2″ / 241 mm, diametrul vârfurilor de 2,3 mm, iar greutatea de 317 g. Mânerele sunt acoperite cu PVC; execuția respectă DIN 5254 și DIN 5256.',
                ['Тип' => 'Щипцы для внутренних стопорных колец', 'Форма наконечников' => 'Изогнутые', 'Диапазон колец' => '40–100 мм', 'Диаметр наконечников' => '2,3 мм', 'Длина' => '241 мм', 'Вес' => '317 г', 'Покрытие рукояток' => 'PVC', 'Стандарт' => 'DIN 5254 / DIN 5256'],
                '/images/catalog-reviewed/king-tony-recovery-2-sourced/68hb',
                '68hb',
                'https://www.kingtony.com/upload/products/68HB.png',
                'https://www.kingtony.com/e_catalog/files/basic-html/page388.html',
                'official_manufacturer',
                100,
            ),
            '9TA52A' => $this->record(
                'Аккумуляторный налобный фонарь KING TONY 9TA52A, 3 Вт, 100/280 лм',
                'Lanternă frontală reîncărcabilă KING TONY 9TA52A, 3 W, 100/280 lm',
                'Налобный фонарь KING TONY 9TA52A оснащён COB LED мощностью 3 Вт и двумя режимами: 100 лм до 5,5 ч и 280 лм до 2 ч. Аккумулятор Li‑Po 3,7 В / 1500 мА·ч заряжается через USB Type‑C примерно за 2,5 ч. Цветовая температура — 6500 K, датчик движения — 10 см, регулировка наклона — 60°, защита — IP65. Длина корпуса — 65 мм, масса — 110 г; версия A предназначена для Европы.',
                'Lanterna frontală KING TONY 9TA52A are un COB LED de 3 W și două moduri: 100 lm până la 5,5 h și 280 lm până la 2 h. Acumulatorul Li‑Po de 3,7 V / 1500 mAh se încarcă prin USB Type‑C în aproximativ 2,5 h. Temperatura de culoare este 6500 K, senzorul de mișcare 10 cm, reglajul 60°, iar protecția IP65. Corpul are 65 mm și 110 g; versiunea A este destinată Europei.',
                ['Тип' => 'Аккумуляторный налобный фонарь', 'Источник света' => 'COB LED', 'Мощность' => '3 Вт', 'Световой поток' => '100 / 280 лм', 'Время работы' => '5,5 / 2 ч', 'Аккумулятор' => 'Li‑Po 3,7 В / 1500 мА·ч', 'Время зарядки' => '2,5 ч', 'Разъём зарядки' => 'USB Type‑C', 'Цветовая температура' => '6500 K', 'Датчик движения' => '10 см', 'Угол наклона' => '60°', 'Степень защиты' => 'IP65', 'Длина' => '65 мм', 'Вес' => '110 г', 'Регион' => 'Европа'],
                '/images/catalog-reviewed/king-tony-recovery-2-sourced/9ta52',
                '9ta52',
                'https://www.kingtony.com/upload/products/9TA52.png',
                'https://www.kingtony.com/e_catalog/files/basic-html/page545.html',
                'official_manufacturer',
                100,
            ),
            '9TA33A' => $this->record(
                'Магнитный инспекционный светильник KING TONY 9TA33A, 10 Вт, 400/1000 лм',
                'Lampă de inspecție magnetică KING TONY 9TA33A, 10 W, 400/1000 lm',
                'Аккумуляторный инспекционный светильник KING TONY 9TA33A оснащён COB LED мощностью 10 Вт и режимами 400 лм до 5,5 ч и 1000 лм до 2,2 ч. Литий‑ионный аккумулятор 7,4 В / 2600 мА·ч заряжается примерно за 4,5 ч. Магнитное основание поворачивается на 170°, корпус — на 360°; предусмотрены два поворотных крюка. Длина — 500 мм, масса — 487 г, защита — IP20 и IK07.',
                'Lampa de inspecție reîncărcabilă KING TONY 9TA33A are un COB LED de 10 W și moduri de 400 lm până la 5,5 h și 1000 lm până la 2,2 h. Acumulatorul Li‑ion de 7,4 V / 2600 mAh se încarcă în aproximativ 4,5 h. Baza magnetică se rotește la 170°, corpul la 360° și există două cârlige pivotante. Lungimea este de 500 mm, greutatea de 487 g, protecția IP20 și IK07.',
                ['Тип' => 'Аккумуляторный инспекционный светильник', 'Источник света' => 'COB LED', 'Мощность' => '10 Вт', 'Световой поток' => '400 / 1000 лм', 'Время работы' => '5,5 / 2,2 ч', 'Аккумулятор' => 'Li‑ion 7,4 В / 2600 мА·ч', 'Время зарядки' => '4,5 ч', 'Цветовая температура' => '6500 K', 'Поворот основания' => '170°', 'Поворот корпуса' => '360°', 'Крюки' => '2 поворотных', 'Длина' => '500 мм', 'Вес' => '487 г', 'Защита' => 'IP20 / IK07'],
                '/images/catalog-reviewed/king-tony-recovery-2-sourced/9ta33',
                '9ta33',
                'https://www.kingtony.com/upload/products/9TA33.png',
                'https://catalog.kingtony.eu/gb/4044',
                'official_manufacturer_region',
                100,
            ),
        ];

        DB::transaction(function () use ($records): void {
            foreach ($records as $sku => $record) {
                $this->updateProduct($sku, $record);
            }
        });
    }

    private function record(string $nameRu, string $nameRo, string $descriptionRu, string $descriptionRo, array $attributes, string $directory, string $imageSlug, string $imageUrl, string $pageUrl, string $sourceType, int $confidence): array
    {
        return compact('nameRu', 'nameRo', 'descriptionRu', 'descriptionRo', 'attributes', 'directory', 'imageSlug', 'imageUrl', 'pageUrl', 'sourceType', 'confidence');
    }

    private function updateProduct(string $sku, array $record): void
    {
        $main = $record['directory'].'/'.$record['imageSlug'].'-main.webp';
        $preview = $record['directory'].'/'.$record['imageSlug'].'-preview.webp';
        $thumb = $record['directory'].'/'.$record['imageSlug'].'-thumb.webp';
        $absoluteMain = public_path(ltrim($main, '/'));
        if (! is_file($absoluteMain) || ! is_file(public_path(ltrim($preview, '/'))) || ! is_file(public_path(ltrim($thumb, '/')))) {
            return;
        }

        $product = DB::table('products')->where('sku', $sku)->first(['id', 'source_parser_item_id', 'parser_source_urls']);
        if (! $product) {
            return;
        }

        $now = now();
        $attributes = json_encode($record['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $pageDomain = strtolower((string) parse_url($record['pageUrl'], PHP_URL_HOST));
        $imageDomain = strtolower((string) parse_url($record['imageUrl'], PHP_URL_HOST));
        $sourceUrls = json_decode((string) $product->parser_source_urls, true);
        $sourceUrls = is_array($sourceUrls) ? $sourceUrls : [];
        $sourceUrls[] = $record['pageUrl'];

        DB::table('products')->where('id', $product->id)->update([
            'name' => $record['nameRu'], 'name_ru' => $record['nameRu'], 'name_ro' => $record['nameRo'],
            'short_description' => $record['descriptionRu'], 'short_description_ru' => $record['descriptionRu'], 'short_description_ro' => $record['descriptionRo'],
            'description' => $record['descriptionRu'], 'description_ru' => $record['descriptionRu'], 'description_ro' => $record['descriptionRo'],
            'attributes' => $attributes, 'main_image' => $main, 'gallery' => json_encode([$main], JSON_UNESCAPED_SLASHES),
            'needs_content_review' => false, 'needs_image_review' => false, 'generated_content' => false,
            'parser_source_urls' => json_encode(array_values(array_unique($sourceUrls)), JSON_UNESCAPED_SLASHES),
            'source_url' => $record['pageUrl'], 'source_domain' => $pageDomain, 'source_type' => $record['sourceType'],
            'fallback_source_used' => false, 'needs_source_review' => false, 'source_reviewed_at' => $now,
            'parser_confidence' => $record['confidence'], 'updated_at' => $now,
        ]);

        if ($product->source_parser_item_id) {
            DB::table('product_parser_image_assets')->where('parser_item_id', $product->source_parser_item_id)->update(['is_selected' => false, 'is_main' => false, 'updated_at' => $now]);
            DB::table('product_parser_image_assets')->updateOrInsert(
                ['parser_item_id' => $product->source_parser_item_id, 'source_url' => $record['imageUrl']],
                ['source_domain' => $imageDomain, 'original_path' => null, 'processed_path' => $main, 'preview_path' => $preview, 'thumb_path' => $thumb, 'width' => 1200, 'height' => 1200, 'mime_type' => 'image/webp', 'status' => 'processed', 'is_selected' => true, 'is_main' => true, 'has_watermark' => true, 'background_removed' => false, 'background_removal_failed' => false, 'needs_review' => false, 'error_message' => null, 'updated_at' => $now, 'created_at' => $now]
            );
            DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                'name_ru' => $record['nameRu'], 'name_ro' => $record['nameRo'], 'short_description_ru' => $record['descriptionRu'], 'short_description_ro' => $record['descriptionRo'], 'description_ru' => $record['descriptionRu'], 'description_ro' => $record['descriptionRo'], 'found_title' => $record['nameRu'], 'found_description' => $record['descriptionRu'], 'found_specs_json' => $attributes,
                'selected_images_json' => json_encode([$record['imageUrl']], JSON_UNESCAPED_SLASHES), 'processed_images_json' => json_encode([$main], JSON_UNESCAPED_SLASHES), 'image_source_type' => 'official_manufacturer_family',
                'official_source_url' => $record['pageUrl'], 'official_source_domain' => $pageDomain, 'official_source_confidence' => $record['confidence'], 'fallback_source_used' => false, 'source_match_confidence' => $record['confidence'],
                'needs_content_review' => false, 'needs_source_review' => false, 'source_reviewed_at' => $now, 'needs_image_review' => false, 'image_reviewed_at' => $now, 'generated_content' => false, 'updated_at' => $now,
            ]);
            DB::table('product_parser_sources')->updateOrInsert(
                ['parser_item_id' => $product->source_parser_item_id, 'url' => $record['pageUrl']],
                ['domain' => $pageDomain, 'title' => 'Verified KING TONY source for '.$sku, 'snippet' => 'Exact item or official manufacturer family reviewed manually.', 'source_type' => $record['sourceType'], 'confidence_score' => $record['confidence'], 'raw_data_json' => json_encode(['sku' => $sku, 'verification' => 'manual_exact_or_official_family'], JSON_UNESCAPED_SLASHES), 'created_at' => $now, 'updated_at' => $now]
            );
        }

        DB::table('product_images')->where('product_id', $product->id)->delete();
        DB::table('product_images')->insert(['product_id' => $product->id, 'path' => $main, 'alt' => $record['nameRu'], 'sort_order' => 1, 'source_url' => $record['imageUrl'], 'source_page_url' => $record['pageUrl'], 'source_domain' => $imageDomain, 'is_official' => $imageDomain === 'www.kingtony.com', 'mime_type' => 'image/webp', 'width' => 1200, 'height' => 1200, 'file_size' => filesize($absoluteMain) ?: null, 'created_at' => $now, 'updated_at' => $now]);
    }

    public function down(): void
    {
        // Curated manufacturer-backed catalog repairs are intentionally irreversible.
    }
};
