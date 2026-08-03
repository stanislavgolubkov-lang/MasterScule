<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $skus = [
            'QB-0808M',
            '041592',
            '032132',
            '040168/1',
            '045354',
            '027336',
            '025882',
            '087323',
            '085879',
            '054677',
            '029378',
            '056992',
            '058583',
            '060791',
            '050693',
            '062191',
            '057449',
            '045224',
            '082809',
            '066489',
            '079878',
            '052994',
            '024175',
            '024168',
            '024205',
            '024182',
            '040175/1',
        ];

        DB::transaction(function () use ($skus): void {
            $now = now();
            $products = DB::table('products')
                ->whereIn('sku', $skus)
                ->get(['id', 'source_parser_item_id']);

            DB::table('products')
                ->whereIn('sku', $skus)
                ->update([
                    'needs_image_review' => false,
                    'updated_at' => $now,
                ]);

            $parserItemIds = $products
                ->pluck('source_parser_item_id')
                ->filter()
                ->values()
                ->all();

            if ($parserItemIds === []) {
                return;
            }

            DB::table('product_parser_image_assets')
                ->whereIn('parser_item_id', $parserItemIds)
                ->where('is_selected', true)
                ->update([
                    'needs_review' => false,
                    'updated_at' => $now,
                ]);

            DB::table('product_parser_items')
                ->whereIn('id', $parserItemIds)
                ->update([
                    'needs_image_review' => false,
                    'image_reviewed_at' => $now,
                    'updated_at' => $now,
                ]);
        });
    }

    public function down(): void
    {
        // A completed visual review is intentionally not reverted.
    }
};
