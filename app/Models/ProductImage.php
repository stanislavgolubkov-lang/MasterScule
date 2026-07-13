<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'path',
        'source_url',
        'source_page_url',
        'source_domain',
        'is_official',
        'mime_type',
        'width',
        'height',
        'file_size',
        'alt',
        'sort_order',
    ];

    protected $casts = [
        'is_official' => 'boolean',
    ];
}
