<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $verifiedSkus = [
            'QB-0808M', 'SX-3201', 'DD-20502', 'DH-20502', 'NC-0208M', 'QE-231P46',
            'JC-507A', 'QD-924_1', 'QD-932_1', 'ZB-812XL', 'ZB-812XXL', 'ZB-814XL',
            'ZB-814XXL', 'ZB-814M', 'JTC-6848', 'JTC-7804', 'JTC-43930', 'JTC-849022',
            'JTC-849023', 'JTC-448226', 'JTC-5362', 'JTC-6672', 'JTC-2594', 'JTC-1450',
            'JTC-1261', '4768-15G', '753A-370', '39110312M', '20418PRUS', '6731-09MUS',
            '34467-1AG-1', '34367-2AG-1',
        ];

        DB::transaction(function () use ($verifiedSkus): void {
            DB::table('products')
                ->whereIn('sku', $verifiedSkus)
                ->where('source_domain', 'tristool.md')
                ->whereNotNull('source_url')
                ->select('id', 'sku', 'source_url', 'source_parser_item_id')
                ->orderBy('id')
                ->get()
                ->each(function (object $product): void {
                    $now = now();

                    DB::table('products')->where('id', $product->id)->update([
                        'source_type' => 'verified_supplier',
                        'fallback_source_used' => false,
                        'needs_source_review' => false,
                        'source_reviewed_at' => $now,
                        'parser_confidence' => 96,
                        'updated_at' => $now,
                    ]);

                    if (! $product->source_parser_item_id) {
                        return;
                    }

                    DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                        'official_source_url' => $product->source_url,
                        'official_source_domain' => 'tristool.md',
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
                        ['parser_item_id' => $product->source_parser_item_id, 'url' => $product->source_url],
                        [
                            'domain' => 'tristool.md',
                            'title' => 'TrisTool exact product page',
                            'snippet' => 'Supplier product page verified by exact SKU in the product HTML.',
                            'source_type' => 'verified_supplier',
                            'confidence_score' => 96,
                            'raw_data_json' => json_encode([
                                'sku' => $product->sku,
                                'verification' => 'exact_html_sku',
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                });
        });
    }

    public function down(): void
    {
        // Exact supplier verification is intentionally irreversible.
    }
};
