<?php

namespace App\Models;

use App\Support\ProductLocalizer;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'brand_id', 'category_id', 'name', 'name_ro', 'slug', 'sku', 'short_description', 'description',
        'description_ro', 'price', 'old_price', 'currency', 'stock_quantity', 'stock_status', 'main_image',
        'gallery', 'attributes', 'package_contents', 'rating', 'reviews_count', 'is_active', 'is_featured',
        'is_bestseller', 'is_new', 'is_discounted', 'warranty', 'weight', 'dimensions', 'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'gallery' => 'array',
        'attributes' => 'array',
        'package_contents' => 'array',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_new' => 'boolean',
        'is_discounted' => 'boolean',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function getBadgeAttribute(): ?string
    {
        return $this->is_discounted ? '-10%' : ($this->is_new ? 'NOU' : ($this->is_bestseller ? 'TOP' : null));
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name_ro ?: ProductLocalizer::name($this->name, $this->brand?->name ?? '', $this->sku);
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        return $this->description_ro ?: $this->description;
    }
}
