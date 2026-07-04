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
        'error_rows',
        'status',
        'options_json',
        'log_json',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'options_json' => 'array',
        'log_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_rows' => 'integer',
        'parsed_rows' => 'integer',
        'product_rows' => 'integer',
        'created_drafts' => 'integer',
        'updated_existing' => 'integer',
        'skipped_rows' => 'integer',
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
