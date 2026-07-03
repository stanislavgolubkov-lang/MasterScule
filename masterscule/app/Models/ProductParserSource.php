<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductParserSource extends Model
{
    protected $fillable = [
        'parser_item_id',
        'url',
        'domain',
        'title',
        'snippet',
        'source_type',
        'confidence_score',
        'raw_data_json',
    ];

    protected $casts = [
        'raw_data_json' => 'array',
    ];

    public function item()
    {
        return $this->belongsTo(ProductParserItem::class, 'parser_item_id');
    }
}
