<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sources = [
            'JTC-1202' => 'https://eng.jtc.com.tw/product/?mode=data&id=2876&top=2',
            'JTC-1206' => 'https://eng.jtc.com.tw/product/?mode=data&id=2876&top=2',
            'JTC-1217' => 'https://eng.jtc.com.tw/product/?mode=data&id=788&top=2',
            'JTC-1241' => 'https://eng.jtc.com.tw/product/?mode=data&id=696&top=2',
            'JTC-1260' => 'https://eng.jtc.com.tw/product/?mode=data&id=376&top=2',
            'JTC-1364' => 'https://eng.jtc.com.tw/product/?mode=data&id=240&top=2',
            'JTC-1524' => 'https://eng.jtc.com.tw/product/?mode=data&id=1102&top=2',
            'JTC-1733' => 'https://eng.jtc.com.tw/product/?mode=data&id=717&top=2',
            'JTC-2542' => 'https://eng.jtc.com.tw/product/?mode=data&id=538&top=2',
            'JTC-2543' => 'https://eng.jtc.com.tw/product/?mode=data&id=2443&top=2',
            'JTC-3520A' => 'https://eng.jtc.com.tw/product/?mode=data&id=1081&top=2',
            'JTC-3520D' => 'https://eng.jtc.com.tw/product/?mode=data&id=3676&top=2',
            'JTC-4226' => 'https://eng.jtc.com.tw/product/?mode=data&id=3977&top=2',
            'JTC-4482' => 'https://eng.jtc.com.tw/product/?mode=data&id=4704&top=2',
            'JTC-4659' => 'https://eng.jtc.com.tw/product/?mode=data&id=1324&top=2',
            'JTC-7807' => 'https://eng.jtc.com.tw/product/?mode=data&id=5949&top=2',
            'JTC-7893' => 'https://eng.jtc.com.tw/product/?mode=data&id=6271&top=2',
            'JTC-7941' => 'https://eng.jtc.com.tw/product/?mode=data&id=6806&top=2',
            'JTC-8P101A' => 'https://eng.jtc.com.tw/product/?mode=data&id=1504&top=2',
            'JTC-8P110' => 'https://eng.jtc.com.tw/product/?mode=data&id=482&top=2',
            'JTC-HB600' => 'https://eng.jtc.com.tw/product/?mode=data&id=1&top=2',
            'JTC-K8261' => 'https://eng.jtc.com.tw/product/?mode=data&id=1402&top=2',
            'JTC-PB810' => 'https://eng.jtc.com.tw/product/?mode=data&id=466&top=2',
        ];

        DB::transaction(function () use ($sources): void {
            $brandId = DB::table('brands')->where('name', 'JTC')->value('id');
            if (! $brandId) {
                return;
            }

            foreach ($sources as $sku => $url) {
                $product = DB::table('products')
                    ->where('brand_id', $brandId)
                    ->where('sku', $sku)
                    ->select('id', 'sku', 'source_parser_item_id', 'parser_source_urls')
                    ->first();
                if (! $product) {
                    continue;
                }

                $now = now();
                $sourceUrls = json_decode((string) $product->parser_source_urls, true);
                $sourceUrls = is_array($sourceUrls) ? $sourceUrls : [];
                $sourceUrls[] = $url;

                DB::table('products')->where('id', $product->id)->update([
                    'parser_source_urls' => json_encode(array_values(array_unique($sourceUrls)), JSON_UNESCAPED_SLASHES),
                    'source_url' => $url,
                    'source_domain' => 'eng.jtc.com.tw',
                    'source_type' => 'official_manufacturer',
                    'fallback_source_used' => false,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'parser_confidence' => 100,
                    'updated_at' => $now,
                ]);

                if (! $product->source_parser_item_id) {
                    continue;
                }

                DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                    'official_source_url' => $url,
                    'official_source_domain' => 'eng.jtc.com.tw',
                    'official_source_confidence' => 100,
                    'fallback_source_url' => null,
                    'fallback_source_domain' => null,
                    'fallback_source_used' => false,
                    'source_match_confidence' => 100,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('product_parser_sources')->updateOrInsert(
                    ['parser_item_id' => $product->source_parser_item_id, 'url' => $url],
                    [
                        'domain' => 'eng.jtc.com.tw',
                        'title' => 'JTC official product page',
                        'snippet' => 'Exact SKU verified in the official JTC product result or product family table.',
                        'source_type' => 'official_manufacturer',
                        'confidence_score' => 100,
                        'raw_data_json' => json_encode([
                            'sku' => $sku,
                            'verification' => 'exact_official_sku',
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
        // Exact official-source verification is intentionally irreversible.
    }
};
