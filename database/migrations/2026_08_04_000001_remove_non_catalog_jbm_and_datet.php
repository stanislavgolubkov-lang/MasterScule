<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $skus = ['JBM-51896', 'TRHS-8781'];
            $products = DB::table('products')
                ->whereIn('sku', $skus)
                ->get(['id', 'source_parser_item_id']);
            $productIds = $products->pluck('id')->map(fn ($id) => (int) $id)->all();
            $parserItemIds = $products->pluck('source_parser_item_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if ($parserItemIds !== []) {
                DB::table('product_parser_sources')->whereIn('parser_item_id', $parserItemIds)->delete();
                DB::table('product_parser_image_assets')->whereIn('parser_item_id', $parserItemIds)->delete();
                DB::table('product_parser_items')->whereIn('id', $parserItemIds)->update([
                    'brand' => null,
                    'category_id' => null,
                    'detected_category_id' => null,
                    'status' => 'rejected',
                    'approval_status' => 'rejected',
                    'processing_stage' => 'excluded_from_catalog',
                    'created_product_id' => null,
                    'existing_product_id' => null,
                    'found_title' => null,
                    'found_description' => null,
                    'found_specs_json' => null,
                    'found_images_json' => null,
                    'selected_images_json' => null,
                    'processed_images_json' => null,
                    'source_urls_json' => null,
                    'official_source_url' => null,
                    'official_source_domain' => null,
                    'fallback_source_url' => null,
                    'fallback_source_domain' => null,
                    'needs_category_review' => false,
                    'needs_image_review' => false,
                    'needs_translation_review' => false,
                    'needs_price_review' => false,
                    'needs_stock_review' => false,
                    'needs_source_review' => false,
                    'needs_content_review' => false,
                    'error_message' => 'Excluded from the MasterScule catalog.',
                    'updated_at' => now(),
                ]);
            }

            if ($productIds !== []) {
                DB::table('products')->whereIn('id', $productIds)->delete();
            }

            DB::table('categories')
                ->where('slug', 'repararea-filetelor')
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('products')
                        ->whereColumn('products.category_id', 'categories.id');
                })
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('category_product')
                        ->whereColumn('category_product.category_id', 'categories.id');
                })
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('categories as children')
                        ->whereColumn('children.parent_id', 'categories.id');
                })
                ->delete();

            DB::table('brands')
                ->whereIn('slug', ['jbm', 'datet'])
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('products')
                        ->whereColumn('products.brand_id', 'brands.id');
                })
                ->delete();
        });
    }

    public function down(): void
    {
        // Non-catalog products must not be recreated automatically.
    }
};
