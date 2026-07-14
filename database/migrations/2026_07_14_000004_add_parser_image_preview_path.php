<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_parser_image_assets', function (Blueprint $table) {
            $table->string('preview_path')->nullable()->after('processed_path');
        });
    }

    public function down(): void
    {
        Schema::table('product_parser_image_assets', function (Blueprint $table) {
            $table->dropColumn('preview_path');
        });
    }
};
