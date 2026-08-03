<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $images = require database_path('data/reviewed-jtc-recovery-2-images.php');
        $content = [
            'JTC-1203' => [
                'name_ru' => 'Динамометрический ключ JTC-1203, 1/2″, 28–210 Н·м',
                'name_ro' => 'Cheie dinamometrică JTC-1203, 1/2″, 28–210 N·m',
                'description_ru' => 'Щелчковый динамометрический ключ JTC-1203 с приводом 1/2″ предназначен для контролируемой затяжки резьбовых соединений в диапазоне 28–210 Н·м. Длина инструмента — 465 мм.',
                'description_ro' => 'Cheia dinamometrică cu declanșare JTC-1203, cu antrenare de 1/2″, este destinată strângerii controlate a îmbinărilor filetate în domeniul 28–210 N·m. Lungimea sculei este de 465 mm.',
                'attributes' => ['Тип' => 'Щелчковый динамометрический ключ', 'Привод' => '1/2″', 'Диапазон момента' => '28–210 Н·м', 'Длина' => '465 мм'],
                'source_type' => 'official_manufacturer',
                'confidence' => 100,
            ],
            'JTC-3473' => [
                'name_ru' => 'Штангенциркуль JTC-3473, 150 мм, точность 0,05 мм',
                'name_ro' => 'Șubler JTC-3473, 150 mm, precizie 0,05 mm',
                'description_ru' => 'Механический штангенциркуль JTC-3473 из нержавеющей стали предназначен для наружных, внутренних и глубинных измерений до 150 мм. Цена деления нониуса — 0,05 мм; нанесены метрическая и дюймовая шкалы.',
                'description_ro' => 'Șublerul mecanic JTC-3473 din oțel inoxidabil este destinat măsurărilor exterioare, interioare și de adâncime până la 150 mm. Diviziunea vernierului este de 0,05 mm; sunt disponibile scale metrică și în inch.',
                'attributes' => ['Тип' => 'Механический штангенциркуль', 'Диапазон измерения' => '0–150 мм', 'Цена деления' => '0,05 мм', 'Материал' => 'Нержавеющая сталь', 'Шкалы' => 'Метрическая и дюймовая'],
                'source_type' => 'official_manufacturer',
                'confidence' => 100,
            ],
            'JTC-5631-20' => [
                'name_ru' => 'Шиномонтажная лопатка JTC-5631-20, 20″',
                'name_ro' => 'Levier pentru anvelope JTC-5631-20, 20″',
                'description_ru' => 'Шиномонтажная лопатка JTC-5631-20 длиной 20″ входит в размерный ряд JTC-5631. Изготовлена из кованой среднеуглеродистой стали и предназначена для монтажа и демонтажа шин.',
                'description_ro' => 'Levierul pentru anvelope JTC-5631-20, cu lungimea de 20″, face parte din seria JTC-5631. Este fabricat din oțel forjat cu conținut mediu de carbon și este destinat montării și demontării anvelopelor.',
                'attributes' => ['Тип' => 'Шиномонтажная лопатка', 'Длина' => '20″', 'Серия' => 'JTC-5631', 'Материал' => 'Кованая среднеуглеродистая сталь'],
                'source_type' => 'official_manufacturer',
                'confidence' => 98,
            ],
            'JTC-5631-24' => [
                'name_ru' => 'Шиномонтажная лопатка JTC-5631-24, 24″',
                'name_ro' => 'Levier pentru anvelope JTC-5631-24, 24″',
                'description_ru' => 'Шиномонтажная лопатка JTC-5631-24 длиной 24″ входит в размерный ряд JTC-5631. Изготовлена из кованой среднеуглеродистой стали и предназначена для монтажа и демонтажа шин.',
                'description_ro' => 'Levierul pentru anvelope JTC-5631-24, cu lungimea de 24″, face parte din seria JTC-5631. Este fabricat din oțel forjat cu conținut mediu de carbon și este destinat montării și demontării anvelopelor.',
                'attributes' => ['Тип' => 'Шиномонтажная лопатка', 'Длина' => '24″', 'Серия' => 'JTC-5631', 'Материал' => 'Кованая среднеуглеродистая сталь'],
                'source_type' => 'official_manufacturer',
                'confidence' => 98,
            ],
            'JW0832' => [
                'name_ru' => 'Набор для снятия свечей накаливания JTC-4263 (JW0832) для Mercedes-Benz CDI',
                'name_ro' => 'Set pentru demontarea bujiilor incandescente JTC-4263 (JW0832) pentru Mercedes-Benz CDI',
                'description_ru' => 'Набор JTC-4263 с кодом поставщика JW0832 предназначен для извлечения повреждённых или прикипевших свечей накаливания на дизельных двигателях Mercedes-Benz CDI OM611, OM612, OM613, OM646, OM647 и OM648.',
                'description_ro' => 'Setul JTC-4263, cod furnizor JW0832, este destinat extragerii bujiilor incandescente deteriorate sau blocate la motoarele diesel Mercedes-Benz CDI OM611, OM612, OM613, OM646, OM647 și OM648.',
                'attributes' => ['Тип' => 'Набор для снятия и ремонта свечей накаливания', 'Модель JTC' => '4263', 'Код поставщика' => 'JW0832', 'Марка автомобиля' => 'Mercedes-Benz', 'Двигатели' => 'CDI OM611, OM612, OM613, OM646, OM647, OM648'],
                'source_type' => 'official_manufacturer',
                'confidence' => 95,
            ],
        ];

        DB::transaction(function () use ($images, $content): void {
            foreach ($images as $sku => $source) {
                $slug = Str::slug($sku);
                $directory = '/images/catalog-reviewed/jtc-recovery-2-sourced/'.$slug;
                $main = $directory.'/'.$slug.'-main.webp';
                $preview = $directory.'/'.$slug.'-preview.webp';
                $thumb = $directory.'/'.$slug.'-thumb.webp';
                $absoluteMain = public_path(ltrim($main, '/'));
                if (! is_file($absoluteMain) || ! is_file(public_path(ltrim($preview, '/'))) || ! is_file(public_path(ltrim($thumb, '/')))) {
                    continue;
                }

                $product = DB::table('products')->where('sku', $sku)
                    ->select('id', 'source_parser_item_id', 'parser_source_urls')->first();
                if (! $product) {
                    continue;
                }

                $now = now();
                $fields = $content[$sku];
                $pageHost = Str::lower((string) parse_url($source['page_url'], PHP_URL_HOST));
                $imageHost = Str::lower((string) parse_url($source['image_url'], PHP_URL_HOST));
                $imageOfficial = Str::endsWith($imageHost, 'jtc.com.tw');
                $sourceUrls = json_decode((string) $product->parser_source_urls, true);
                $sourceUrls = is_array($sourceUrls) ? $sourceUrls : [];
                $sourceUrls[] = $source['page_url'];
                if (isset($source['verification_url'])) {
                    $sourceUrls[] = $source['verification_url'];
                }

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
                    'source_type' => $fields['source_type'],
                    'fallback_source_used' => false,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'parser_confidence' => $fields['confidence'],
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
                        'image_source_type' => $imageOfficial ? 'official_manufacturer' : 'reviewed_exact_reseller',
                        'official_source_url' => $source['page_url'],
                        'official_source_domain' => $pageHost,
                        'official_source_confidence' => $fields['confidence'],
                        'fallback_source_used' => false,
                        'source_match_confidence' => $fields['confidence'],
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
                            'title' => 'Verified JTC source for '.$sku,
                            'snippet' => 'Manufacturer catalog or exact product page matched to the catalog record and reviewed manually.',
                            'source_type' => $fields['source_type'],
                            'confidence_score' => $fields['confidence'],
                            'raw_data_json' => json_encode(['sku' => $sku, 'verification' => 'manual_exact_or_family_review'], JSON_UNESCAPED_SLASHES),
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
                    'source_page_url' => $source['verification_url'] ?? $source['page_url'],
                    'source_domain' => $imageHost,
                    'is_official' => $imageOfficial,
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
