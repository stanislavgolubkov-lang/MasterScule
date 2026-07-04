<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductParserItem extends Model
{
    protected $fillable = [
        'batch_id',
        'row_number',
        'sku',
        'brand',
        'category_id',
        'status',
        'confidence_score',
        'raw_name',
        'parsed_name',
        'raw_price',
        'parsed_price',
        'raw_stock',
        'parsed_stock',
        'detected_group',
        'detected_subgroup',
        'detected_category_id',
        'detected_category_path',
        'category_confidence_score',
        'category_detection_method',
        'category_detection_notes_json',
        'needs_category_review',
        'needs_stock_review',
        'needs_image_review',
        'approval_status',
        'name_ru',
        'name_ro',
        'short_description_ru',
        'short_description_ro',
        'description_ru',
        'description_ro',
        'source_file_name',
        'import_row_json',
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
        'category_detection_notes_json' => 'array',
        'import_row_json' => 'array',
        'needs_category_review' => 'boolean',
        'needs_stock_review' => 'boolean',
        'needs_image_review' => 'boolean',
        'parsed_price' => 'decimal:2',
        'parsed_stock' => 'integer',
    ];

    public function batch()
    {
        return $this->belongsTo(ProductParserBatch::class, 'batch_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function detectedCategory()
    {
        return $this->belongsTo(Category::class, 'detected_category_id');
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
