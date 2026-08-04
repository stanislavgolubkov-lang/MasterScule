<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();

            $products = DB::table('products')
                ->where(function ($query): void {
                    $query->where('name_ro', 'like', '%filtru%ulei%')
                        ->orWhere('description_ro', 'like', '%filtru%ulei%');
                })
                ->get(['id', 'name_ro', 'short_description_ro', 'description_ro']);

            foreach ($products as $product) {
                $updates = [];
                foreach (['name_ro', 'short_description_ro', 'description_ro'] as $column) {
                    $value = (string) $product->{$column};
                    $updated = str_ireplace('Extractor filtru ulei', 'Extractor pentru filtru de ulei', $value);
                    $updated = preg_replace('/\b(\d+)\s*g\./iu', '$1 muchii', $updated) ?? $updated;
                    if ($updated !== $value) {
                        $updates[$column] = $updated;
                    }
                }
                if ($updates !== []) {
                    $updates['updated_at'] = $now;
                    DB::table('products')->where('id', $product->id)->update($updates);
                }
            }

            $mallets = [
                '7842-22' => 'Ciocan cu capete interschimbabile din poliuretan KING TONY 7842-22, 22 mm, 110 g',
                '7842-28' => 'Ciocan cu capete interschimbabile din poliuretan KING TONY 7842-28, 28 mm, 165 g',
                '7842-35' => 'Ciocan cu capete interschimbabile din poliuretan KING TONY 7842-35, 38 mm, 305 g',
                '7842-45' => 'Ciocan cu capete interschimbabile din poliuretan KING TONY 7842-45, 45 mm, 420 g',
                '7842-60' => 'Ciocan cu capete interschimbabile din poliuretan KING TONY 7842-60, 60 mm, 895 g',
            ];

            foreach ($mallets as $sku => $title) {
                $product = DB::table('products')->where('sku', $sku)->first(['id', 'name_ro', 'short_description_ro', 'description_ro']);
                if (! $product) {
                    continue;
                }
                DB::table('products')->where('id', $product->id)->update([
                    'name_ro' => $title,
                    'short_description_ro' => str_replace((string) $product->name_ro, $title, (string) $product->short_description_ro),
                    'description_ro' => str_replace((string) $product->name_ro, $title, (string) $product->description_ro),
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Verified catalog corrections are intentionally not reverted.
    }
};
