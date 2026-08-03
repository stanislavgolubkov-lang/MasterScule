<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $skus = [
            'SC-2B',
            'SC-2A',
            'HT4R041',
            'HT1W441',
            'HT1W446',
            'HT4R892',
            '9DT11-1',
            '9DT11-2',
            '9DT11-3',
            '9DT11-4',
            '9DT11-5',
            '9DT11-6',
            '9DT11-7',
            '9DT11-8',
            '9DT11-9',
            '9DT11-10',
            '9DT11-11',
            '9DT11-12',
        ];

        DB::transaction(function () use ($skus): void {
            $now = now();
            $products = DB::table('products')
                ->whereIn('sku', $skus)
                ->get(['id', 'source_parser_item_id']);
            $parserItemIds = $products->pluck('source_parser_item_id')->filter()->values()->all();

            DB::table('products')->whereIn('sku', $skus)->update([
                'needs_image_review' => false,
                'updated_at' => $now,
            ]);

            if ($parserItemIds === []) {
                return;
            }

            DB::table('product_parser_image_assets')
                ->whereIn('parser_item_id', $parserItemIds)
                ->where('is_selected', true)
                ->update(['needs_review' => false, 'updated_at' => $now]);
            DB::table('product_parser_items')->whereIn('id', $parserItemIds)->update([
                'needs_image_review' => false,
                'image_reviewed_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        // Completed visual reviews are intentionally not reverted.
    }
};
