<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('brands')
            ->where('slug', 'uhl-mash')
            ->orWhere('name', 'УХЛ-МАШ')
            ->first();

        $values = [
            'name' => 'УХЛ-МАШ',
            'slug' => 'uhl-mash',
            'description' => 'Mobilier metalic profesional pentru atelier, service și spații de depozitare.',
            'logo' => '/images/brand/uhl-mash.svg',
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

    public function down(): void
    {
        DB::table('brands')->where('slug', 'uhl-mash')->delete();
    }
};
