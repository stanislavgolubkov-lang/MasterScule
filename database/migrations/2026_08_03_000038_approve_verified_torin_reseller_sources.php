<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sources = [
            'TRF70-3P' => 'https://www.mercadolibre.com.ec/aspiradora-torin-industrial-trf703p/up/MECU2677865537',
            'T25002' => 'https://toolwork.cl/products/banco-de-motores-de-500-kgs-con-reductor',
            'TRHS-E3412' => 'https://toolmania.cl/desabolladura-y-pintura/juego-de-martillos-desabolladores-trhs-e3412-torin-mi-ton-049352-14125.html',
        ];

        DB::transaction(function () use ($sources): void {
            $brandId = DB::table('brands')->where('name', 'Torin BIG RED')->value('id');
            if (! $brandId) {
                return;
            }

            foreach ($sources as $sku => $url) {
                $product = DB::table('products')
                    ->where('brand_id', $brandId)
                    ->where('sku', $sku)
                    ->where('source_url', $url)
                    ->select('id', 'sku', 'source_parser_item_id')
                    ->first();
                if (! $product) {
                    continue;
                }

                $now = now();
                $domain = (string) parse_url($url, PHP_URL_HOST);

                DB::table('products')->where('id', $product->id)->update([
                    'source_type' => 'verified_reseller',
                    'fallback_source_used' => false,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'parser_confidence' => 96,
                    'updated_at' => $now,
                ]);

                if (! $product->source_parser_item_id) {
                    continue;
                }

                DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                    'official_source_url' => $url,
                    'official_source_domain' => $domain,
                    'official_source_confidence' => 96,
                    'fallback_source_url' => null,
                    'fallback_source_domain' => null,
                    'fallback_source_used' => false,
                    'source_match_confidence' => 96,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('product_parser_sources')->updateOrInsert(
                    ['parser_item_id' => $product->source_parser_item_id, 'url' => $url],
                    [
                        'domain' => $domain,
                        'title' => 'Verified Torin reseller product page',
                        'snippet' => 'Exact Torin model verified on the linked reseller product page.',
                        'source_type' => 'verified_reseller',
                        'confidence_score' => 96,
                        'raw_data_json' => json_encode([
                            'sku' => $sku,
                            'verification' => 'exact_reseller_model',
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        });
    }

    public function down(): void
    {
        // Manual exact-model verification is intentionally irreversible.
    }
};
