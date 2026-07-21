<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $skus = ['044128', '044135', '054868', '054813', '054806', '054790', '055469', '066304', '067622', '066298'];
        $products = DB::table('products')->whereIn('sku', $skus)->get();

        DB::transaction(function () use ($products): void {
            foreach ($products as $product) {
                $now = now();
                $updates = [];
                foreach (['name', 'name_ru', 'short_description', 'short_description_ru', 'description', 'description_ru'] as $field) {
                    $updates[$field] = $this->russianUnits((string) $product->{$field});
                }
                $updates['updated_at'] = $now;
                DB::table('products')->where('id', $product->id)->update($updates);

                if ($product->source_parser_item_id) {
                    DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                        'name_ru' => $this->russianUnits((string) $product->name_ru),
                        'short_description_ru' => $this->russianUnits((string) $product->short_description_ru),
                        'description_ru' => $this->russianUnits((string) $product->description_ru),
                        'found_title' => $this->russianUnits((string) $product->name_ru),
                        'found_description' => $this->russianUnits((string) $product->description_ru),
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    private function russianUnits(string $value): string
    {
        return str_replace([' g', ' mm', ' cm'], [' г', ' мм', ' см'], $value);
    }

    public function down(): void
    {
        // Correct Russian unit labels are intentionally retained.
    }
};
