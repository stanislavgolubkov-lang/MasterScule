<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->images() as $slug => $image) {
            DB::table('categories')
                ->where('slug', $slug)
                ->update(['image' => $image]);
        }
    }

    public function down(): void
    {
        $fallbacks = [
            'mobilier-pentru-service' => '/images/categories/dulapuri-organizare.svg',
            'scule-speciale-auto' => '/images/categories/echipamente-service.svg',
            'instrument-manual' => '/images/categories/chei-surubelnite.svg',
            'scule-pneumatice' => '/images/categories/scule-pneumatice.svg',
            'electroinstrumente' => '/images/categories/echipamente-service.svg',
            'instrumente-cu-acumulator' => '/images/categories/scule-pneumatice.svg',
            'instrumente-electromontaj' => '/images/categories/chei-surubelnite.svg',
            'instrumente-de-masurare' => '/images/categories/cheie-dinamometrica.svg',
            'accesorii-si-consumabile' => '/images/categories/tubulare-clichete.svg',
        ];

        foreach ($fallbacks as $slug => $image) {
            DB::table('categories')
                ->where('slug', $slug)
                ->update(['image' => $image]);
        }
    }

    private function images(): array
    {
        return [
            'mobilier-pentru-service' => '/images/categories/mobilier-pentru-service.png',
            'scule-speciale-auto' => '/images/categories/scule-speciale-auto.png',
            'instrument-manual' => '/images/categories/instrument-manual.png',
            'scule-pneumatice' => '/images/categories/scule-pneumatice.png',
            'electroinstrumente' => '/images/categories/electroinstrumente.png',
            'instrumente-cu-acumulator' => '/images/categories/instrumente-cu-acumulator.png',
            'instrumente-electromontaj' => '/images/categories/instrumente-electromontaj.png',
            'instrumente-de-masurare' => '/images/categories/instrumente-de-masurare.png',
            'accesorii-si-consumabile' => '/images/categories/accesorii-si-consumabile.png',
        ];
    }
};
