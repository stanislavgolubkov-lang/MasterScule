<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_parser_category_learnings', function (Blueprint $table) {
            $table->id();
            $table->string('key_type', 40);
            $table->char('key_hash', 40);
            $table->text('key_value');
            $table->string('brand_key', 80)->default('*');
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('source', 60);
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->unsignedInteger('observations')->default(1);
            $table->json('context_json')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['key_type', 'key_hash', 'brand_key', 'category_id'],
                'parser_category_learning_unique'
            );
            $table->index(
                ['key_type', 'key_hash', 'brand_key'],
                'parser_category_learning_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_parser_category_learnings');
    }
};
