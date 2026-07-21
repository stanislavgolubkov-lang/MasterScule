<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();

            DB::table('products')
                ->where('source_url', 'like', '%maximum.md%')
                ->update([
                    'source_url' => null,
                    'source_domain' => null,
                    'source_type' => null,
                    'fallback_source_used' => false,
                    'needs_source_review' => true,
                    'source_reviewed_at' => null,
                    'updated_at' => $now,
                ]);

            $parserItems = DB::table('product_parser_items')
                ->where(function ($query): void {
                    $query->where('official_source_url', 'like', '%maximum.md%')
                        ->orWhere('fallback_source_url', 'like', '%maximum.md%')
                        ->orWhere('source_urls_json', 'like', '%maximum.md%');
                })
                ->select(['id', 'official_source_url', 'fallback_source_url', 'source_urls_json'])
                ->get();

            foreach ($parserItems as $item) {
                $sourceUrls = json_decode((string) $item->source_urls_json, true) ?: [];
                $sourceUrls = array_values(array_filter(
                    $sourceUrls,
                    fn ($url) => ! is_string($url) || stripos($url, 'maximum.md') === false,
                ));
                $officialRetired = stripos((string) $item->official_source_url, 'maximum.md') !== false;
                $fallbackRetired = stripos((string) $item->fallback_source_url, 'maximum.md') !== false;

                DB::table('product_parser_items')->where('id', $item->id)->update([
                    'official_source_url' => $officialRetired ? null : $item->official_source_url,
                    'official_source_domain' => $officialRetired ? null : DB::raw('official_source_domain'),
                    'official_source_confidence' => $officialRetired ? null : DB::raw('official_source_confidence'),
                    'fallback_source_url' => $fallbackRetired ? null : $item->fallback_source_url,
                    'fallback_source_domain' => $fallbackRetired ? null : DB::raw('fallback_source_domain'),
                    'fallback_source_used' => $fallbackRetired ? false : DB::raw('fallback_source_used'),
                    'source_urls_json' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'needs_source_review' => true,
                    'source_reviewed_at' => null,
                    'updated_at' => $now,
                ]);
            }

            DB::table('product_parser_sources')
                ->where('domain', 'like', '%maximum.md%')
                ->orWhere('url', 'like', '%maximum.md%')
                ->delete();

            DB::table('product_images')
                ->where('source_page_url', 'like', '%maximum.md%')
                ->update(['source_page_url' => null, 'is_official' => false, 'updated_at' => $now]);
            DB::table('product_images')
                ->where('source_url', 'like', '%maximum.md%')
                ->update(['source_url' => null, 'is_official' => false, 'updated_at' => $now]);

            $marketplaceImageItemIds = DB::table('product_parser_image_assets')
                ->where('source_domain', 'like', '%simpalsmedia.com%')
                ->orWhere('source_url', 'like', '%simpalsmedia.com%')
                ->pluck('parser_item_id')
                ->unique()
                ->values();

            if ($marketplaceImageItemIds->isNotEmpty()) {
                DB::table('product_parser_image_assets')
                    ->whereIn('parser_item_id', $marketplaceImageItemIds)
                    ->update(['needs_review' => true, 'updated_at' => $now]);
                DB::table('product_parser_items')
                    ->whereIn('id', $marketplaceImageItemIds)
                    ->update(['needs_image_review' => true, 'image_reviewed_at' => null, 'updated_at' => $now]);
                DB::table('products')
                    ->whereIn('source_parser_item_id', $marketplaceImageItemIds)
                    ->update(['needs_image_review' => true, 'updated_at' => $now]);
            }

            DB::table('product_images')
                ->where('source_domain', 'like', '%simpalsmedia.com%')
                ->update(['is_official' => false, 'updated_at' => $now]);
        });
    }

    public function down(): void
    {
        // Retired marketplace URLs and source labels are intentionally not restored.
    }
};
