<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductParserItem extends Model
{
    protected $fillable = [
        'batch_id',
        'row_number',
        'sku',
        'normalized_sku',
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
        'vehicle_application',
        'tristools_url',
        'tristools_match_confidence',
        'detected_category_id',
        'detected_category_path',
        'category_confidence_score',
        'category_detection_method',
        'category_detection_notes_json',
        'needs_category_review',
        'needs_stock_review',
        'needs_image_review',
        'needs_translation_review',
        'needs_price_review',
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
        'official_source_url',
        'official_source_domain',
        'official_source_confidence',
        'fallback_source_url',
        'fallback_source_domain',
        'fallback_source_used',
        'source_match_confidence',
        'needs_source_review',
        'needs_content_review',
        'generated_content',
        'content_source_type',
        'image_source_type',
        'translation_source_type',
        'source_reviewed_at',
        'image_reviewed_at',
        'translation_reviewed_at',
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
        'needs_translation_review' => 'boolean',
        'needs_price_review' => 'boolean',
        'parsed_price' => 'decimal:2',
        'parsed_stock' => 'integer',
        'tristools_match_confidence' => 'integer',
        'official_source_confidence' => 'integer',
        'source_match_confidence' => 'integer',
        'fallback_source_used' => 'boolean',
        'needs_source_review' => 'boolean',
        'needs_content_review' => 'boolean',
        'generated_content' => 'boolean',
        'source_reviewed_at' => 'datetime',
        'image_reviewed_at' => 'datetime',
        'translation_reviewed_at' => 'datetime',
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
