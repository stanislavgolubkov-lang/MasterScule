<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $blockedSkus = [
            'JTC-1322-S1',
            '096000',
            '51914ind1',
            '51938ind1',
            '53139',
            '53539',
            '53608',
            '97039C',
            '97290',
            '97300',
            '97380',
            '97443C',
        ];
        $now = now();

        DB::transaction(function () use ($blockedSkus, $now): void {
            $productIds = DB::table('products')
                ->where('status', 'draft')
                ->where('needs_review', true)
                ->whereNotIn('sku', $blockedSkus)
                ->where('needs_image_review', false)
                ->where('needs_category_review', false)
                ->where('needs_translation_review', false)
                ->where('needs_price_review', false)
                ->where('needs_stock_review', false)
                ->where('needs_content_review', false)
                ->where('needs_source_review', false)
                ->whereNotNull('source_url')
                ->whereNotNull('main_image')
                ->where('main_image', '!=', '')
                ->pluck('id');

            if ($productIds->isEmpty()) {
                return;
            }

            DB::table('products')->whereIn('id', $productIds)->update([
                'status' => 'published',
                'approval_status' => 'approved',
                'needs_review' => false,
                'is_active' => true,
                'updated_at' => $now,
            ]);

            DB::table('product_parser_items')->whereIn('created_product_id', $productIds)->update([
                'status' => 'approved',
                'approval_status' => 'approved',
                'processing_stage' => 'published',
                'needs_image_review' => false,
                'needs_category_review' => false,
                'needs_translation_review' => false,
                'needs_price_review' => false,
                'needs_stock_review' => false,
                'needs_content_review' => false,
                'needs_source_review' => false,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        // Guard-verified publication is intentionally irreversible.
    }
};
