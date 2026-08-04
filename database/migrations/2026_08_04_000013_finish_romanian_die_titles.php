<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $titles = [
            '12939MQ-A23' => 'Filieră KING TONY 12939MQ-A23, M3×0,5',
            '12939MQ-A25' => 'Filieră KING TONY 12939MQ-A25, M4×0,7',
            '12939MQ-A33' => 'Filieră KING TONY 12939MQ-A33, M8×1,0',
            '12939MQ-A36' => 'Filieră KING TONY 12939MQ-A36, M10×1,5',
            '12939MQ-A37' => 'Filieră KING TONY 12939MQ-A37, M12×1,5',
        ];

        DB::transaction(function () use ($titles): void {
            $now = now();

            foreach ($titles as $sku => $title) {
                $product = DB::table('products')->where('sku', $sku)->first([
                    'id', 'name_ro', 'short_description_ro', 'description_ro',
                ]);
                if (! $product) {
                    continue;
                }

                $updates = ['name_ro' => $title, 'updated_at' => $now];
                foreach (['short_description_ro', 'description_ro'] as $column) {
                    $value = (string) $product->{$column};
                    if ($value !== '' && (string) $product->name_ro !== '') {
                        $updates[$column] = str_replace((string) $product->name_ro, $title, $value);
                    }
                }

                DB::table('products')->where('id', $product->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        // Verified catalog corrections are intentionally retained.
    }
};
