<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'name_ru')) {
                $table->string('name_ru')->nullable()->after('name');
            }
            if (! Schema::hasColumn('products', 'short_description_ru')) {
                $table->text('short_description_ru')->nullable()->after('short_description');
            }
            if (! Schema::hasColumn('products', 'short_description_ro')) {
                $table->text('short_description_ro')->nullable()->after('short_description_ru');
            }
            if (! Schema::hasColumn('products', 'description_ru')) {
                $table->longText('description_ru')->nullable()->after('description');
            }
            if (! Schema::hasColumn('products', 'needs_category_review')) {
                $table->boolean('needs_category_review')->default(false)->index();
            }
            if (! Schema::hasColumn('products', 'needs_translation_review')) {
                $table->boolean('needs_translation_review')->default(false)->index();
            }
            if (! Schema::hasColumn('products', 'needs_price_review')) {
                $table->boolean('needs_price_review')->default(false)->index();
            }
        });

        if (Schema::hasTable('product_parser_items')) {
            $indexes = collect(Schema::getIndexes('product_parser_items'))->pluck('name');

            Schema::table('product_parser_items', function (Blueprint $table) use ($indexes) {
                if (! $indexes->contains('parser_items_batch_status_idx')) {
                    $table->index(['batch_id', 'status'], 'parser_items_batch_status_idx');
                }
                if (! $indexes->contains('parser_items_batch_review_idx')) {
                    $table->index(['batch_id', 'needs_category_review'], 'parser_items_batch_review_idx');
                }
                if (! $indexes->contains('parser_items_batch_created_idx')) {
                    $table->index(['batch_id', 'created_product_id'], 'parser_items_batch_created_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_parser_items')) {
            $indexes = collect(Schema::getIndexes('product_parser_items'))->pluck('name');

            Schema::table('product_parser_items', function (Blueprint $table) use ($indexes) {
                foreach (['parser_items_batch_status_idx', 'parser_items_batch_review_idx', 'parser_items_batch_created_idx'] as $index) {
                    if ($indexes->contains($index)) {
                        $table->dropIndex($index);
                    }
                }
            });
        }

        $columns = [
            'name_ru',
            'short_description_ru',
            'short_description_ro',
            'description_ru',
            'needs_category_review',
            'needs_translation_review',
            'needs_price_review',
        ];

        $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('products', $column)));
        if ($existing !== []) {
            Schema::table('products', fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }
};
