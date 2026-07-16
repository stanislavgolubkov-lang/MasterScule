<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_parser_items', function (Blueprint $table) {
            $table->string('processing_stage')->nullable()->after('status')->index();
            $table->timestamp('tristool_checked_at')->nullable()->after('processing_stage');
            $table->timestamp('external_checked_at')->nullable()->after('tristool_checked_at');
            $table->unsignedSmallInteger('external_attempts')->default(0)->after('external_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('product_parser_items', function (Blueprint $table) {
            $table->dropIndex(['processing_stage']);
            $table->dropColumn([
                'processing_stage',
                'tristool_checked_at',
                'external_checked_at',
                'external_attempts',
            ]);
        });
    }
};
