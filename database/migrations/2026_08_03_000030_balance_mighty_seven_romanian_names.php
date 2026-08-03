<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $brandId = DB::table('brands')->where('name', 'M7 / Mighty Seven')->value('id');
        if (! $brandId) {
            return;
        }

        DB::transaction(function () use ($brandId): void {
            DB::table('products')
                ->where('brand_id', $brandId)
                ->orderBy('id')
                ->get()
                ->each(function (object $product): void {
                    $oldName = (string) $product->name_ro;
                    $newName = $this->sanitizeName($oldName);
                    if ($newName === $oldName) {
                        return;
                    }

                    $descriptionRo = $this->replacePrefix((string) $product->description_ro, $oldName, $newName);
                    $shortRo = mb_strimwidth($descriptionRo, 0, 240, '');
                    $updates = [
                        'name_ro' => $newName,
                        'description_ro' => $descriptionRo,
                        'short_description_ro' => $shortRo,
                        'updated_at' => now(),
                    ];
                    DB::table('products')->where('id', $product->id)->update($updates);

                    $parserUpdates = $updates;
                    $query = DB::table('product_parser_items');
                    $product->source_parser_item_id
                        ? $query->where('id', $product->source_parser_item_id)->update($parserUpdates)
                        : $query->where('sku', $product->sku)->update($parserUpdates);
                });
        });
    }

    private function sanitizeName(string $value): string
    {
        $value = str_replace(['(', ')', '[', ']'], ' ', $value);
        $value = preg_replace('/\s*[,;]\s*(?:[,;]\s*)+/u', ', ', $value) ?: $value;
        $value = preg_replace('/(^|\s)[,;]+\s*/u', '$1', $value) ?: $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        return trim($value, " \t\n\r\0\x0B,.;-–—");
    }

    private function replacePrefix(string $text, string $oldName, string $newName): string
    {
        if ($oldName !== '' && str_starts_with($text, $oldName)) {
            return $newName.substr($text, strlen($oldName));
        }

        return $text;
    }

    public function down(): void
    {
        // Balanced reviewed names are intentionally retained.
    }
};
