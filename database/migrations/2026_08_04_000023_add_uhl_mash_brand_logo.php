<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('brands')
            ->where('slug', 'uhl-mash')
            ->update([
                'logo' => '/images/brand/uhl-mash.svg',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('brands')
            ->where('slug', 'uhl-mash')
            ->update([
                'logo' => null,
                'updated_at' => now(),
            ]);
    }
};
