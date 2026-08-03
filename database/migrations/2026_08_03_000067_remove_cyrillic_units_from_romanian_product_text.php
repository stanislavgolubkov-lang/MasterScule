<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('products')->orderBy('id')->chunkById(500, function ($products): void {
                foreach ($products as $product) {
                    $updates = [];
                    foreach (['name_ro', 'short_description_ro', 'description_ro'] as $column) {
                        $value = (string) $product->{$column};
                        $clean = str_replace(['мм', 'см', 'кг'], ['mm', 'cm', 'kg'], $value);
                        if ($clean !== $value) {
                            $updates[$column] = $clean;
                        }
                    }

                    if ($updates !== []) {
                        $updates['updated_at'] = now();
                        DB::table('products')->where('id', $product->id)->update($updates);
                    }
                }
            });
        });
    }

    public function down(): void
    {
        // Localized unit cleanup is intentionally irreversible.
    }
};
