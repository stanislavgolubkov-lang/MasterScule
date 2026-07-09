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
                $this->unsignedIntegerColumn($table, 'service_rows');
                $this->unsignedIntegerColumn($table, 'new_sku_count');
                $this->unsignedIntegerColumn($table, 'existing_sku_count');
                $this->unsignedIntegerColumn($table, 'duplicate_sku_count');
                $this->unsignedIntegerColumn($table, 'rows_without_price');
                $this->unsignedIntegerColumn($table, 'rows_without_stock');
                $this->unsignedIntegerColumn($table, 'rows_without_category');
                $this->unsignedIntegerColumn($table, 'planned_drafts');
                $this->jsonColumn($table, 'dry_run_report_json');
            });
        }

        if (Schema::hasTable('product_parser_items')) {
            Schema::table('product_parser_items', function (Blueprint $table) {
                $this->stringColumn($table, 'normalized_sku', 120);
                $this->stringColumn($table, 'vehicle_application', 255);
                $this->stringColumn($table, 'tristools_url', 1200);
                $this->unsignedTinyIntegerColumn($table, 'tristools_match_confidence');
                $this->booleanColumn($table, 'needs_translation_review');
                $this->booleanColumn($table, 'needs_price_review');
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $this->stringColumn($table, 'vehicle_application', 255);
            });
        }
    }

    public function down(): void
    {
        $this->dropColumns('products', ['vehicle_application']);
        $this->dropColumns('product_parser_items', [
            'needs_price_review',
            'needs_translation_review',
            'tristools_match_confidence',
            'tristools_url',
            'vehicle_application',
            'normalized_sku',
        ]);
        $this->dropColumns('product_parser_batches', [
            'dry_run_report_json',
            'planned_drafts',
            'rows_without_category',
            'rows_without_stock',
            'rows_without_price',
            'duplicate_sku_count',
            'existing_sku_count',
            'new_sku_count',
            'service_rows',
        ]);
    }

    private function stringColumn(Blueprint $table, string $column, int $length = 255): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->string($column, $length)->nullable();
        }
    }

    private function unsignedIntegerColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->unsignedInteger($column)->default(0);
        }
    }

    private function unsignedTinyIntegerColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->unsignedTinyInteger($column)->nullable();
        }
    }

    private function booleanColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->boolean($column)->default(false);
        }
    }

    private function jsonColumn(Blueprint $table, string $column): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->json($column)->nullable();
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
