<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'brand_id', 'category_id', 'name', 'name_ru', 'name_ro', 'slug', 'sku', 'short_description',
        'short_description_ru', 'short_description_ro', 'description', 'description_ru', 'description_ro',
        'price', 'old_price', 'currency', 'stock_quantity', 'stock_status', 'status',
        'parser_confidence', 'parser_source_urls', 'main_image', 'gallery', 'attributes', 'package_contents',
        'rating', 'reviews_count', 'is_active', 'is_featured', 'is_bestseller', 'is_new', 'is_discounted',
        'warranty', 'weight', 'dimensions', 'approval_status', 'needs_review', 'needs_stock_review',
        'needs_image_review', 'needs_category_review', 'needs_translation_review', 'needs_price_review',
        'source_import_batch_id', 'source_parser_item_id', 'vehicle_application',
        'meta_title', 'meta_description',
        'source_url', 'source_domain', 'source_type', 'fallback_source_used',
        'needs_source_review', 'needs_content_review', 'generated_content', 'source_reviewed_at',
    ];

    protected $casts = [
        'gallery' => 'array',
        'parser_source_urls' => 'array',
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
        'needs_review' => 'boolean',
        'needs_stock_review' => 'boolean',
        'needs_image_review' => 'boolean',
        'needs_category_review' => 'boolean',
        'needs_translation_review' => 'boolean',
        'needs_price_review' => 'boolean',
        'source_import_batch_id' => 'integer',
        'source_parser_item_id' => 'integer',
        'fallback_source_used' => 'boolean',
        'needs_source_review' => 'boolean',
        'needs_content_review' => 'boolean',
        'generated_content' => 'boolean',
        'source_reviewed_at' => 'datetime',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)
            ->withPivot(['is_primary', 'source', 'confidence'])
            ->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function scopeAvailableForSale($query)
    {
        return $query
            ->where('is_active', true)
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->where('needs_review', false)
            ->where('needs_image_review', false)
            ->where('needs_category_review', false)
            ->where('needs_translation_review', false)
            ->where('needs_price_review', false)
            ->whereNotNull('main_image')
            ->where('main_image', '!=', '')
            ->where('main_image', 'not like', '%placeholder%')
            ->where('main_image', 'not like', '%fallback%')
            ->where('stock_status', 'in_stock')
            ->where('stock_quantity', '>', 0);
    }

    public function scopeInCatalogCategories($query, array $categoryIds)
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

        if ($categoryIds === []) {
            return $query;
        }

        return $query->where(function ($inner) use ($categoryIds) {
            $inner
                ->whereIn('category_id', $categoryIds)
                ->orWhereHas('categories', fn ($categories) => $categories->whereIn('categories.id', $categoryIds));
        });
    }

    public function syncCategoryLinks(array $categoryIds, ?int $primaryCategoryId = null, string $source = 'admin', array $confidenceById = []): void
    {
        $categoryIds = collect($categoryIds)
            ->push($primaryCategoryId)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $sync = $categoryIds->mapWithKeys(function (int $categoryId) use ($primaryCategoryId, $source, $confidenceById) {
            return [
                $categoryId => [
                    'is_primary' => $primaryCategoryId ? $categoryId === (int) $primaryCategoryId : false,
                    'source' => $source,
                    'confidence' => max(0, min(100, (int) ($confidenceById[$categoryId] ?? 100))),
                ],
            ];
        })->all();

        $this->categories()->sync($sync);
    }

    public function getBadgeAttribute(): ?string
    {
        return $this->is_discounted
            ? discountPercent($this->old_price, $this->price)
            : ($this->is_new ? mb_strtoupper(__('ui.new')) : ($this->is_bestseller ? mb_strtoupper(__('ui.top')) : null));
    }

    public function getDisplayNameAttribute(): string
    {
        if (app()->isLocale('ru')) {
            return $this->name_ru ?: $this->name ?: $this->name_ro ?: $this->sku;
        }

        return $this->name_ro ?: $this->name_ru ?: $this->name ?: $this->sku;
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        if (app()->isLocale('ru')) {
            return $this->description_ru ?: $this->description ?: $this->short_description_ru ?: $this->short_description;
        }

        return $this->description_ro ?: $this->short_description_ro;
    }

    public function getDisplayAttributesAttribute(): array
    {
        if (! app()->isLocale('ru')) {
            return $this->getAttributeValue('attributes') ?? [];
        }

        $keys = [
            'Numar piese' => 'Количество предметов',
            'Număr piese' => 'Количество предметов',
            'Material' => 'Материал',
            'Utilizare' => 'Применение',
            'Greutate' => 'Вес',
            'Dimensiuni' => 'Размеры',
            'Garantie' => 'Гарантия',
        ];

        $values = [
            'Otel crom-vanadiu' => 'Хром-ванадиевая сталь',
            'Oțel crom-vanadiu' => 'Хром-ванадиевая сталь',
            'Profesional' => 'Профессиональное',
            'Service' => 'Сервис',
            '24 luni' => '24 месяца',
        ];

        return collect($this->getAttributeValue('attributes') ?? [])
            ->mapWithKeys(fn ($value, $key) => [
                $keys[$key] ?? $key => $values[$value] ?? $value,
            ])
            ->all();
    }
}
