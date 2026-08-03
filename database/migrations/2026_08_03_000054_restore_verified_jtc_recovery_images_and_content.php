<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $images = require database_path('data/reviewed-jtc-recovery-images.php');
        unset($images['JTC-1322-S1']);

        $content = [
            'JTC-1249' => [
                'name_ru' => 'Беспроводной индикатор напряжения JTC-1249, 3–28 В DC',
                'name_ro' => 'Tester de tensiune fără fir JTC-1249, 3–28 V DC',
                'description_ru' => 'Индикатор JTC-1249 предназначен для проверки цепей постоянного тока бортовой сети автомобиля, поиска коротких замыканий и обрывов. Диапазон измерения — 3–28 В DC. Металлический корпус и защитный колпачок щупа рассчитаны на работу в автосервисе. В комплект входит батарея; прибор нельзя применять в цепях переменного тока.',
                'description_ro' => 'Testerul JTC-1249 este destinat verificării circuitelor de curent continuu ale instalației electrice auto și localizării scurtcircuitelor sau întreruperilor. Domeniul de măsurare este 3–28 V DC. Carcasa metalică și capacul de protecție al sondei sunt potrivite pentru utilizarea în service. Bateria este inclusă; aparatul nu se utilizează în circuite de curent alternativ.',
                'attributes' => ['Тип' => 'Индикатор напряжения', 'Диапазон' => '3–28 В DC', 'Применение' => 'Электрооборудование автомобиля', 'Корпус' => 'Металлический', 'Комплектация' => 'Индикатор, батарея'],
            ],
            'JTC-4145' => [
                'name_ru' => 'Съёмник ТНВД JTC-4145 для BMW N47/N57',
                'name_ro' => 'Extractor pompă de înaltă presiune JTC-4145 pentru BMW N47/N57',
                'description_ru' => 'Съёмник JTC-4145 предназначен для демонтажа топливного насоса высокого давления на дизельных двигателях BMW N47, N47S, N57 и N57S. Соответствует назначению специнструментов BMW 11 8 740, 11 8 741 и 11 8 742. Модель снята с производства; производитель предлагает JTC-6816 как замену.',
                'description_ro' => 'Extractorul JTC-4145 este destinat demontării pompei de combustibil de înaltă presiune la motoarele diesel BMW N47, N47S, N57 și N57S. Corespunde utilizării sculelor speciale BMW 11 8 740, 11 8 741 și 11 8 742. Modelul nu se mai produce; JTC-6816 este înlocuitorul indicat de producător.',
                'attributes' => ['Тип' => 'Съёмник ТНВД', 'Марка автомобиля' => 'BMW', 'Двигатели' => 'N47, N47S, N57, N57S', 'OEM-назначение' => '11 8 740 / 11 8 741 / 11 8 742', 'Статус' => 'Снята с производства', 'Замена' => 'JTC-6816'],
            ],
            'JTC-4510' => [
                'name_ru' => 'Приспособление для заправки охлаждающей жидкости JTC-4510',
                'name_ro' => 'Dispozitiv pentru umplerea sistemului de răcire JTC-4510',
                'description_ru' => 'Приспособление JTC-4510 обеспечивает аккуратную заправку радиатора и помогает предотвратить разлив охлаждающей жидкости. Комплект переходников рассчитан на большинство легковых автомобилей и лёгких грузовиков.',
                'description_ro' => 'Dispozitivul JTC-4510 permite umplerea controlată a radiatorului și ajută la prevenirea vărsării lichidului de răcire. Setul de adaptoare este potrivit pentru majoritatea autoturismelor și autoutilitarelor ușoare.',
                'attributes' => ['Тип' => 'Приспособление для заправки охлаждающей жидкости', 'Применение' => 'Радиаторы и системы охлаждения', 'Совместимость' => 'Большинство легковых автомобилей и лёгких грузовиков', 'Комплектация' => 'Воронка и переходники'],
            ],
            'JTC-4682' => [
                'name_ru' => 'Универсальный набор для обслуживания поликлинового ремня JTC-4682',
                'name_ro' => 'Set universal pentru întreținerea curelei serpentine JTC-4682',
                'description_ru' => 'Набор JTC-4682 предназначен для ослабления натяжителя и обслуживания поликлиновых ремней на основных типах автомобилей. Трещоточный ключ и сменные насадки облегчают доступ к рычагам натяжителей в ограниченном пространстве. Размеры головок: 8, 12, 13, 14, 17, 18 и 24 мм; 3/8, 1/2 и 5/8 дюйма.',
                'description_ro' => 'Setul JTC-4682 este destinat eliberării întinzătorului și întreținerii curelelor serpentine la principalele tipuri de automobile. Cheia cu clichet și adaptoarele facilitează accesul la brațele întinzătoarelor în spații restrânse. Dimensiuni tubulare: 8, 12, 13, 14, 17, 18 și 24 mm; 3/8, 1/2 și 5/8 inch.',
                'attributes' => ['Тип' => 'Набор для поликлинового ремня', 'Назначение' => 'Ослабление натяжителя и замена ремня', 'Метрические размеры' => '8, 12, 13, 14, 17, 18, 24 мм', 'Дюймовые размеры' => '3/8, 1/2, 5/8 дюйма'],
            ],
            'JW0084' => [
                'name_ru' => 'Нутромер JTC-5012 (JW0084), 50–160 мм',
                'name_ro' => 'Alezometru JTC-5012 (JW0084), 50–160 mm',
                'description_ru' => 'Индикаторный нутромер JTC-5012 с кодом поставщика JW0084 предназначен для сравнительного измерения внутренних диаметров в диапазоне 50–160 мм. Комплект поставляется с индикатором часового типа и сменными измерительными стержнями.',
                'description_ro' => 'Alezometrul cu comparator JTC-5012, cod furnizor JW0084, este destinat măsurării comparative a diametrelor interioare în domeniul 50–160 mm. Setul include comparatorul și tijele de măsurare interschimbabile.',
                'attributes' => ['Тип' => 'Индикаторный нутромер', 'Диапазон измерения' => '50–160 мм', 'Модель JTC' => '5012', 'Код поставщика' => 'JW0084'],
            ],
            'JW0573' => [
                'name_ru' => 'Магнитный держатель JTC JW0573 для индикатора JTC-5501',
                'name_ro' => 'Suport magnetic JTC JW0573 pentru comparatorul JTC-5501',
                'description_ru' => 'Магнитный держатель JTC JW0573 предназначен для установки индикатора часового типа JTC-5501. V-образное магнитное основание развивает усилие около 800 Н. Общая высота стойки — 250 мм, вертикальная штанга — 180 мм, поперечная — 160 мм.',
                'description_ro' => 'Suportul magnetic JTC JW0573 este destinat montării comparatorului JTC-5501. Baza magnetică în formă de V dezvoltă o forță de aproximativ 800 N. Înălțimea totală este 250 mm, tija verticală are 180 mm, iar tija transversală 160 mm.',
                'attributes' => ['Тип' => 'Магнитный держатель индикатора', 'Совместимость' => 'JTC-5501', 'Усилие магнита' => 'около 800 Н', 'Общая высота' => '250 мм', 'Вертикальная штанга' => '180 мм', 'Поперечная штанга' => '160 мм', 'Основание' => 'V-образное'],
            ],
        ];

        DB::transaction(function () use ($images, $content): void {
            foreach ($images as $sku => $source) {
                $slug = Str::slug($sku);
                $directory = '/images/catalog-reviewed/jtc-recovery-sourced/'.$slug;
                $main = $directory.'/'.$slug.'-main.webp';
                $preview = $directory.'/'.$slug.'-preview.webp';
                $thumb = $directory.'/'.$slug.'-thumb.webp';
                $absoluteMain = public_path(ltrim($main, '/'));
                if (! is_file($absoluteMain)
                    || ! is_file(public_path(ltrim($preview, '/')))
                    || ! is_file(public_path(ltrim($thumb, '/')))) {
                    continue;
                }

                $product = DB::table('products')->where('sku', $sku)
                    ->select('id', 'source_parser_item_id', 'parser_source_urls')->first();
                if (! $product) {
                    continue;
                }

                $now = now();
                $pageHost = Str::lower((string) parse_url($source['page_url'], PHP_URL_HOST));
                $imageHost = Str::lower((string) parse_url($source['image_url'], PHP_URL_HOST));
                $sourceUrls = json_decode((string) $product->parser_source_urls, true);
                $sourceUrls = is_array($sourceUrls) ? $sourceUrls : [];
                $sourceUrls[] = $source['page_url'];
                $fields = $content[$sku];

                DB::table('products')->where('id', $product->id)->update([
                    'name_ru' => $fields['name_ru'],
                    'name_ro' => $fields['name_ro'],
                    'description_ru' => $fields['description_ru'],
                    'description_ro' => $fields['description_ro'],
                    'attributes' => json_encode($fields['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'main_image' => $main,
                    'gallery' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                    'needs_image_review' => false,
                    'parser_source_urls' => json_encode(array_values(array_unique($sourceUrls)), JSON_UNESCAPED_SLASHES),
                    'source_url' => $source['page_url'],
                    'source_domain' => $pageHost,
                    'source_type' => 'verified_reseller',
                    'fallback_source_used' => false,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'parser_confidence' => 95,
                    'updated_at' => $now,
                ]);

                if ($product->source_parser_item_id) {
                    DB::table('product_parser_image_assets')->where('parser_item_id', $product->source_parser_item_id)
                        ->update(['is_selected' => false, 'is_main' => false, 'updated_at' => $now]);
                    DB::table('product_parser_image_assets')->updateOrInsert(
                        ['parser_item_id' => $product->source_parser_item_id, 'source_url' => $source['image_url']],
                        [
                            'source_domain' => $imageHost,
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
                        'selected_images_json' => json_encode([$source['image_url']], JSON_UNESCAPED_SLASHES),
                        'processed_images_json' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                        'image_source_type' => 'reviewed_exact_reseller',
                        'official_source_url' => $source['page_url'],
                        'official_source_domain' => $pageHost,
                        'official_source_confidence' => 95,
                        'fallback_source_used' => false,
                        'source_match_confidence' => 95,
                        'needs_source_review' => false,
                        'source_reviewed_at' => $now,
                        'needs_image_review' => false,
                        'image_reviewed_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DB::table('product_parser_sources')->updateOrInsert(
                        ['parser_item_id' => $product->source_parser_item_id, 'url' => $source['page_url']],
                        [
                            'domain' => $pageHost,
                            'title' => 'Verified exact JTC product card '.$sku,
                            'snippet' => 'Exact-SKU product card, specifications, and image reviewed manually.',
                            'source_type' => 'verified_reseller',
                            'confidence_score' => 95,
                            'raw_data_json' => json_encode(['sku' => $sku, 'verification' => 'manual_exact_sku_review'], JSON_UNESCAPED_SLASHES),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }

                DB::table('product_images')->where('product_id', $product->id)->delete();
                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'path' => $main,
                    'alt' => $fields['name_ru'],
                    'sort_order' => 1,
                    'source_url' => $source['image_url'],
                    'source_page_url' => $source['page_url'],
                    'source_domain' => $imageHost,
                    'is_official' => false,
                    'mime_type' => 'image/webp',
                    'width' => 1200,
                    'height' => 1200,
                    'file_size' => filesize($absoluteMain) ?: null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Verified catalog repairs are intentionally irreversible.
    }
};
