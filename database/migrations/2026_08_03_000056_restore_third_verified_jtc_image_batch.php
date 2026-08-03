<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $images = require database_path('data/reviewed-jtc-recovery-3-images.php');
        $content = [
            'JTC-4218' => [
                'name_ru' => 'Универсальный ключ для ступичных гаек JTC-4218, 45–150 мм',
                'name_ro' => 'Cheie universală pentru piulițe de butuc JTC-4218, 45–150 mm',
                'description_ru' => 'Универсальный ключ JTC-4218 предназначен для шестигранных и восьмигранных ступичных гаек диаметром 45–150 мм. Раздвижные захваты длиной 100 мм приводятся квадратом 3/4″. Инструмент поставляется в кейсе.',
                'description_ro' => 'Cheia universală JTC-4218 este destinată piulițelor de butuc hexagonale și octogonale cu diametrul de 45–150 mm. Fălcile reglabile de 100 mm sunt acționate printr-un pătrat de 3/4″. Scula este livrată în cutie.',
                'attributes' => ['Тип' => 'Универсальный ключ для ступичных гаек', 'Рабочий диапазон' => '45–150 мм', 'Профиль гаек' => '6 и 8 граней', 'Привод' => '3/4″', 'Длина захватов' => '100 мм', 'Комплектация' => 'Инструмент, кейс'],
                'source_type' => 'official_distributor',
                'confidence' => 98,
            ],
            'JTC-43930' => [
                'name_ru' => 'Торцевая головка JTC-43930, 1/2″, 12 граней, 30 мм',
                'name_ro' => 'Cap tubular JTC-43930, 1/2″, 12 laturi, 30 mm',
                'description_ru' => 'Двенадцатигранная торцевая головка JTC-43930 из хромованадиевой стали предназначена для ручного привода 1/2″. Размер головки — 30 мм, длина — 42 мм, наружный диаметр рабочей части — 39,9 мм, глубина рабочего профиля — 16,5 мм.',
                'description_ro' => 'Capul tubular cu 12 laturi JTC-43930 din oțel crom-vanadiu este destinat antrenării manuale de 1/2″. Dimensiunea este de 30 mm, lungimea de 42 mm, diametrul exterior al părții de lucru de 39,9 mm, iar adâncimea profilului de 16,5 mm.',
                'attributes' => ['Тип' => 'Торцевая головка', 'Профиль' => '12 граней', 'Привод' => '1/2″', 'Размер' => '30 мм', 'Длина' => '42 мм', 'Наружный диаметр' => '39,9 мм', 'Глубина профиля' => '16,5 мм', 'Материал' => 'Хромованадиевая сталь'],
                'source_type' => 'official_manufacturer',
                'confidence' => 100,
            ],
        ];

        DB::transaction(function () use ($images, $content): void {
            foreach ($images as $sku => $source) {
                $slug = Str::slug($sku);
                $directory = '/images/catalog-reviewed/jtc-recovery-3-sourced/'.$slug;
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
                        'image_source_type' => 'reviewed_exact_reseller',
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
                            'snippet' => 'Exact product or official family publication reviewed manually.',
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
