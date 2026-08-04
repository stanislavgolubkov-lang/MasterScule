<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->logos() as $slug => $logo) {
            DB::table('brands')->where('slug', $slug)->update([
                'logo' => $logo,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->logos() as $slug => $logo) {
            DB::table('brands')
                ->where('slug', $slug)
                ->where('logo', $logo)
                ->update([
                    'logo' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    /** @return array<string, string> */
    private function logos(): array
    {
        return [
            'spin' => '/images/brand/spin.png',
            'telwin' => '/images/brand/telwin.svg',
            'thinkcar' => '/images/brand/thinkcar.png',
        ];
    }
};
