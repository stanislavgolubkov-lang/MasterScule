<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductParserBatch extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'source_type',
        'supplier_name',
        'file_name',
        'file_path',
        'file_type',
        'brand_default',
        'category_default_id',
        'price_type',
        'import_mode',
        'sku_count',
        'total_rows',
        'parsed_rows',
        'product_rows',
        'created_drafts',
        'updated_existing',
        'skipped_rows',
        'service_rows',
        'new_sku_count',
        'existing_sku_count',
        'duplicate_sku_count',
        'rows_without_price',
        'rows_without_stock',
        'rows_without_category',
        'planned_drafts',
        'error_rows',
        'status',
        'options_json',
        'log_json',
        'dry_run_report_json',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'options_json' => 'array',
        'log_json' => 'array',
        'dry_run_report_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_rows' => 'integer',
        'parsed_rows' => 'integer',
        'product_rows' => 'integer',
        'created_drafts' => 'integer',
        'updated_existing' => 'integer',
        'skipped_rows' => 'integer',
        'service_rows' => 'integer',
        'new_sku_count' => 'integer',
        'existing_sku_count' => 'integer',
        'duplicate_sku_count' => 'integer',
        'rows_without_price' => 'integer',
        'rows_without_stock' => 'integer',
        'rows_without_category' => 'integer',
        'planned_drafts' => 'integer',
        'error_rows' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ProductParserItem::class, 'batch_id');
    }

    public function addLog(string $message, array $context = []): void
    {
        $log = $this->log_json ?: [];
        $log[] = [
            'at' => now()->toDateTimeString(),
            'message' => $message,
            'context' => $context,
        ];

        $this->forceFill(['log_json' => array_slice($log, -200)])->save();
    }
}
