<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_parser_items', function (Blueprint $table) {
            $table->string('official_source_url', 1200)->nullable();
            $table->string('official_source_domain')->nullable();
            $table->unsignedTinyInteger('official_source_confidence')->nullable();
            $table->string('fallback_source_url', 1200)->nullable();
            $table->string('fallback_source_domain')->nullable();
            $table->boolean('fallback_source_used')->default(false);
            $table->unsignedTinyInteger('source_match_confidence')->nullable();
            $table->boolean('needs_source_review')->default(true);
            $table->boolean('needs_content_review')->default(true);
            $table->boolean('generated_content')->default(false);
            $table->string('content_source_type')->nullable();
            $table->string('image_source_type')->nullable();
            $table->string('translation_source_type')->nullable();
            $table->timestamp('source_reviewed_at')->nullable();
            $table->timestamp('image_reviewed_at')->nullable();
            $table->timestamp('translation_reviewed_at')->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('source_url', 1200)->nullable();
            $table->string('source_domain')->nullable();
            $table->string('source_type')->nullable();
            $table->boolean('fallback_source_used')->default(false);
            $table->boolean('needs_source_review')->default(false);
            $table->boolean('needs_content_review')->default(false);
            $table->boolean('generated_content')->default(false);
            $table->timestamp('source_reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('product_parser_items', function (Blueprint $table) {
            $table->dropColumn([
                'official_source_url', 'official_source_domain', 'official_source_confidence',
                'fallback_source_url', 'fallback_source_domain', 'fallback_source_used',
                'source_match_confidence', 'needs_source_review', 'needs_content_review',
                'generated_content', 'content_source_type', 'image_source_type',
                'translation_source_type', 'source_reviewed_at', 'image_reviewed_at',
                'translation_reviewed_at',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'source_url', 'source_domain', 'source_type', 'fallback_source_used',
                'needs_source_review', 'needs_content_review', 'generated_content', 'source_reviewed_at',
            ]);
        });
    }
};
