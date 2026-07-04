<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductParserItem extends Model
{
    protected $fillable = [
        'batch_id',
        'sku',
        'brand',
        'category_id',
        'status',
        'confidence_score',
        'found_title',
        'found_description',
        'found_specs_json',
        'found_images_json',
        'selected_images_json',
        'processed_images_json',
        'source_urls_json',
        'error_message',
        'created_product_id',
        'existing_product_id',
    ];

    protected $casts = [
        'found_specs_json' => 'array',
        'found_images_json' => 'array',
        'selected_images_json' => 'array',
        'processed_images_json' => 'array',
        'source_urls_json' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(ProductParserBatch::class, 'batch_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function sources()
    {
        return $this->hasMany(ProductParserSource::class, 'parser_item_id');
    }

    public function imageAssets()
    {
        return $this->hasMany(ProductParserImageAsset::class, 'parser_item_id');
    }

    public function existingProduct()
    {
        return $this->belongsTo(Product::class, 'existing_product_id');
    }

    public function createdProduct()
    {
        return $this->belongsTo(Product::class, 'created_product_id');
    }
}
