<?php

namespace App\Models;

use App\Support\ProductLocalizer;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'brand_id', 'category_id', 'name', 'name_ro', 'slug', 'sku', 'short_description', 'description',
        'description_ro', 'price', 'old_price', 'currency', 'stock_quantity', 'stock_status', 'status',
        'parser_confidence', 'parser_source_urls', 'main_image', 'gallery', 'attributes', 'package_contents',
        'rating', 'reviews_count', 'is_active', 'is_featured', 'is_bestseller', 'is_new', 'is_discounted',
        'warranty', 'weight', 'dimensions', 'approval_status', 'needs_review', 'needs_stock_review',
        'needs_image_review', 'source_import_batch_id', 'source_parser_item_id', 'vehicle_application',
        'meta_title', 'meta_description',
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
            return ProductLocalizer::russianName($this->name ?: $this->name_ro, $this->brand?->name ?? '', $this->sku);
        }

        return $this->name_ro ?: ProductLocalizer::name($this->name, $this->brand?->name ?? '', $this->sku);
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        if (app()->isLocale('ru')) {
            return ProductLocalizer::russianDescription($this->description ?: $this->description_ro, $this->display_name, $this->brand?->name ?? '', $this->sku);
        }

        return $this->description_ro ?: $this->description;
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
