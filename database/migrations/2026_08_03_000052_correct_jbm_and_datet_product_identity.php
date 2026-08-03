<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $jbmBrandId = DB::table('brands')->where('slug', 'jbm')->value('id');
            if (! $jbmBrandId) {
                $jbmBrandId = DB::table('brands')->insertGetId([
                    'name' => 'JBM',
                    'slug' => 'jbm',
                    'description' => 'JBM Campllong professional tools.',
                    'is_featured' => false,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $datetBrandId = DB::table('brands')->where('slug', 'datet')->value('id');
            if (! $datetBrandId) {
                $datetBrandId = DB::table('brands')->insertGetId([
                    'name' => 'DATET',
                    'slug' => 'datet',
                    'description' => 'Automotive workshop equipment and specialist tools.',
                    'is_featured' => false,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $threadRepairCategoryId = DB::table('categories')->where('slug', 'repararea-filetelor')->value('id');
            if (! $threadRepairCategoryId) {
                $threadRepairCategoryId = DB::table('categories')->insertGetId([
                    'parent_id' => DB::table('categories')->where('slug', 'instrument-manual')->value('id'),
                    'name' => 'Ремонт резьбы',
                    'name_ro' => 'Repararea filetelor',
                    'slug' => 'repararea-filetelor',
                    'description' => 'Инструменты и наборы для восстановления повреждённой резьбы.',
                    'description_ro' => 'Scule și seturi pentru repararea filetelor deteriorate.',
                    'sort_order' => 0,
                    'is_active' => true,
                    'is_assignable' => true,
                    'is_menu_visible' => true,
                    'source' => 'catalog_audit',
                    'taxonomy_version' => '2026-08-03',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->repairJbm($jbmBrandId, $threadRepairCategoryId, $now);
            $this->repairDatet($datetBrandId, $now);
        });
    }

    private function repairJbm(int $brandId, int $categoryId, $now): void
    {
        $product = DB::table('products')->where('sku', 'JBM-51896')->first();
        if (! $product) {
            return;
        }

        $pageUrl = 'https://jbmcamp.com/en/utillaje/reparacion-tuercas/kit-de-reparacion-de-tuercas-helicoidales.html';
        $verificationUrl = 'https://www.sculebgs.ro/cumpara/set-reparatii-filete-helicoil-m-m12-jbm-51896-8719';
        $imageUrl = 'https://c.cdnmp.net/528999060/p/l/6/set-reparatii-filete-helicoil-m-m12-jbm-51896~9676.jpg';
        $directory = '/images/catalog-reviewed/jbm-sourced/jbm-51896';
        $main = $directory.'/jbm-51896-main.webp';
        $preview = $directory.'/jbm-51896-preview.webp';
        $thumb = $directory.'/jbm-51896-thumb.webp';
        if (! is_file(public_path(ltrim($main, '/')))) {
            return;
        }

        $nameRu = 'Набор для ремонта резьбы с вставками Helicoil JBM 51896, 115 предметов';
        $nameRo = 'Set pentru repararea filetelor cu inserții Helicoil JBM 51896, 115 piese';
        $descriptionRu = 'JBM 51896 — набор из 115 предметов для восстановления резьбы M5×0,8, M6×1,0, M8×1,25, M10×1,5 и M12×1,75. Для каждого размера предусмотрены сверло, метчик, установочный инструмент, выколотка и резьбовые вставки из нержавеющей стали.';
        $descriptionRo = 'JBM 51896 este un set de 115 piese pentru repararea filetelor M5×0,8, M6×1,0, M8×1,25, M10×1,5 și M12×1,75. Pentru fiecare dimensiune sunt incluse burghiu, tarod, instrument de montare, poanson și inserții filetate din oțel inoxidabil.';
        $sourceUrls = json_decode((string) $product->parser_source_urls, true);
        $sourceUrls = is_array($sourceUrls) ? $sourceUrls : [];
        array_push($sourceUrls, $pageUrl, $verificationUrl);

        DB::table('products')->where('id', $product->id)->update([
            'brand_id' => $brandId,
            'category_id' => $categoryId,
            'name' => $nameRu,
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description' => $descriptionRu,
            'short_description_ru' => $descriptionRu,
            'short_description_ro' => $descriptionRo,
            'description' => $descriptionRu,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'attributes' => json_encode([
                'Количество предметов' => 115,
                'Размеры резьбы' => 'M5×0,8; M6×1,0; M8×1,25; M10×1,5; M12×1,75',
                'Материал вставок' => 'Нержавеющая сталь',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'main_image' => $main,
            'gallery' => json_encode([$main], JSON_UNESCAPED_SLASHES),
            'meta_title' => $nameRu.' | MasterScule.md',
            'meta_description' => 'Набор JBM 51896 из 115 предметов для ремонта резьбы M5–M12 с резьбовыми вставками Helicoil.',
            'source_url' => $pageUrl,
            'source_domain' => 'jbmcamp.com',
            'source_type' => 'official_manufacturer',
            'parser_source_urls' => json_encode(array_values(array_unique($sourceUrls)), JSON_UNESCAPED_SLASHES),
            'parser_confidence' => 100,
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'needs_content_review' => false,
            'needs_category_review' => false,
            'needs_translation_review' => false,
            'needs_image_review' => false,
            'source_reviewed_at' => $now,
            'updated_at' => $now,
        ]);

        if ($product->source_parser_item_id) {
            DB::table('product_parser_image_assets')->where('parser_item_id', $product->source_parser_item_id)
                ->update(['is_selected' => false, 'is_main' => false, 'updated_at' => $now]);
            DB::table('product_parser_image_assets')->updateOrInsert(
                ['parser_item_id' => $product->source_parser_item_id, 'source_url' => $imageUrl],
                [
                    'source_domain' => 'c.cdnmp.net',
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
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                'brand' => 'JBM',
                'category_id' => $categoryId,
                'detected_category_id' => $categoryId,
                'name_ru' => $nameRu,
                'name_ro' => $nameRo,
                'short_description_ru' => $descriptionRu,
                'short_description_ro' => $descriptionRo,
                'description_ru' => $descriptionRu,
                'description_ro' => $descriptionRo,
                'selected_images_json' => json_encode([$imageUrl], JSON_UNESCAPED_SLASHES),
                'processed_images_json' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                'source_urls_json' => json_encode([$pageUrl, $verificationUrl], JSON_UNESCAPED_SLASHES),
                'official_source_url' => $pageUrl,
                'official_source_domain' => 'jbmcamp.com',
                'official_source_confidence' => 100,
                'source_match_confidence' => 100,
                'image_source_type' => 'verified_reseller_exact_sku',
                'content_source_type' => 'official_manufacturer',
                'fallback_source_used' => false,
                'needs_source_review' => false,
                'needs_content_review' => false,
                'needs_category_review' => false,
                'needs_translation_review' => false,
                'needs_image_review' => false,
                'source_reviewed_at' => $now,
                'image_reviewed_at' => $now,
                'translation_reviewed_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('product_parser_sources')->updateOrInsert(
                ['parser_item_id' => $product->source_parser_item_id, 'url' => $pageUrl],
                [
                    'domain' => 'jbmcamp.com',
                    'title' => 'Official JBM 51896 product page',
                    'snippet' => 'Manufacturer page confirms the 115-piece thread repair set and exact reference 51896.',
                    'source_type' => 'official_manufacturer',
                    'confidence_score' => 100,
                    'raw_data_json' => json_encode(['sku' => 'JBM-51896', 'ean' => '8435034518962'], JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('product_images')->where('product_id', $product->id)->delete();
        DB::table('product_images')->insert([
            'product_id' => $product->id,
            'path' => $main,
            'alt' => $nameRu,
            'sort_order' => 1,
            'source_url' => $imageUrl,
            'source_page_url' => $verificationUrl,
            'source_domain' => 'c.cdnmp.net',
            'is_official' => false,
            'mime_type' => 'image/webp',
            'width' => 1200,
            'height' => 1200,
            'file_size' => filesize(public_path(ltrim($main, '/'))) ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function repairDatet(int $brandId, $now): void
    {
        $product = DB::table('products')->where('sku', 'TRHS-8781')->first();
        if (! $product) {
            return;
        }

        $pageUrl = 'https://datet.vn/en/danh-muc/thiet-bi-gara-tong-hop/dung-cu-chuyen-dung';
        $nameRu = 'Набор съёмников дизельных форсунок DATET TRHS-8781';
        $nameRo = 'Set de extractoare pentru injectoare diesel DATET TRHS-8781';
        $descriptionRu = 'DATET TRHS-8781 — набор для демонтажа дизельных форсунок, поставляемый в формованном кейсе. Точный состав и совместимость следует сверять с актуальной документацией поставщика.';
        $descriptionRo = 'DATET TRHS-8781 este un set pentru demontarea injectoarelor diesel, livrat într-o cutie turnată. Componența exactă și compatibilitatea trebuie verificate în documentația actuală a furnizorului.';

        DB::table('products')->where('id', $product->id)->update([
            'brand_id' => $brandId,
            'name' => $nameRu,
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description' => $descriptionRu,
            'short_description_ru' => $descriptionRu,
            'short_description_ro' => $descriptionRo,
            'description' => $descriptionRu,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'attributes' => json_encode(['Тип' => 'Набор съёмников дизельных форсунок'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'meta_title' => $nameRu.' | MasterScule.md',
            'meta_description' => 'Набор DATET TRHS-8781 для демонтажа дизельных форсунок в формованном кейсе.',
            'source_url' => $pageUrl,
            'source_domain' => 'datet.vn',
            'source_type' => 'manufacturer_catalog',
            'parser_source_urls' => json_encode([$pageUrl], JSON_UNESCAPED_SLASHES),
            'parser_confidence' => 98,
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'needs_content_review' => false,
            'needs_translation_review' => false,
            'needs_image_review' => false,
            'source_reviewed_at' => $now,
            'updated_at' => $now,
        ]);

        if ($product->source_parser_item_id) {
            DB::table('product_parser_image_assets')->where('parser_item_id', $product->source_parser_item_id)
                ->where('is_selected', true)
                ->update(['needs_review' => false, 'updated_at' => $now]);
            DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                'brand' => 'DATET',
                'name_ru' => $nameRu,
                'name_ro' => $nameRo,
                'short_description_ru' => $descriptionRu,
                'short_description_ro' => $descriptionRo,
                'description_ru' => $descriptionRu,
                'description_ro' => $descriptionRo,
                'source_urls_json' => json_encode([$pageUrl], JSON_UNESCAPED_SLASHES),
                'official_source_url' => $pageUrl,
                'official_source_domain' => 'datet.vn',
                'official_source_confidence' => 98,
                'source_match_confidence' => 98,
                'fallback_source_used' => false,
                'needs_source_review' => false,
                'needs_content_review' => false,
                'needs_translation_review' => false,
                'needs_image_review' => false,
                'source_reviewed_at' => $now,
                'image_reviewed_at' => $now,
                'translation_reviewed_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('product_parser_sources')->updateOrInsert(
                ['parser_item_id' => $product->source_parser_item_id, 'url' => $pageUrl],
                [
                    'domain' => 'datet.vn',
                    'title' => 'DATET specialist tools catalogue',
                    'snippet' => 'Manufacturer catalogue lists exact SKU TRHS-8781 as an injector puller set.',
                    'source_type' => 'manufacturer_catalog',
                    'confidence_score' => 98,
                    'raw_data_json' => json_encode(['sku' => 'TRHS-8781', 'verification' => 'exact_catalog_listing'], JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('product_images')->where('product_id', $product->id)->update([
            'source_page_url' => $pageUrl,
            'is_official' => false,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Product identity corrections are intentionally irreversible.
    }
};
