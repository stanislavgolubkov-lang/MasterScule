<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->brands() as $brand) {
            $existing = DB::table('brands')
                ->where('slug', $brand['slug'])
                ->orWhere('name', $brand['name'])
                ->first();

            $values = [
                ...$brand,
                'is_featured' => false,
                'is_active' => true,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('brands')->where('id', $existing->id)->update($values);
            } else {
                DB::table('brands')->insert([...$values, 'created_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->brands() as $brand) {
            DB::table('brands')
                ->where('slug', $brand['slug'])
                ->where('logo', $brand['logo'])
                ->update([
                    'logo' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    /** @return array<int, array{name: string, slug: string, logo: string, description: string}> */
    private function brands(): array
    {
        return [
            [
                'name' => 'HAZET',
                'slug' => 'hazet',
                'logo' => '/images/brand/hazet.svg',
                'description' => 'Scule profesionale și echipamente pentru atelier.',
            ],
            [
                'name' => 'VIGOR',
                'slug' => 'vigor',
                'logo' => '/images/brand/vigor.svg',
                'description' => 'Scule și echipamente profesionale pentru service auto.',
            ],
        ];
    }
};
