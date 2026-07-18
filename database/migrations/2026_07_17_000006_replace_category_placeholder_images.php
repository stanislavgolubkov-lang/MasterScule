<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->setImages([
            'echipamente-pentru-service' => '/images/categories/echipamente-pentru-service.png',
            'sudura-richtuire-vopsire' => '/images/categories/sudura-richtuire-vopsire.png',
            'vulcanizare' => '/images/categories/vulcanizare.png',
            'echipament-protectie' => '/images/categories/echipament-protectie.png',
        ]);
    }

    public function down(): void
    {
        $this->setImages([
            'echipamente-pentru-service' => '/images/categories/echipamente-service.svg',
            'sudura-richtuire-vopsire' => '/images/categories/echipamente-service.svg',
            'vulcanizare' => '/images/categories/cric-ridicare.svg',
            'echipament-protectie' => '/images/categories/echipamente-service.svg',
        ]);
    }

    private function setImages(array $images): void
    {
        foreach ($images as $slug => $image) {
            DB::table('categories')->where('slug', $slug)->update(['image' => $image]);
        }
    }
};
