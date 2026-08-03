<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $kingTonyId = DB::table('brands')->where('name', 'King Tony')->value('id');

            if ($kingTonyId) {
                $products = DB::table('products')
                    ->where('brand_id', $kingTonyId)
                    ->whereNull('source_url')
                    ->whereNotNull('source_parser_item_id')
                    ->get(['id', 'sku', 'source_parser_item_id', 'parser_source_urls']);

                foreach ($products as $product) {
                    $image = DB::table('product_images')
                        ->where('product_id', $product->id)
                        ->where('is_official', true)
                        ->where('source_domain', 'like', '%kingtony.com')
                        ->whereNotNull('source_url')
                        ->orderBy('sort_order')
                        ->first();
                    if (! $image) {
                        continue;
                    }

                    $sourceUrls = json_decode((string) $product->parser_source_urls, true);
                    $sourceUrls = is_array($sourceUrls) ? $sourceUrls : [];
                    $sourceUrls[] = $image->source_url;

                    DB::table('products')->where('id', $product->id)->update([
                        'source_url' => $image->source_url,
                        'source_domain' => 'www.kingtony.com',
                        'source_type' => 'official_manufacturer_media',
                        'parser_source_urls' => json_encode(array_values(array_unique($sourceUrls)), JSON_UNESCAPED_SLASHES),
                        'parser_confidence' => 100,
                        'fallback_source_used' => false,
                        'needs_source_review' => false,
                        'source_reviewed_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                        'official_source_url' => $image->source_url,
                        'official_source_domain' => 'www.kingtony.com',
                        'official_source_confidence' => 100,
                        'source_match_confidence' => 100,
                        'fallback_source_used' => false,
                        'needs_source_review' => false,
                        'source_reviewed_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('product_parser_sources')->updateOrInsert(
                        ['parser_item_id' => $product->source_parser_item_id, 'url' => $image->source_url],
                        [
                            'domain' => 'www.kingtony.com',
                            'title' => 'Official King Tony media for '.$product->sku,
                            'snippet' => 'Exact SKU or reviewed catalogue-family media from the manufacturer archive.',
                            'source_type' => 'official_manufacturer_media',
                            'confidence_score' => 100,
                            'raw_data_json' => json_encode(['sku' => $product->sku, 'verification' => 'official_manufacturer_media'], JSON_UNESCAPED_SLASHES),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }

            $verified = [
                '060791' => ['https://s-b-c-i.fr/carrosserie-/4852-outil-de-remise-en-forme-passage-de-roue-new.html', 's-b-c-i.fr', 'verified_reseller', 95],
                '062191' => ['https://gys-ukraine.com/wp-content/uploads/2021/07/062191.pdf', 'gys-ukraine.com', 'official_distributor', 98],
                '057449' => ['https://www.msh-equipment.nl/richten/pers-en-treksets/persset-10-ton-inclusief-toebehoren/', 'msh-equipment.nl', 'verified_reseller', 95],
                '066489' => ['https://www.rodrisystem.pt/product/conjunto-de-25-ferramentas-isoladas-1000v', 'rodrisystem.pt', 'verified_reseller', 95],
                '079878' => ['https://www.skb.ch/produit/tabouret-pour-atelier-3-tiroirs/', 'skb.ch', 'verified_reseller', 95],
                'TEL05004s-sc' => ['https://www.liudoirankiai.com/en/multifuncional-saddle-for-transmission-jack-tel05004s', 'www.liudoirankiai.com', 'verified_reseller', 95],
            ];

            foreach ($verified as $sku => [$url, $domain, $type, $confidence]) {
                $product = DB::table('products')->where('sku', $sku)->first(['id', 'source_parser_item_id', 'parser_source_urls']);
                if (! $product) {
                    continue;
                }

                $sourceUrls = json_decode((string) $product->parser_source_urls, true);
                $sourceUrls = is_array($sourceUrls) ? $sourceUrls : [];
                $sourceUrls[] = $url;

                DB::table('products')->where('id', $product->id)->update([
                    'source_url' => $url,
                    'source_domain' => $domain,
                    'source_type' => $type,
                    'parser_source_urls' => json_encode(array_values(array_unique($sourceUrls)), JSON_UNESCAPED_SLASHES),
                    'parser_confidence' => $confidence,
                    'fallback_source_used' => false,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'updated_at' => $now,
                ]);

                if (! $product->source_parser_item_id) {
                    continue;
                }

                DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                    'official_source_url' => $type === 'official_distributor' ? $url : null,
                    'official_source_domain' => $type === 'official_distributor' ? $domain : null,
                    'official_source_confidence' => $type === 'official_distributor' ? $confidence : null,
                    'source_match_confidence' => $confidence,
                    'fallback_source_used' => false,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('product_parser_sources')->updateOrInsert(
                    ['parser_item_id' => $product->source_parser_item_id, 'url' => $url],
                    [
                        'domain' => $domain,
                        'title' => 'Verified exact-SKU source for '.$sku,
                        'snippet' => 'Product identity and model were verified by exact SKU.',
                        'source_type' => $type,
                        'confidence_score' => $confidence,
                        'raw_data_json' => json_encode(['sku' => $sku, 'verification' => 'exact_sku'], JSON_UNESCAPED_SLASHES),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        });
    }

    public function down(): void
    {
        // Verified provenance is intentionally not reverted.
    }
};
