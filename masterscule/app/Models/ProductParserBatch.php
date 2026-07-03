<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductParserBatch extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'source_type',
        'sku_count',
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
