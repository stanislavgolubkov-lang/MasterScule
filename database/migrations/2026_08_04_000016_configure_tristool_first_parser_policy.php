<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'product_parser')->first();
        if (! $row) {
            return;
        }

        $settings = filled($row->value)
            ? (json_decode((string) $row->value, true) ?: [])
            : [];

        $settings['official_sources_enabled'] = true;
        $settings['official_source_priority'] = false;
        $settings['tristools_fallback_enabled'] = true;
        $settings['tristools_image_first'] = true;
        $settings['tristools_content_first'] = true;
        $settings['tristools_fallback_only'] = false;
        $settings['tristools'] = array_replace($settings['tristools'] ?? [], [
            'enabled' => true,
        ]);

        DB::table('settings')->where('key', 'product_parser')->update([
            'value' => json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Source priority is an operational safety policy and is intentionally retained.
    }
};
