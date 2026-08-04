<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $thinkcar = DB::table('brands')
            ->whereIn('slug', ['thinkcar', 'thinckar'])
            ->orWhereIn('name', ['THINKCAR', 'THINCKAR'])
            ->orderByRaw("CASE WHEN slug = 'thinkcar' OR name = 'THINKCAR' THEN 0 ELSE 1 END")
            ->first();

        if ($thinkcar) {
            $duplicateIds = DB::table('brands')
                ->where(function ($query) {
                    $query->whereIn('slug', ['thinkcar', 'thinckar'])
                        ->orWhereIn('name', ['THINKCAR', 'THINCKAR']);
                })
                ->where('id', '!=', $thinkcar->id)
                ->pluck('id');

            if ($duplicateIds->isNotEmpty()) {
                DB::table('products')->whereIn('brand_id', $duplicateIds)->update(['brand_id' => $thinkcar->id]);
                DB::table('brands')->whereIn('id', $duplicateIds)->delete();
            }
        }

        foreach ([
            ['name' => 'SPIN', 'slug' => 'spin', 'logo' => '/images/brand/spin.png', 'description' => 'Echipamente profesionale pentru service și întreținere auto.'],
            ['name' => 'TELWIN', 'slug' => 'telwin', 'logo' => '/images/brand/telwin.svg', 'description' => 'Echipamente profesionale pentru sudură, încărcare și pornire.'],
            ['name' => 'THINKCAR', 'slug' => 'thinkcar', 'logo' => '/images/brand/thinkcar.png', 'description' => 'Echipamente și soluții profesionale pentru diagnosticarea automobilelor.'],
        ] as $brand) {
            $existing = $brand['slug'] === 'thinkcar' && $thinkcar
                ? $thinkcar
                : DB::table('brands')
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
        // Brand rows are retained to avoid cascading deletion of imported products.
    }
};
