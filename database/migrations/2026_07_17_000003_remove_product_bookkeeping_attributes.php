<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ignored = [
            'brand', 'бренд', 'marca', 'sku', 'артикул', 'cod produs', 'group', 'группа', 'grup',
            'retail price', 'price retail', 'розничная цена', 'цена розничная', 'pret retail', 'preț retail',
            'price source', 'источник цены', 'sursa pretului', 'sursa prețului',
            'select all', 'выбрать все', 'selecteaza tot', 'selectează tot',
        ];

        DB::table('products')->select(['id', 'attributes'])->whereNotNull('attributes')
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($ignored): void {
                foreach ($products as $product) {
                    $attributes = json_decode((string) $product->attributes, true);
                    if (! is_array($attributes)) {
                        continue;
                    }

                    $clean = collect($attributes)->reject(function ($value, $key) use ($ignored): bool {
                        $normalized = mb_strtolower(trim((string) preg_replace('/[\s:_-]+/u', ' ', (string) $key)));

                        return in_array($normalized, $ignored, true);
                    })->all();

                    if ($clean !== $attributes) {
                        DB::table('products')->where('id', $product->id)->update([
                            'attributes' => json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Removed duplicate bookkeeping attributes are intentionally not reconstructed.
    }
};
