<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $skus = [
            'NC-0208M', 'JC-507A', 'ZB-812XL', 'ZB-812XXL', 'ZB-814XL', 'ZB-814XXL', 'ZB-814M',
            'QB-91110', 'QB-91106', 'QB-91108', 'QB-91210', 'QB-91208', 'QB-7114', 'QB-7115M',
            'QP-327M', 'QB-9315', 'QB-9305', 'QB-9306', 'QB-49602P08', 'QB-51602P21',
        ];
        $now = now();

        DB::table('products')->whereIn('sku', $skus)->orderBy('id')->chunkById(100, function ($products) use ($now): void {
            foreach ($products as $product) {
                $updates = [];
                foreach (['name_ro', 'short_description_ro', 'description_ro'] as $field) {
                    $updates[$field] = $this->normalize((string) $product->{$field});
                }
                $updates['needs_translation_review'] = false;
                $updates['updated_at'] = $now;
                DB::table('products')->where('id', $product->id)->update($updates);
                DB::table('product_parser_items')->where('sku', $product->sku)->update($updates);
            }
        });
    }

    private function normalize(string $value): string
    {
        return strtr($value, ['мм' => 'mm', 'М' => 'M', 'Р' => 'P', 'х' => '×']);
    }

    public function down(): void
    {
        // Invalid Cyrillic units are intentionally not restored.
    }
};
