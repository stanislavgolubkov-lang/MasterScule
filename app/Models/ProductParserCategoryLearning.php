<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductParserCategoryLearning extends Model
{
    protected $fillable = [
        'key_type',
        'key_hash',
        'key_value',
        'brand_key',
        'category_id',
        'source',
        'confidence',
        'observations',
        'context_json',
        'last_seen_at',
    ];

    protected $casts = [
        'confidence' => 'integer',
        'observations' => 'integer',
        'context_json' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
