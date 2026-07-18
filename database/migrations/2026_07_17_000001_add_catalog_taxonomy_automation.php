<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_assignable')->default(true)->after('is_active');
            $table->boolean('is_menu_visible')->default(true)->after('is_assignable');
            $table->string('source')->default('catalog')->after('is_menu_visible');
            $table->string('taxonomy_version')->nullable()->after('source');
        });

        Schema::create('product_category_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('previous_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('selected_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('taxonomy_version');
            $table->string('input_hash', 64);
            $table->string('mode')->default('deterministic');
            $table->string('status')->default('proposed');
            $table->string('model')->nullable();
            $table->string('verifier_model')->nullable();
            $table->decimal('classifier_confidence', 5, 4)->default(0);
            $table->decimal('verifier_confidence', 5, 4)->default(0);
            $table->json('evidence')->nullable();
            $table->json('alternatives')->nullable();
            $table->json('validation_errors')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['taxonomy_version', 'input_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category_decisions');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['is_assignable', 'is_menu_visible', 'source', 'taxonomy_version']);
        });
    }
};
