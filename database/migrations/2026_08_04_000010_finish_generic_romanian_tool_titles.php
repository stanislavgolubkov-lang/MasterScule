<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $titles = [
            '67G1-09' => 'Clește de sertizat conectori tip pin KING TONY 67G1-09',
            '68SS-10' => 'Clește drept pentru inele de siguranță exterioare KING TONY 68SS-10, 250 mm',
            'JTC-5618' => 'Clește pentru dezizolare JTC-5618, 0,5–2,0 mm, mânere verzi',
            'JTC-5619' => 'Clește pentru dezizolare JTC-5619, 1,9–3,2 mm, mânere roșii',
            'JTC-5620' => 'Clește automat pentru dezizolare JTC-5620, 0,2–5 mm / 8 mm',
        ];

        DB::transaction(function () use ($titles, $now): void {
            foreach ($titles as $sku => $title) {
                $product = DB::table('products')->where('sku', $sku)->first([
                    'id', 'name_ro', 'short_description_ro', 'description_ro',
                ]);
                if (! $product) {
                    continue;
                }

                $updates = ['name_ro' => $title, 'updated_at' => $now];
                foreach (['short_description_ro', 'description_ro'] as $column) {
                    $updates[$column] = str_replace((string) $product->name_ro, $title, (string) $product->{$column});
                }
                DB::table('products')->where('id', $product->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        // Verified terminology corrections are intentionally not reverted.
    }
};
