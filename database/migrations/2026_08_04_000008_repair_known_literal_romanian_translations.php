<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $titles = [
                '041424' => 'Pistolet de sudură MIG-MAG GYS 041424, MB15, 150 A, răcire cu aer',
                '041462' => 'Pistolet de sudură MIG-MAG GYS 041462, MB15 ALU, 150 A, răcire cu aer',
                '043268' => 'Pistolet de sudură MIG-MAG GYS 043268, MB15 GRIP, 150 A, răcire cu aer',
                '063754' => 'Pistolet de sudură MIG-MAG GYS 063754, MB15, 150 A, răcire cu aer',
                '063761' => 'Pistolet de sudură MIG-MAG GYS 063761, ALU, 150 A, răcire cu aer',
                '056831' => 'Punte de tragere pentru redresarea caroseriei GYS 056831',
                '079649' => 'Clemă specială pentru caroserie GYS 079649',
            ];

            foreach (['4105PR', '4109PR', '4110PR', '4116PR', '4119PR', '4120PR', '4128PR', '4136PR', '9-4129PRV', '9-4147PRV'] as $sku) {
                $titles[$sku] = "Set de capete cu biți KING TONY {$sku}, 1/2″";
            }

            foreach ($titles as $sku => $title) {
                DB::table('products')->where('sku', $sku)->update(['name_ro' => $title, 'updated_at' => $now]);
            }

            $replacements = [
                'Lanterna de sudura' => 'Pistoletul de sudură',
                'lanterna de sudura' => 'pistoletul de sudură',
                'Lanterna de sudură' => 'Pistoletul de sudură',
                'lanterna de sudură' => 'pistoletul de sudură',
                'Pod care trag corpul' => 'Punte pentru tragerea caroseriei',
                'pod care trag corpul' => 'punte pentru tragerea caroseriei',
                'Clemă de corp' => 'Clemă pentru caroserie',
                'clemă de corp' => 'clemă pentru caroserie',
                'Set prize de capăt' => 'Set de capete cu biți',
                'set prize de capăt' => 'set de capete cu biți',
                'Cap mufă' => 'Cap tubular',
                'cap mufă' => 'cap tubular',
            ];

            DB::table('products')->orderBy('id')->chunkById(250, function ($products) use ($replacements, $now): void {
                foreach ($products as $product) {
                    $updates = [];
                    foreach (['short_description_ro', 'description_ro'] as $column) {
                        $value = (string) $product->{$column};
                        $updated = str_replace(array_keys($replacements), array_values($replacements), $value);
                        if ($updated !== $value) {
                            $updates[$column] = $updated;
                        }
                    }
                    if ($updates !== []) {
                        $updates['updated_at'] = $now;
                        DB::table('products')->where('id', $product->id)->update($updates);
                    }
                }
            });
        });
    }

    public function down(): void
    {
        // Verified terminology corrections are intentionally not reverted.
    }
};
