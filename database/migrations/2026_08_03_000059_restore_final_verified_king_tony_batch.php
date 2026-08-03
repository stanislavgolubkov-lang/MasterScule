<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setAttributes = [
            'Тип' => 'Набор торцевых головок и принадлежностей',
            'Количество предметов' => '27',
            'Приводы' => '1/4″ и 1/2″',
            'Профиль головок' => 'Шестигранный',
            'Головки 1/4″' => '5,5; 6; 7; 8; 9; 10; 11; 12; 13 мм',
            'Головки 1/2″' => '10; 11; 12; 13; 14; 16; 17; 19; 21; 24; 27 мм',
            'Трещотки' => '1/4″ и 1/2″',
            'Материал' => 'Хромованадиевая сталь',
        ];

        $records = [
            '7527MR' => [
                'nameRu' => 'Набор торцевых головок KING TONY 7527MR, 1/4″ и 1/2″, 27 предметов',
                'nameRo' => 'Set de capete tubulare KING TONY 7527MR, 1/4″ și 1/2″, 27 piese',
                'descriptionRu' => 'Профессиональный набор KING TONY 7527MR включает шестигранные торцевые головки 1/4″ размером 5,5–13 мм, головки 1/2″ размером 10–27 мм, две трещотки и принадлежности — всего 27 предметов. Инструменты изготовлены из хромованадиевой стали и размещены в металлическом кейсе.',
                'descriptionRo' => 'Setul profesional KING TONY 7527MR conține capete tubulare hexagonale de 1/4″ de la 5,5 la 13 mm, capete de 1/2″ de la 10 la 27 mm, două clichete și accesorii — 27 de piese în total. Sculele sunt fabricate din oțel crom-vanadiu și sunt livrate într-o cutie metalică.',
                'attributes' => $setAttributes + ['Depozitare' => 'Cutie metalică'],
                'pageUrl' => 'https://miar62.ru/uplouds/catalogs/80/king-tony.pdf',
                'sourceType' => 'official_catalog_mirror',
                'confidence' => 96,
                'images' => [$this->image('king-tony-recovery-3-sourced', '7527MR', 'https://dicon.md/upload/catalog/items/j0osl75yvv.jpg')],
            ],
            '9-7527MR' => [
                'nameRu' => 'Набор торцевых головок KING TONY 9-7527MR в ложементе, 1/4″ и 1/2″, 27 предметов',
                'nameRo' => 'Set de capete tubulare KING TONY 9-7527MR în modul, 1/4″ și 1/2″, 27 piese',
                'descriptionRu' => 'Набор KING TONY 9-7527MR объединяет шестигранные торцевые головки 1/4″ размером 5,5–13 мм, головки 1/2″ размером 10–27 мм, две трещотки и принадлежности — всего 27 предметов. Комплект из хромованадиевой стали размещён в формованном ложементе для инструментальной тележки.',
                'descriptionRo' => 'Setul KING TONY 9-7527MR include capete tubulare hexagonale de 1/4″ de la 5,5 la 13 mm, capete de 1/2″ de la 10 la 27 mm, două clichete și accesorii — 27 de piese în total. Setul din oțel crom-vanadiu este organizat într-un modul termoformat pentru căruciorul de scule.',
                'attributes' => $setAttributes + ['Depozitare' => 'Lojement termoformat pentru cărucior'],
                'pageUrl' => 'https://dicon.md/ro/set-capete-chei-tubulare-si-accesorii-27-buc-king-tony-9-7527mr/',
                'sourceType' => 'verified_exact_distributor',
                'confidence' => 96,
                'images' => [$this->image('king-tony-recovery-3-sourced', '7527MR', 'https://dicon.md/upload/catalog/items/j0osl75yvv.jpg')],
            ],
            '9TV12-020' => [
                'nameRu' => 'Пневматическое устройство для прокачки тормозов KING TONY 9TV12-020, 2 л',
                'nameRo' => 'Dispozitiv pneumatic pentru aerisirea frânelor KING TONY 9TV12-020, 2 l',
                'descriptionRu' => 'Пневматическое устройство KING TONY 9TV12-020 предназначено для замены и удаления тормозной жидкости без утечек. Европейская версия имеет бак 2 л, рабочее давление 40–170 PSI, вход воздуха 1/4″, силиконовый шланг длиной 1 м и универсальный резиновый адаптер. Рабочая температура — от −19 до +60 °C, масса — 1050 г.',
                'descriptionRo' => 'Dispozitivul pneumatic KING TONY 9TV12-020 este destinat înlocuirii și evacuării lichidului de frână fără scurgeri. Versiunea europeană are rezervor de 2 l, presiune de lucru 40–170 PSI, racord de aer de 1/4″, furtun din silicon de 1 m și adaptor universal din cauciuc. Temperatura de lucru este între −19 și +60 °C, iar greutatea este de 1050 g.',
                'attributes' => ['Тип' => 'Пневматическое устройство для прокачки тормозов', 'Версия' => 'Европейская', 'Объём бака' => '2 л', 'Рабочее давление' => '40–170 PSI', 'Рабочая температура' => '−19…+60 °C', 'Вход воздуха' => '1/4″', 'Длина шланга' => '1 м', 'Адаптер' => 'Универсальный резиновый', 'Вес' => '1050 г'],
                'pageUrl' => 'https://www.kingtony.com/upload/file/pdf/2021/590.pdf',
                'sourceType' => 'official_manufacturer',
                'confidence' => 100,
                'images' => [$this->image('king-tony-recovery-3-sourced', '9TV12-020', 'https://shvedik.ru/pictures/product/big/286836_big.png')],
            ],
            '9BU152T' => [
                'nameRu' => 'Ручной шприц для консистентной смазки KING TONY 9BU152T, 500 см³',
                'nameRo' => 'Pompă manuală pentru unsoare KING TONY 9BU152T, 500 cm³',
                'descriptionRu' => 'Рычажный шприц KING TONY 9BU152T предназначен для подачи консистентной смазки при обслуживании автомобилей, машин и сельскохозяйственного оборудования. Вместимость цилиндра — 500 см³, рабочее давление — до 4500 PSI, длина корпуса — 267 мм. В комплект входит жёсткая трубка для точной подачи смазки.',
                'descriptionRo' => 'Pompa manuală cu pârghie KING TONY 9BU152T este destinată aplicării unsorii la întreținerea automobilelor, utilajelor și echipamentelor agricole. Capacitatea cilindrului este de 500 cm³, presiunea de lucru ajunge la 4500 PSI, iar lungimea corpului este de 267 mm. Setul include un tub rigid pentru aplicare precisă.',
                'attributes' => ['Тип' => 'Ручной рычажный шприц для смазки', 'Вместимость' => '500 см³', 'Рабочее давление' => 'до 4500 PSI', 'Длина' => '267 мм', 'Комплектация' => 'Жёсткая трубка'],
                'pageUrl' => 'https://supplyvan.com/kingtony-lubrication-grease-gun-with-tube-9bu152t-500cc-capacity.html',
                'sourceType' => 'verified_exact_distributor',
                'confidence' => 96,
                'images' => [$this->image('king-tony-recovery-3-sourced', '9BU152T', 'https://cdn11.bigcommerce.com/s-z1j8jc5ejl/products/523095/images/717922/kingtony_9bu152t_01__17837.1726073698.386.513.jpg?c=1')],
            ],
            '4405MX' => [
                'nameRu' => 'Набор тонкостенных ударных головок KING TONY 4405MX, 1/2″, 17/19/21 мм, 3 предмета',
                'nameRo' => 'Set capete tubulare de impact cu pereți subțiri KING TONY 4405MX, 1/2″, 17/19/21 mm, 3 piese',
                'descriptionRu' => 'KING TONY 4405MX — набор из трёх глубоких шестигранных ударных головок 1/2″ размером 17, 19 и 21 мм. Тонкостенные головки из хромомолибденовой стали имеют фосфатированное покрытие и наружные пластиковые гильзы, защищающие полированные диски и колёсные гайки. Комплект поставляется в пластиковом кейсе.',
                'descriptionRo' => 'KING TONY 4405MX este un set de trei capete tubulare adânci de impact, hexagonale, cu antrenare de 1/2″ și dimensiuni de 17, 19 și 21 mm. Capetele cu pereți subțiri din oțel crom-molibden au finisaj fosfatat și manșoane exterioare din plastic pentru protejarea jantelor lustruite și a piulițelor. Setul este livrat în cutie din plastic.',
                'attributes' => ['Тип' => 'Набор глубоких тонкостенных ударных головок', 'Привод' => '1/2″', 'Размеры' => '17; 19; 21 мм', 'Количество' => '3', 'Профиль' => 'Шестигранный', 'Материал' => 'Хромомолибденовая сталь', 'Покрытие' => 'Фосфатированное', 'Защита' => 'Наружные пластиковые гильзы', 'Упаковка' => 'Пластиковый кейс'],
                'pageUrl' => 'https://www.king-tony.com.au/product-page/deep-impact-socket-set-1-2-drive-thin-wall-metric',
                'sourceType' => 'official_manufacturer_region',
                'confidence' => 100,
                'images' => [$this->image('king-tony-recovery-3-sourced', '4405MX', 'https://static.wixstatic.com/media/7434c6_6953158f24554dcd8429e633be9d821e~mv2.jpg/v1/fit/w_500,h_500,q_90/file.jpg')],
            ],
            '7CA0411MUS' => [
                'nameRu' => 'Набор трубогиба KING TONY 7CA0411MUS для мягких многослойных труб, 10–22 мм, 11 предметов',
                'nameRo' => 'Set dispozitiv de îndoit țevi KING TONY 7CA0411MUS pentru țevi moi multistrat, 10–22 mm, 11 piese',
                'descriptionRu' => 'Набор KING TONY 7CA0411MUS предназначен для точной гибки мягких медных и многослойных труб под углом до 90°. Сменные гибочные сегменты быстро устанавливаются и возвращаются в исходное положение. В комплект из 11 предметов входят головки для труб 10, 12, 14, 15, 16, 18, 20 и 22 мм, механизм трубогиба и принадлежности.',
                'descriptionRo' => 'Setul KING TONY 7CA0411MUS este destinat îndoirii precise până la 90° a țevilor moi din cupru și a țevilor multistrat. Segmentele de îndoire se schimbă ușor și se resetează rapid. Setul de 11 piese include capete pentru țevi de 10, 12, 14, 15, 16, 18, 20 și 22 mm, mecanismul de îndoire și accesoriile.',
                'attributes' => ['Тип' => 'Набор ручного трубогиба', 'Количество предметов' => '11', 'Диаметры труб' => '10; 12; 14; 15; 16; 18; 20; 22 мм', 'Максимальный угол гибки' => '90°', 'Материал труб' => 'Мягкая медь и многослойные трубы', 'Сменные сегменты' => 'Да', 'Быстрый возврат' => 'Да'],
                'pageUrl' => 'https://www.king-tony.com.au/product-page/tube-bender-kit-soft-copper-11-piece',
                'sourceType' => 'official_manufacturer_region',
                'confidence' => 100,
                'images' => [$this->image('king-tony-recovery-3-sourced', '7CA0411MUS', 'https://static.wixstatic.com/media/7434c6_a58283b1df6849e9ac8d1a15aa721518~mv2.jpg')],
            ],
            '9TA42A-87162' => [
                'nameRu' => 'Рабочий прожектор KING TONY 9TA42A со штативом 87162, 30 Вт, 3000 лм',
                'nameRo' => 'Proiector de lucru KING TONY 9TA42A cu trepied 87162, 30 W, 3000 lm',
                'descriptionRu' => 'Комплект состоит из сетевого рабочего прожектора KING TONY 9TA42A и регулируемого стального штатива 87162. COB LED прожектора мощностью 30 Вт создаёт световой поток 3000 лм при цветовой температуре 7000 K; основание регулируется на 180°, защита — IP54 и IK07. Штатив имеет три места крепления, регулируется по высоте от 700 до 1500 мм, складывается до 700 мм и выдерживает нагрузку до 3 кг.',
                'descriptionRo' => 'Setul este alcătuit din proiectorul de lucru alimentat la rețea KING TONY 9TA42A și trepiedul reglabil din oțel 87162. LED-ul COB de 30 W oferă 3000 lm la 7000 K; baza se reglează la 180°, iar protecția este IP54 și IK07. Trepiedul are trei poziții de montare, înălțime reglabilă între 700 și 1500 mm, lungime pliată de 700 mm și sarcină maximă de 3 kg.',
                'attributes' => ['Тип' => 'Рабочий прожектор со штативом', 'Модель прожектора' => '9TA42A', 'Модель штатива' => '87162', 'Источник света' => 'COB LED', 'Мощность' => '30 Вт', 'Световой поток' => '3000 лм', 'Цветовая температура' => '7000 K', 'Регулировка основания' => '180°', 'Питание' => 'AC 100–240 В', 'Длина кабеля' => '1,8 м', 'Защита прожектора' => 'IP54 / IK07', 'Высота штатива' => '700–1500 мм', 'Длина штатива в сложенном виде' => '700 мм', 'Максимальная нагрузка штатива' => '3 кг', 'Количество мест крепления' => '3'],
                'pageUrl' => 'https://www.bluparts.com.br/_arq/pdf/pdf-bluparts-2023-10-06-10-42-28-.pdf',
                'sourceType' => 'official_manufacturer_region',
                'confidence' => 100,
                'images' => [
                    $this->image('king-tony-recovery-2-sourced', '9TA42', 'https://www.kingtony.com/upload/products/9TA42.png'),
                    $this->image('king-tony-recovery-3-sourced', '87162', 'https://www.kingtony.com/upload/products/87162.png'),
                ],
            ],
            '6CB01US' => [
                'nameRu' => 'Отвёртка-индикатор напряжения KING TONY 6CB01US, 100–500 В',
                'nameRo' => 'Șurubelniță indicator de tensiune KING TONY 6CB01US, 100–500 V',
                'descriptionRu' => 'Отвёртка-индикатор KING TONY 6CB01US предназначена для проверки низкого напряжения в диапазоне 100–500 В. Размер плоского жала — 3 × 0,5 мм, длина стержня — 64 мм, диаметр рукоятки — 7 мм, масса — 10 г.',
                'descriptionRo' => 'Șurubelnița indicator KING TONY 6CB01US este destinată verificării tensiunii joase în intervalul 100–500 V. Vârful plat are 3 × 0,5 mm, tija are 64 mm, diametrul mânerului este de 7 mm, iar greutatea este de 10 g.',
                'attributes' => ['Тип' => 'Отвёртка-индикатор напряжения', 'Диапазон напряжения' => '100–500 В', 'Размер жала' => '3 × 0,5 мм', 'Длина стержня' => '64 мм', 'Диаметр рукоятки' => '7 мм', 'Вес' => '10 г'],
                'pageUrl' => 'https://www.rodacastalia.es/ficheros/kt_utillaje.pdf',
                'sourceType' => 'official_catalog_mirror',
                'confidence' => 98,
                'images' => [$this->image('king-tony-recovery-3-sourced', '6CB01', 'https://www.abcommerces.com/53889-large_default/tournevis-detecteurs-basse-tension.jpg')],
            ],
        ];

        DB::transaction(function () use ($records): void {
            foreach ($records as $sku => $record) {
                $this->updateProduct($sku, $record);
            }
        });
    }

    private function image(string $dataset, string $slug, string $sourceUrl): array
    {
        $slug = strtolower($slug);

        return [
            'main' => "/images/catalog-reviewed/{$dataset}/{$slug}/{$slug}-main.webp",
            'preview' => "/images/catalog-reviewed/{$dataset}/{$slug}/{$slug}-preview.webp",
            'thumb' => "/images/catalog-reviewed/{$dataset}/{$slug}/{$slug}-thumb.webp",
            'sourceUrl' => $sourceUrl,
        ];
    }

    private function updateProduct(string $sku, array $record): void
    {
        foreach ($record['images'] as $image) {
            foreach (['main', 'preview', 'thumb'] as $variant) {
                if (! is_file(public_path(ltrim($image[$variant], '/')))) {
                    return;
                }
            }
        }

        $product = DB::table('products')->where('sku', $sku)->first(['id', 'source_parser_item_id', 'parser_source_urls']);
        if (! $product) {
            return;
        }

        $now = now();
        $mainImage = $record['images'][0]['main'];
        $gallery = array_column($record['images'], 'main');
        $sourceImages = array_column($record['images'], 'sourceUrl');
        $pageDomain = strtolower((string) parse_url($record['pageUrl'], PHP_URL_HOST));
        $sourceUrls = json_decode((string) $product->parser_source_urls, true);
        $sourceUrls = is_array($sourceUrls) ? $sourceUrls : [];
        $sourceUrls[] = $record['pageUrl'];
        $attributes = json_encode($record['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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
            'main_image' => $mainImage,
            'gallery' => json_encode($gallery, JSON_UNESCAPED_SLASHES),
            'needs_content_review' => false,
            'needs_image_review' => false,
            'generated_content' => false,
            'parser_source_urls' => json_encode(array_values(array_unique($sourceUrls)), JSON_UNESCAPED_SLASHES),
            'source_url' => $record['pageUrl'],
            'source_domain' => $pageDomain,
            'source_type' => $record['sourceType'],
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'parser_confidence' => $record['confidence'],
            'updated_at' => $now,
        ]);

        if ($product->source_parser_item_id) {
            DB::table('product_parser_image_assets')->where('parser_item_id', $product->source_parser_item_id)->update(['is_selected' => false, 'is_main' => false, 'updated_at' => $now]);
            foreach ($record['images'] as $index => $image) {
                $imageDomain = strtolower((string) parse_url($image['sourceUrl'], PHP_URL_HOST));
                DB::table('product_parser_image_assets')->updateOrInsert(
                    ['parser_item_id' => $product->source_parser_item_id, 'source_url' => $image['sourceUrl']],
                    ['source_domain' => $imageDomain, 'original_path' => null, 'processed_path' => $image['main'], 'preview_path' => $image['preview'], 'thumb_path' => $image['thumb'], 'width' => 1200, 'height' => 1200, 'mime_type' => 'image/webp', 'status' => 'processed', 'is_selected' => true, 'is_main' => $index === 0, 'has_watermark' => true, 'background_removed' => false, 'background_removal_failed' => false, 'needs_review' => false, 'error_message' => null, 'updated_at' => $now, 'created_at' => $now]
                );
            }

            DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                'name_ru' => $record['nameRu'], 'name_ro' => $record['nameRo'],
                'short_description_ru' => $record['descriptionRu'], 'short_description_ro' => $record['descriptionRo'],
                'description_ru' => $record['descriptionRu'], 'description_ro' => $record['descriptionRo'],
                'found_title' => $record['nameRu'], 'found_description' => $record['descriptionRu'], 'found_specs_json' => $attributes,
                'selected_images_json' => json_encode($sourceImages, JSON_UNESCAPED_SLASHES),
                'processed_images_json' => json_encode($gallery, JSON_UNESCAPED_SLASHES),
                'image_source_type' => 'verified_exact_product',
                'official_source_url' => $record['pageUrl'], 'official_source_domain' => $pageDomain,
                'official_source_confidence' => $record['confidence'], 'fallback_source_used' => false,
                'source_match_confidence' => $record['confidence'], 'needs_content_review' => false,
                'needs_source_review' => false, 'source_reviewed_at' => $now,
                'needs_image_review' => false, 'image_reviewed_at' => $now,
                'generated_content' => false, 'updated_at' => $now,
            ]);

            DB::table('product_parser_sources')->updateOrInsert(
                ['parser_item_id' => $product->source_parser_item_id, 'url' => $record['pageUrl']],
                ['domain' => $pageDomain, 'title' => 'Verified KING TONY source for '.$sku, 'snippet' => 'Exact product data and media reviewed manually.', 'source_type' => $record['sourceType'], 'confidence_score' => $record['confidence'], 'raw_data_json' => json_encode(['sku' => $sku, 'verification' => 'manual_exact_product'], JSON_UNESCAPED_SLASHES), 'created_at' => $now, 'updated_at' => $now]
            );
        }

        DB::table('product_images')->where('product_id', $product->id)->delete();
        foreach ($record['images'] as $index => $image) {
            $imageDomain = strtolower((string) parse_url($image['sourceUrl'], PHP_URL_HOST));
            DB::table('product_images')->insert([
                'product_id' => $product->id,
                'path' => $image['main'],
                'alt' => $record['nameRu'],
                'sort_order' => $index + 1,
                'source_url' => $image['sourceUrl'],
                'source_page_url' => $record['pageUrl'],
                'source_domain' => $imageDomain,
                'is_official' => in_array($imageDomain, ['www.kingtony.com', 'static.wixstatic.com'], true),
                'mime_type' => 'image/webp',
                'width' => 1200,
                'height' => 1200,
                'file_size' => filesize(public_path(ltrim($image['main'], '/'))) ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Curated manufacturer-backed catalog repairs are intentionally irreversible.
    }
};
