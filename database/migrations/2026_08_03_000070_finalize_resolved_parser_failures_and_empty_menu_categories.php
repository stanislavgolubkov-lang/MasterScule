<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $emptyCategorySlugs = [
            'cricuri-si-ridicare',
            'scule-motor-frane-suspensie',
            'accesorii-pneumatice',
            'alte-scule-pneumatice',
            'vulcanizare',
            'discuri-perii-abrazive',
        ];

        DB::transaction(function () use ($now, $emptyCategorySlugs): void {
            DB::table('product_parser_items')
                ->where('status', 'draft_created')
                ->whereNotNull('created_product_id')
                ->whereIn('created_product_id', fn ($query) => $query->select('id')->from('products')->where('status', 'published'))
                ->update([
                    'status' => 'approved',
                    'approval_status' => 'approved',
                    'processing_stage' => 'published',
                    'updated_at' => $now,
                ]);

            DB::table('failed_jobs')
                ->whereBetween('id', [33, 47])
                ->where('failed_at', '<=', '2026-07-18 23:59:59')
                ->where('exception', 'like', '%database is locked%')
                ->delete();

            DB::table('categories')->whereIn('slug', $emptyCategorySlugs)->update([
                'is_menu_visible' => false,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        // Resolved queue failures are not recreated; categories remain available but hidden until populated.
    }
};
