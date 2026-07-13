<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('source_url', 1200)->nullable()->after('path');
            $table->string('source_page_url', 1200)->nullable()->after('source_url');
            $table->string('source_domain')->nullable()->index()->after('source_page_url');
            $table->boolean('is_official')->default(false)->index()->after('source_domain');
            $table->string('mime_type', 80)->nullable()->after('is_official');
            $table->unsignedInteger('width')->nullable()->after('mime_type');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->unsignedBigInteger('file_size')->nullable()->after('height');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex(['source_domain']);
            $table->dropIndex(['is_official']);
            $table->dropColumn([
                'source_url',
                'source_page_url',
                'source_domain',
                'is_official',
                'mime_type',
                'width',
                'height',
                'file_size',
            ]);
        });
    }
};
