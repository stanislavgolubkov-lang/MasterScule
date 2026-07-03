<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            $needsStatus = ! Schema::hasColumn('products', 'status');
            $needsParserConfidence = ! Schema::hasColumn('products', 'parser_confidence');
            $needsParserSources = ! Schema::hasColumn('products', 'parser_source_urls');

            Schema::table('products', function (Blueprint $table) use ($needsStatus, $needsParserConfidence, $needsParserSources) {
                if ($needsStatus) {
                    $table->string('status')->default('published')->after('stock_status');
                }

                if ($needsParserConfidence) {
                    $table->unsignedTinyInteger('parser_confidence')->nullable()->after('status');
                }

                if ($needsParserSources) {
                    $table->json('parser_source_urls')->nullable()->after('parser_confidence');
                }
            });
        }

        Schema::create('product_parser_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('source_type')->default('single');
            $table->unsignedInteger('sku_count')->default(0);
            $table->string('status')->default('pending')->index();
            $table->json('options_json')->nullable();
            $table->json('log_json')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_parser_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('product_parser_batches')->cascadeOnDelete();
            $table->string('sku')->index();
            $table->string('brand')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('queued')->index();
            $table->unsignedTinyInteger('confidence_score')->nullable();
            $table->string('found_title')->nullable();
            $table->longText('found_description')->nullable();
            $table->json('found_specs_json')->nullable();
            $table->json('found_images_json')->nullable();
            $table->json('selected_images_json')->nullable();
            $table->json('processed_images_json')->nullable();
            $table->json('source_urls_json')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('existing_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('product_parser_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parser_item_id')->constrained('product_parser_items')->cascadeOnDelete();
            $table->string('url', 1200);
            $table->string('domain')->nullable();
            $table->string('title')->nullable();
            $table->text('snippet')->nullable();
            $table->string('source_type')->default('generic');
            $table->unsignedTinyInteger('confidence_score')->nullable();
            $table->json('raw_data_json')->nullable();
            $table->timestamps();
        });

        Schema::create('product_parser_image_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parser_item_id')->constrained('product_parser_items')->cascadeOnDelete();
            $table->string('source_url', 1200);
            $table->string('source_domain')->nullable();
            $table->string('original_path')->nullable();
            $table->string('processed_path')->nullable();
            $table->string('thumb_path')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('status')->default('found')->index();
            $table->boolean('is_selected')->default(false);
            $table->boolean('is_main')->default(false);
            $table->boolean('has_watermark')->default(false);
            $table->boolean('background_removed')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_parser_image_assets');
        Schema::dropIfExists('product_parser_sources');
        Schema::dropIfExists('product_parser_items');
        Schema::dropIfExists('product_parser_batches');

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                foreach (['parser_source_urls', 'parser_confidence'] as $column) {
                    if (Schema::hasColumn('products', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
