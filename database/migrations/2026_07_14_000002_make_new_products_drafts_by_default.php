<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->change();
            $table->string('status')->default('draft')->change();
            $table->string('approval_status')->default('pending_review')->change();
            $table->boolean('needs_review')->default(true)->change();
            $table->boolean('needs_image_review')->default(true)->change();
            $table->boolean('needs_category_review')->default(true)->change();
            $table->boolean('needs_translation_review')->default(true)->change();
            $table->boolean('needs_price_review')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->change();
            $table->string('status')->default('published')->change();
            $table->string('approval_status')->default('approved')->change();
            $table->boolean('needs_review')->default(false)->change();
            $table->boolean('needs_image_review')->default(false)->change();
            $table->boolean('needs_category_review')->default(false)->change();
            $table->boolean('needs_translation_review')->default(false)->change();
            $table->boolean('needs_price_review')->default(false)->change();
        });
    }
};
