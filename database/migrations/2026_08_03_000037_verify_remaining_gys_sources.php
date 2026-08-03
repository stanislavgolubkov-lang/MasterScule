<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sources = [
            '041592' => [
                'url' => 'https://www.groupe-mlv-france.fr/accessoires-et-consommables/489-177-gaine-acier-3-m.html#/96-diametre_gaine-06_08',
                'domain' => 'www.groupe-mlv-france.fr',
                'type' => 'official_distributor',
            ],
            '032132' => [
                'url' => 'https://www.gys.fr/pdf/datasheet/uk/032132.pdf',
                'domain' => 'www.gys.fr',
                'type' => 'official_manufacturer_document',
            ],
            '045354' => [
                'url' => 'https://www.gys.fr/pdf/datasheet/uk/045354.pdf',
                'domain' => 'www.gys.fr',
                'type' => 'official_manufacturer_document',
            ],
            '050693' => [
                'url' => 'https://www.groupe-mlv-france.fr/outils-accessoires/229-barre-de-debosselage.html',
                'domain' => 'www.groupe-mlv-france.fr',
                'type' => 'official_distributor',
            ],
            '045224' => [
                'url' => 'https://www.groupe-mlv-france.fr/protection-du-carrossier-epi/180-cagoule-textile-de-protection.html',
                'domain' => 'www.groupe-mlv-france.fr',
                'type' => 'official_distributor',
            ],
            '082809' => [
                'url' => 'https://www.gys.fr/pdf/datasheet/uk/082809.pdf',
                'domain' => 'www.gys.fr',
                'type' => 'official_manufacturer_document',
            ],
        ];

        DB::transaction(function () use ($sources): void {
            $brandId = DB::table('brands')->where('name', 'GYS')->value('id');
            if (! $brandId) {
                return;
            }

            foreach ($sources as $sku => $source) {
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
                $sourceUrls[] = $source['url'];

                DB::table('products')->where('id', $product->id)->update([
                    'parser_source_urls' => json_encode(array_values(array_unique($sourceUrls)), JSON_UNESCAPED_SLASHES),
                    'source_url' => $source['url'],
                    'source_domain' => $source['domain'],
                    'source_type' => $source['type'],
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
                    'official_source_url' => $source['url'],
                    'official_source_domain' => $source['domain'],
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
                    ['parser_item_id' => $product->source_parser_item_id, 'url' => $source['url']],
                    [
                        'domain' => $source['domain'],
                        'title' => 'Verified GYS source for reference '.$sku,
                        'snippet' => 'Exact GYS reference verified in a manufacturer document or reviewed distributor page.',
                        'source_type' => $source['type'],
                        'confidence_score' => 100,
                        'raw_data_json' => json_encode([
                            'sku' => $sku,
                            'verification' => 'exact_gys_reference',
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
        // Exact source verification is intentionally irreversible.
    }
};
