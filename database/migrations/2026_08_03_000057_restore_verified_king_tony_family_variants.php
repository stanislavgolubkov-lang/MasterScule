<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $records = array_merge(
            $this->ratchetRecords(),
            $this->handSocketRecords(),
            $this->impactSocketRecords(),
            $this->electricalRecords(),
        );

        DB::transaction(function () use ($records): void {
            foreach ($records as $sku => $record) {
                $this->updateProduct($sku, $record);
            }
        });
    }

    private function ratchetRecords(): array
    {
        return [
            '4762-10GUS' => $this->record(
                'Трещотка KING TONY 4762-10GUS, 32 зуба, привод 1/2″, 250 мм',
                'Clichet KING TONY 4762-10GUS, 32 de dinți, antrenare 1/2″, 250 mm',
                'Реверсивная трещотка KING TONY 4762-10GUS с быстрым сбросом головки рассчитана на привод 1/2″. Механизм имеет 32 зуба, рабочий угол 11° и двухзубую собачку. Общая длина — 250 мм, масса — 536 г; исполнение соответствует DIN 3122.',
                'Clichetul reversibil KING TONY 4762-10GUS cu eliberare rapidă este destinat antrenării de 1/2″. Mecanismul are 32 de dinți, un unghi de lucru de 11° și clichet cu doi dinți. Lungimea totală este de 250 mm, greutatea de 536 g, iar execuția respectă DIN 3122.',
                ['Тип' => 'Реверсивная трещотка с быстрым сбросом', 'Привод' => '1/2″', 'Количество зубьев' => '32', 'Рабочий угол' => '11°', 'Длина' => '250 мм', 'Вес' => '536 г', 'Стандарт' => 'DIN 3122'],
                '/images/catalog-reviewed/king-tony-families/4762g',
                '4762g',
                'https://www.kingtony.com/upload/products/4762G.png',
                'https://www.kingtony.com/e_catalog/files/basic-html/page221.html',
                true,
            ),
        ];
    }

    private function handSocketRecords(): array
    {
        $records = [];
        foreach ([
            '633023MUS' => [23, '34,0', '35,7', 16, 51, 210],
            '633028MUS' => [28, '39,0', '36,0', 19, 52, 220],
            '633029MUS' => [29, '40,0', '38,0', 19, 52, 245],
        ] as $sku => [$size, $d1, $d2, $depth, $length, $weight]) {
            $records[$sku] = $this->record(
                "Торцевая головка KING TONY {$sku}, 3/4″, 12 граней, {$size} мм",
                "Cap tubular KING TONY {$sku}, 3/4″, 12 laturi, {$size} mm",
                "Стандартная 12-гранная торцевая головка KING TONY {$sku} размером {$size} мм предназначена для ручного привода 3/4″. Головка изготовлена из хромованадиевой стали, отполирована и хромирована; соответствует DIN 3124 и ISO 2725-1. Наружные диаметры D1/D2 — {$d1}/{$d2} мм, рабочая глубина — {$depth} мм, общая длина — {$length} мм, масса — {$weight} г.",
                "Capul tubular standard cu 12 laturi KING TONY {$sku}, de {$size} mm, este destinat antrenării manuale de 3/4″. Este fabricat din oțel crom-vanadiu, lustruit și cromat, conform DIN 3124 și ISO 2725-1. Diametrele exterioare D1/D2 sunt {$d1}/{$d2} mm, adâncimea de lucru {$depth} mm, lungimea totală {$length} mm, iar greutatea {$weight} g.",
                ['Тип' => 'Стандартная торцевая головка', 'Профиль' => '12 граней', 'Привод' => '3/4″', 'Размер' => "{$size} мм", 'D1' => "{$d1} мм", 'D2' => "{$d2} мм", 'Рабочая глубина' => "{$depth} мм", 'Длина' => "{$length} мм", 'Вес' => "{$weight} г", 'Материал' => 'Хромованадиевая сталь', 'Стандарт' => 'DIN 3124 / ISO 2725-1'],
                '/images/catalog-reviewed/king-tony-families/6330m',
                '6330m',
                'https://www.kingtony.com/upload/products/6330M.png',
                'https://www.kingtony.com/e_catalog/files/basic-html/page239.html',
                true,
            );
        }

        foreach ([
            '833038MUS' => [38, 54, 48, 26, 65, 536],
            '833058MUS' => [58, 79, 57, 42, 83, 1263],
        ] as $sku => [$size, $d1, $d2, $depth, $length, $weight]) {
            $records[$sku] = $this->record(
                "Торцевая головка KING TONY {$sku}, 1″, 12 граней, {$size} мм",
                "Cap tubular KING TONY {$sku}, 1″, 12 laturi, {$size} mm",
                "Стандартная 12-гранная торцевая головка KING TONY {$sku} размером {$size} мм предназначена для ручного привода 1″. Головка изготовлена из хромованадиевой стали, отполирована и хромирована; соответствует DIN 3124 и ISO 2725-1. Наружные диаметры D1/D2 — {$d1}/{$d2} мм, рабочая глубина — {$depth} мм, общая длина — {$length} мм, масса — {$weight} г.",
                "Capul tubular standard cu 12 laturi KING TONY {$sku}, de {$size} mm, este destinat antrenării manuale de 1″. Este fabricat din oțel crom-vanadiu, lustruit și cromat, conform DIN 3124 și ISO 2725-1. Diametrele exterioare D1/D2 sunt {$d1}/{$d2} mm, adâncimea de lucru {$depth} mm, lungimea totală {$length} mm, iar greutatea {$weight} g.",
                ['Тип' => 'Стандартная торцевая головка', 'Профиль' => '12 граней', 'Привод' => '1″', 'Размер' => "{$size} мм", 'D1' => "{$d1} мм", 'D2' => "{$d2} мм", 'Рабочая глубина' => "{$depth} мм", 'Длина' => "{$length} мм", 'Вес' => "{$weight} г", 'Материал' => 'Хромованадиевая сталь', 'Стандарт' => 'DIN 3124 / ISO 2725-1'],
                '/images/catalog-reviewed/king-tony-families/8330m',
                '8330m',
                'https://www.kingtony.com/upload/products/8330M.png',
                'https://www.kingtony.com/e_catalog/files/basic-html/page245.html',
                true,
            );
        }

        return $records;
    }

    private function impactSocketRecords(): array
    {
        $records = [];
        foreach ([
            '653517MUS' => [17, '29,0', 44, 10, 50, 309],
            '653521MUS' => [21, '35,0', 44, 13, 50, 336],
            '653528MUS' => [28, '44,0', 44, 18, 53, 390],
            '653535MUS' => [35, '52,0', 44, 22, 57, 499],
            '653541MUS' => [41, '59,0', 44, 24, 58, 584],
        ] as $sku => [$size, $d1, $d2, $depth, $length, $weight]) {
            $records[$sku] = $this->record(
                "Ударная торцевая головка KING TONY {$sku}, 3/4″, {$size} мм",
                "Cap tubular de impact KING TONY {$sku}, 3/4″, {$size} mm",
                "Стандартная ударная торцевая головка KING TONY {$sku} размером {$size} мм предназначена для привода 3/4″. Головка изготовлена из хромомолибденовой стали и соответствует DIN 3121. Наружные диаметры D1/D2 — {$d1}/{$d2} мм, рабочая глубина — {$depth} мм, общая длина — {$length} мм, масса — {$weight} г.",
                "Capul tubular standard de impact KING TONY {$sku}, de {$size} mm, este destinat antrenării de 3/4″. Este fabricat din oțel crom-molibden și respectă DIN 3121. Diametrele exterioare D1/D2 sunt {$d1}/{$d2} mm, adâncimea de lucru {$depth} mm, lungimea totală {$length} mm, iar greutatea {$weight} g.",
                ['Тип' => 'Стандартная ударная торцевая головка', 'Привод' => '3/4″', 'Размер' => "{$size} мм", 'D1' => "{$d1} мм", 'D2' => "{$d2} мм", 'Рабочая глубина' => "{$depth} мм", 'Длина' => "{$length} мм", 'Вес' => "{$weight} г", 'Материал' => 'Хромомолибденовая сталь', 'Стандарт' => 'DIN 3121'],
                '/images/catalog-reviewed/king-tony-recovery-sourced/6535m',
                '6535m',
                'https://www.kingtony.com/upload/products/6535M.png',
                'https://www.kingtony.com/e_catalog/files/basic-html/page284.html',
                true,
            );
        }

        $records['443526MUS'] = $this->record(
            'Глубокая ударная торцевая головка KING TONY 443526MUS, 1/2″, 26 мм',
            'Cap tubular lung de impact KING TONY 443526MUS, 1/2″, 26 mm',
            'Глубокая ударная торцевая головка KING TONY 443526MUS размером 26 мм предназначена для привода 1/2″. Головка изготовлена из хромомолибденовой стали и соответствует DIN 3121. Наружные диаметры D1/D2 — 37,8/30,0 мм, рабочая глубина — 27 мм, общая длина — 80 мм, масса — 367 г.',
            'Capul tubular lung de impact KING TONY 443526MUS, de 26 mm, este destinat antrenării de 1/2″. Este fabricat din oțel crom-molibden și respectă DIN 3121. Diametrele exterioare D1/D2 sunt 37,8/30,0 mm, adâncimea de lucru 27 mm, lungimea totală 80 mm, iar greutatea 367 g.',
            ['Тип' => 'Глубокая ударная торцевая головка', 'Привод' => '1/2″', 'Размер' => '26 мм', 'D1' => '37,8 мм', 'D2' => '30,0 мм', 'Рабочая глубина' => '27 мм', 'Длина' => '80 мм', 'Вес' => '367 г', 'Материал' => 'Хромомолибденовая сталь', 'Стандарт' => 'DIN 3121'],
            '/images/catalog-reviewed/king-tony-recovery-sourced/4435m',
            '4435m',
            'https://www.kingtony.com/upload/products/4435M.png',
            'https://www.kingtony.com/e_catalog/files/basic-html/page273.html',
            true,
        );

        return $records;
    }

    private function electricalRecords(): array
    {
        return [
            '715065S1US' => $this->record(
                'Магнитная бита KING TONY 715065S1US, SL 6,5 × 1,2 мм, 1/4″, 50 мм',
                'Bit magnetic KING TONY 715065S1US, SL 6,5 × 1,2 mm, 1/4″, 50 mm',
                'Магнитная шлицевая бита KING TONY 715065S1US изготовлена из легированной стали S2. Ширина наконечника — 6,5 мм, толщина — 1,2 мм, длина — 50 мм, шестигранный хвостовик — 1/4″. Поверхность фосфатирована и защищена антикоррозионным маслом; исполнение соответствует DIN ISO 2351-1.',
                'Bitul magnetic drept KING TONY 715065S1US este fabricat din oțel aliat S2. Lățimea vârfului este de 6,5 mm, grosimea de 1,2 mm, lungimea de 50 mm, iar tija hexagonală de 1/4″. Suprafața este fosfatată și protejată cu ulei anticoroziv; execuția respectă DIN ISO 2351-1.',
                ['Тип' => 'Магнитная шлицевая бита', 'Профиль' => 'SL', 'Ширина наконечника' => '6,5 мм', 'Толщина наконечника' => '1,2 мм', 'Хвостовик' => '1/4″', 'Длина' => '50 мм', 'Материал' => 'Легированная сталь S2', 'Стандарт' => 'DIN ISO 2351-1'],
                '/images/catalog-reviewed/king-tony-families/7150s',
                '7150s',
                'https://www.kingtony.com/upload/products/7150S.png',
                'https://www.kingtony.com/e_catalog/files/basic-html/page431.html',
                true,
            ),
            '67G1-09US' => $this->record(
                'Кримпер KING TONY 67G1-09US для F-разъёмов кабеля RG59/RG6, 222 мм',
                'Clește de sertizat KING TONY 67G1-09US pentru conectori F RG59/RG6, 222 mm',
                'Кримпер KING TONY 67G1-09US с храповым механизмом и сменной матрицей предназначен для обжима F-разъёмов кабеля RG59 и RG6. Размеры матрицы — 0,068″, 0,262″, 0,324″ и 0,360″ (1,72 / 6,65 / 8,23 / 9,14 мм). Общая длина инструмента — 222 мм, масса — 480 г; сменная матрица — 67G1P01.',
                'Cleștele de sertizat cu clichet KING TONY 67G1-09US și matriță interschimbabilă este destinat conectorilor F pentru cabluri RG59 și RG6. Dimensiunile matriței sunt 0,068″, 0,262″, 0,324″ și 0,360″ (1,72 / 6,65 / 8,23 / 9,14 mm). Lungimea totală este de 222 mm, greutatea de 480 g, iar matrița de schimb este 67G1P01.',
                ['Тип' => 'Кримпер с храповым механизмом', 'Применение' => 'F-разъёмы RG59 / RG6', 'Размеры матрицы' => '1,72 / 6,65 / 8,23 / 9,14 мм', 'Сменная матрица' => '67G1P01', 'Длина' => '222 мм', 'Вес' => '480 г'],
                '/images/catalog-reviewed/king-tony-exact-sourced/67g1-09',
                '67g1-09',
                'https://www.kingtony.com/upload/products/67G1-09.png',
                'https://www.kingtony.com/upload/file/pdf/2019/405.pdf',
                false,
            ),
            '6AB31-85US' => $this->record(
                'Многоцелевые ножницы электрика KING TONY 6AB31-85US, 214 мм',
                'Foarfecă multifuncțională pentru electrician KING TONY 6AB31-85US, 214 mm',
                'Многоцелевые ножницы электрика KING TONY 6AB31-85US длиной 214 мм предназначены для монтажных и сервисных работ. Карточка использует точное архивное изображение производителя с маркировкой модели 6AB31-85.',
                'Foarfeca multifuncțională pentru electrician KING TONY 6AB31-85US, cu lungimea de 214 mm, este destinată lucrărilor de montaj și service. Fișa utilizează imaginea exactă din arhiva producătorului, marcată cu modelul 6AB31-85.',
                ['Тип' => 'Многоцелевые ножницы электрика', 'Длина' => '214 мм', 'Модель производителя' => '6AB31-85'],
                '/images/catalog-reviewed/king-tony-exact-sourced/6ab31-85',
                '6ab31-85',
                'https://www.kingtony.com/upload/products/6AB31-85.png',
                'https://www.kingtony.com/upload/products/6AB31-85.png',
                false,
            ),
            '6741-06US' => $this->record(
                'Клещи для зачистки и резки проводов KING TONY 6741-06US, 0,5–5,5 мм²',
                'Clește pentru dezizolat și tăiat conductoare KING TONY 6741-06US, 0,5–5,5 mm²',
                'Клещи KING TONY 6741-06US предназначены для зачистки и резки проводов сечением 0,5 / 0,75 / 1,25 / 2,0 / 3,5 / 5,5 мм². Номинальная длина инструмента — 6″, масса — 0,23 фунта (около 104 г).',
                'Cleștele KING TONY 6741-06US este destinat dezizolării și tăierii conductoarelor cu secțiuni de 0,5 / 0,75 / 1,25 / 2,0 / 3,5 / 5,5 mm². Lungimea nominală este de 6″, iar greutatea de 0,23 lb (aproximativ 104 g).',
                ['Тип' => 'Клещи для зачистки и резки проводов', 'Сечения проводов' => '0,5 / 0,75 / 1,25 / 2,0 / 3,5 / 5,5 мм²', 'Длина' => '6″', 'Вес' => 'около 104 г'],
                '/images/catalog-reviewed/king-tony-recovery-sourced/6741-06',
                '6741-06',
                'https://www.kingtony.com/upload/products/6741-06.png',
                'https://www.kingtony.com/e_catalog_ktpro/files/basic-html/page95.html',
                false,
            ),
        ];
    }

    private function record(
        string $nameRu,
        string $nameRo,
        string $descriptionRu,
        string $descriptionRo,
        array $attributes,
        string $directory,
        string $imageSlug,
        string $imageUrl,
        string $pageUrl,
        bool $familyImage,
    ): array {
        return compact('nameRu', 'nameRo', 'descriptionRu', 'descriptionRo', 'attributes', 'directory', 'imageSlug', 'imageUrl', 'pageUrl', 'familyImage');
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
            'name' => $record['nameRu'],
            'name_ru' => $record['nameRu'],
            'name_ro' => $record['nameRo'],
            'short_description' => $record['descriptionRu'],
            'short_description_ru' => $record['descriptionRu'],
            'short_description_ro' => $record['descriptionRo'],
            'description' => $record['descriptionRu'],
            'description_ru' => $record['descriptionRu'],
            'description_ro' => $record['descriptionRo'],
            'attributes' => $attributes,
            'main_image' => $main,
            'gallery' => json_encode([$main], JSON_UNESCAPED_SLASHES),
            'needs_content_review' => false,
            'needs_image_review' => false,
            'generated_content' => false,
            'parser_source_urls' => json_encode(array_values(array_unique($sourceUrls)), JSON_UNESCAPED_SLASHES),
            'source_url' => $record['pageUrl'],
            'source_domain' => $pageDomain,
            'source_type' => 'official_manufacturer',
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'parser_confidence' => $record['familyImage'] ? 98 : 100,
            'updated_at' => $now,
        ]);

        if ($product->source_parser_item_id) {
            DB::table('product_parser_image_assets')->where('parser_item_id', $product->source_parser_item_id)
                ->update(['is_selected' => false, 'is_main' => false, 'updated_at' => $now]);
            DB::table('product_parser_image_assets')->updateOrInsert(
                ['parser_item_id' => $product->source_parser_item_id, 'source_url' => $record['imageUrl']],
                [
                    'source_domain' => $imageDomain,
                    'original_path' => null,
                    'processed_path' => $main,
                    'preview_path' => $preview,
                    'thumb_path' => $thumb,
                    'width' => 1200,
                    'height' => 1200,
                    'mime_type' => 'image/webp',
                    'status' => 'processed',
                    'is_selected' => true,
                    'is_main' => true,
                    'has_watermark' => true,
                    'background_removed' => false,
                    'background_removal_failed' => false,
                    'needs_review' => false,
                    'error_message' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
            DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                'name_ru' => $record['nameRu'],
                'name_ro' => $record['nameRo'],
                'short_description_ru' => $record['descriptionRu'],
                'short_description_ro' => $record['descriptionRo'],
                'description_ru' => $record['descriptionRu'],
                'description_ro' => $record['descriptionRo'],
                'found_title' => $record['nameRu'],
                'found_description' => $record['descriptionRu'],
                'found_specs_json' => $attributes,
                'selected_images_json' => json_encode([$record['imageUrl']], JSON_UNESCAPED_SLASHES),
                'processed_images_json' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                'image_source_type' => $record['familyImage'] ? 'official_manufacturer_family' : 'official_manufacturer_exact',
                'official_source_url' => $record['pageUrl'],
                'official_source_domain' => $pageDomain,
                'official_source_confidence' => $record['familyImage'] ? 98 : 100,
                'fallback_source_used' => false,
                'source_match_confidence' => $record['familyImage'] ? 98 : 100,
                'needs_content_review' => false,
                'needs_source_review' => false,
                'source_reviewed_at' => $now,
                'needs_image_review' => false,
                'image_reviewed_at' => $now,
                'generated_content' => false,
                'updated_at' => $now,
            ]);
            DB::table('product_parser_sources')->updateOrInsert(
                ['parser_item_id' => $product->source_parser_item_id, 'url' => $record['pageUrl']],
                [
                    'domain' => $pageDomain,
                    'title' => 'Verified KING TONY source for '.$sku,
                    'snippet' => 'Exact model or official catalog family reviewed against the manufacturer publication.',
                    'source_type' => 'official_manufacturer',
                    'confidence_score' => $record['familyImage'] ? 98 : 100,
                    'raw_data_json' => json_encode(['sku' => $sku, 'verification' => $record['familyImage'] ? 'official_family' : 'official_exact'], JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('product_images')->where('product_id', $product->id)->delete();
        DB::table('product_images')->insert([
            'product_id' => $product->id,
            'path' => $main,
            'alt' => $record['nameRu'],
            'sort_order' => 1,
            'source_url' => $record['imageUrl'],
            'source_page_url' => $record['pageUrl'],
            'source_domain' => $imageDomain,
            'is_official' => true,
            'mime_type' => 'image/webp',
            'width' => 1200,
            'height' => 1200,
            'file_size' => filesize($absoluteMain) ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Curated manufacturer-backed catalog repairs are intentionally irreversible.
    }
};
