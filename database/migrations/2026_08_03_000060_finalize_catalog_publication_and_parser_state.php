<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $unpublishableSkus = [
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

        DB::transaction(function () use ($now, $unpublishableSkus): void {
            DB::table('products')->whereIn('sku', $unpublishableSkus)->update([
                'status' => 'draft',
                'approval_status' => 'pending_review',
                'needs_review' => true,
                'needs_image_review' => true,
                'is_active' => false,
                'updated_at' => $now,
            ]);

            DB::table('product_parser_items')
                ->whereIn('created_product_id', fn ($query) => $query->select('id')->from('products')->whereIn('sku', $unpublishableSkus))
                ->update([
                    'status' => 'draft_created',
                    'processing_stage' => 'draft_created_missing_verified_image',
                    'needs_image_review' => true,
                    'updated_at' => $now,
                ]);

            $jbm = DB::table('products')->where('sku', 'JBM-51896')->first(['id', 'category_id']);
            if ($jbm) {
                DB::table('category_product')->where('product_id', $jbm->id)->where('is_primary', true)->update([
                    'category_id' => $jbm->category_id,
                    'updated_at' => $now,
                ]);
            }

            DB::table('product_parser_items')
                ->whereIn('status', ['queued', 'ready_for_review'])
                ->whereNull('created_product_id')
                ->whereNotNull('existing_product_id')
                ->whereIn('existing_product_id', fn ($query) => $query->select('id')->from('products')->where('status', 'published'))
                ->update([
                    'status' => 'existing_product_found',
                    'processing_stage' => 'existing_product_found',
                    'needs_image_review' => false,
                    'needs_source_review' => false,
                    'updated_at' => $now,
                ]);

            DB::table('product_parser_items')
                ->where('status', 'ready_for_review')
                ->whereNotNull('created_product_id')
                ->whereIn('created_product_id', fn ($query) => $query->select('id')->from('products')->where('status', 'published'))
                ->update([
                    'status' => 'approved',
                    'processing_stage' => 'published',
                    'needs_image_review' => false,
                    'needs_source_review' => false,
                    'updated_at' => $now,
                ]);

            DB::table('product_parser_items')
                ->where('status', 'needs_manual_review')
                ->whereNotNull('created_product_id')
                ->whereIn('created_product_id', fn ($query) => $query->select('id')->from('products')->where('status', 'draft'))
                ->update([
                    'status' => 'draft_created',
                    'processing_stage' => 'draft_created',
                    'needs_image_review' => false,
                    'needs_source_review' => false,
                    'updated_at' => $now,
                ]);

            DB::table('product_parser_items')->whereIn('status', ['rejected', 'skipped'])->update([
                'needs_image_review' => false,
                'needs_source_review' => false,
                'updated_at' => $now,
            ]);

            DB::table('product_parser_items')
                ->whereNotNull('created_product_id')
                ->whereIn('created_product_id', fn ($query) => $query->select('id')->from('products')->where('needs_image_review', false))
                ->update(['needs_image_review' => false, 'updated_at' => $now]);

            $this->replaceMaximumSource($now);
        });
    }

    private function replaceMaximumSource($now): void
    {
        $sku = 'TEL05004s-sc';
        $sourceUrl = 'https://www.liudoirankiai.com/en/multifuncional-saddle-for-transmission-jack-tel05004s';
        $sourceDomain = 'www.liudoirankiai.com';
        $product = DB::table('products')->where('sku', $sku)->first(['id', 'source_parser_item_id']);
        if (! $product) {
            return;
        }

        DB::table('products')->where('id', $product->id)->update([
            'source_url' => $sourceUrl,
            'source_domain' => $sourceDomain,
            'source_type' => 'verified_exact_distributor',
            'parser_source_urls' => json_encode([$sourceUrl], JSON_UNESCAPED_SLASHES),
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'parser_confidence' => 97,
            'updated_at' => $now,
        ]);

        if (! $product->source_parser_item_id) {
            return;
        }

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
            'official_source_url' => $sourceUrl,
            'official_source_domain' => $sourceDomain,
            'official_source_confidence' => 97,
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'source_reviewed_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('product_parser_sources')
            ->where('parser_item_id', $product->source_parser_item_id)
            ->where(function ($query): void {
                $query->whereRaw("LOWER(COALESCE(domain,'')) LIKE '%maximum%'")
                    ->orWhereRaw("LOWER(COALESCE(url,'')) LIKE '%maximum%'");
            })
            ->delete();

        DB::table('product_parser_sources')->updateOrInsert(
            ['parser_item_id' => $product->source_parser_item_id, 'url' => $sourceUrl],
            [
                'domain' => $sourceDomain,
                'title' => 'Multifunctional saddle for transmission jack TEL05004S',
                'snippet' => 'Exact Tongrun/Torin transmission-jack saddle, manufacturer code TEL05004S1.',
                'source_type' => 'verified_exact_distributor',
                'confidence_score' => 97,
                'raw_data_json' => json_encode(['catalog_sku' => $sku, 'manufacturer_code' => 'TEL05004S1'], JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        // Publication safety and verified source corrections are intentionally irreversible.
    }
};
