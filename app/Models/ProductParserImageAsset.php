<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductParserImageAsset extends Model
{
    protected $fillable = [
        'parser_item_id',
        'source_url',
        'source_domain',
        'original_path',
        'processed_path',
        'preview_path',
        'thumb_path',
        'width',
        'height',
        'mime_type',
        'status',
        'is_selected',
        'is_main',
        'has_watermark',
        'background_removed',
        'needs_review',
        'background_removal_failed',
        'error_message',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
        'is_main' => 'boolean',
        'has_watermark' => 'boolean',
        'background_removed' => 'boolean',
        'needs_review' => 'boolean',
        'background_removal_failed' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(ProductParserItem::class, 'parser_item_id');
    }
}
