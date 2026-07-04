<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_parser_batches')) {
            Schema::table('product_parser_batches', function (Blueprint $table) {
                $this->stringColumn($table, 'supplier_name');
                $this->stringColumn($table, 'file_name');
                $this->stringColumn($table, 'file_path');
                $this->stringColumn($table, 'file_type', 40);
                $this->stringColumn($table, 'brand_default');
                $this->unsignedBigIntegerColumn($table, 'category_default_id');
                $this->stringColumn($table, 'price_type', 40, 'retail_price');
                $this->stringColumn($table, 'import_mode', 80, 'create_drafts');
                $this->unsignedIntegerColumn($table, 'total_rows');
                $this->unsignedIntegerColumn($table, 'parsed_rows');
                $this->unsignedIntegerColumn($table, 'product_rows');
                $this->unsignedIntegerColumn($table, 'created_drafts');
                $this->unsignedIntegerColumn($table, 'updated_existing');
                $this->unsignedIntegerColumn($table, 'skipped_rows');
                $this->unsignedIntegerColumn($table, 'error_rows');
            });
        }

        if (Schema::hasTable('product_parser_items')) {
            Schema::table('product_parser_items', function (Blueprint $table) {
                $this->unsignedIntegerColumn($table, 'row_number', null);
                $this->textColumn($table, 'raw_name');
                $this->textColumn($table, 'parsed_name');
                $this->stringColumn($table, 'raw_price', 120);
                $this->decimalColumn($table, 'parsed_price');
                $this->stringColumn($table, 'raw_stock', 120);
                $this->integerColumn($table, 'parsed_stock');
                $this->stringColumn($table, 'detected_group');
                $this->stringColumn($table, 'detected_subgroup');
                $this->unsignedBigIntegerColumn($table, 'detected_category_id');
                $this->stringColumn($table, 'detected_category_path', 800);
                $this->unsignedTinyIntegerColumn($table, 'category_confidence_score');
                $this->stringColumn($table, 'category_detection_method');
                $this->jsonColumn($table, 'category_detection_notes_json');
                $this->booleanColumn($table, 'needs_category_review');
                $this->booleanColumn($table, 'needs_stock_review');
                $this->booleanColumn($table, 'needs_image_review');
                $this->stringColumn($table, 'approval_status', 80, 'pending_review');
                $this->stringColumn($table, 'name_ru', 500);
                $this->stringColumn($table, 'name_ro', 500);
                $this->textColumn($table, 'short_description_ru');
                $this->textColumn($table, 'short_description_ro');
                $this->longTextColumn($table, 'description_ru');
                $this->longTextColumn($table, 'description_ro');
                $this->stringColumn($table, 'source_file_name');
                $this->jsonColumn($table, 'import_row_json');
            });
        }

        if (Schema::hasTable('product_parser_image_assets')) {
            Schema::table('product_parser_image_assets', function (Blueprint $table) {
                $this->booleanColumn($table, 'needs_review');
                $this->booleanColumn($table, 'background_removal_failed');
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $this->stringColumn($table, 'approval_status', 80, 'approved');
                $this->booleanColumn($table, 'needs_review');
                $this->booleanColumn($table, 'needs_stock_review');
                $this->booleanColumn($table, 'needs_image_review');
                $this->unsignedBigIntegerColumn($table, 'source_import_batch_id');
                $this->unsignedBigIntegerColumn($table, 'source_parser_item_id');
            });
        }
    }

    public function down(): void
    {
        $this->dropColumns('products', [
            'source_parser_item_id', 'source_import_batch_id', 'needs_image_review',
            'needs_stock_review', 'needs_review', 'approval_status',
        ]);
        $this->dropColumns('product_parser_image_assets', ['background_removal_failed', 'needs_review']);
        $this->dropColumns('product_parser_items', [
            'import_row_json', 'source_file_name', 'description_ro', 'description_ru',
            'short_description_ro', 'short_description_ru', 'name_ro', 'name_ru',
            'approval_status', 'needs_image_review', 'needs_stock_review',
            'needs_category_review', 'category_detection_notes_json',
            'category_detection_method', 'category_confidence_score',
            'detected_category_path', 'detected_category_id', 'detected_subgroup',
            'detected_group', 'parsed_stock', 'raw_stock', 'parsed_price',
            'raw_price', 'parsed_name', 'raw_name', 'row_number',
        ]);
        $this->dropColumns('product_parser_batches', [
            'error_rows', 'skipped_rows', 'updated_existing', 'created_drafts',
            'product_rows', 'parsed_rows', 'total_rows', 'import_mode',
            'price_type', 'category_default_id', 'brand_default', 'file_type',
            'file_path', 'file_name', 'supplier_name',
        ]);
    }

    private function stringColumn(Blueprint $table, string $column, int $length = 255, ?string $default = null): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $definition = $table->string($column, $length)->nullable();
            if ($default !== null) {
                $definition->default($default);
            }
        }
    }

    private function textColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->text($column)->nullable();
        }
    }

    private function longTextColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->longText($column)->nullable();
        }
    }

    private function jsonColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->json($column)->nullable();
        }
    }

    private function booleanColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->boolean($column)->default(false);
        }
    }

    private function unsignedIntegerColumn(Blueprint $table, string $column, mixed $default = 0): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $definition = $table->unsignedInteger($column)->nullable();
            if ($default !== null) {
                $definition->default($default);
            }
        }
    }

    private function integerColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->integer($column)->nullable();
        }
    }

    private function unsignedTinyIntegerColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->unsignedTinyInteger($column)->nullable();
        }
    }

    private function unsignedBigIntegerColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->unsignedBigInteger($column)->nullable()->index();
        }
    }

    private function decimalColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->decimal($column, 12, 2)->nullable();
        }
    }

    private function dropColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $existing = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($table, $column)));

        if ($existing) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($existing));
        }
    }
};
