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
            ->where('main_image', 'not like', '%placeholder%');
    }

    public function scopePurchasable($query)
    {
        return $query
            ->availableForSale()
            ->where('stock_status', 'in_stock')
            ->where('stock_quantity', '>', 0);
    }

    public function getIsPurchasableAttribute(): bool
    {
        return $this->stock_status === 'in_stock' && (int) $this->stock_quantity > 0;
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

    public function getDisplayDescriptionAttribute(): string
    {
        $candidates = app()->isLocale('ru')
            ? [$this->description_ru, $this->description, $this->short_description_ru, $this->short_description, $this->description_ro, $this->short_description_ro]
            : [$this->description_ro, $this->short_description_ro, $this->description, $this->description_ru, $this->short_description, $this->short_description_ru];

        foreach ($candidates as $candidate) {
            $description = trim((string) $candidate);

            if ($description !== '') {
                return $description;
            }
        }

        return app()->isLocale('ru')
            ? "{$this->display_name} — профессиональный товар для автосервиса, мастерской или гаража. Артикул: {$this->sku}."
            : "{$this->display_name} este un produs profesional pentru service auto, atelier sau garaj. Cod produs: {$this->sku}.";
    }

    public function getDisplayAttributesAttribute(): array
    {
        $keys = [
            'Numar piese' => 'Количество предметов',
            'Număr piese' => 'Количество предметов',
            'Material' => 'Материал',
            'Utilizare' => 'Применение',
            'Greutate' => 'Вес',
            'Dimensiuni' => 'Размеры',
        ];

        $values = [
            'Otel crom-vanadiu' => 'Хром-ванадиевая сталь',
            'Oțel crom-vanadiu' => 'Хром-ванадиевая сталь',
            'Profesional' => 'Профессиональное',
            'Service' => 'Сервис',
            '12 luni' => '12 месяцев',
        ];

        $attributes = collect($this->getAttributeValue('attributes') ?? [])
            ->filter(fn ($value, $key) => trim((string) $key) !== ''
                && trim((string) $value) !== ''
                && ! $this->isHiddenDisplayAttribute((string) $key));

        if (app()->isLocale('ru')) {
            $attributes = $attributes
                ->mapWithKeys(fn ($value, $key) => [
                    $keys[$key] ?? $key => $values[$value] ?? $value,
                ]);
        }

        $attributes = $attributes->all();

        foreach ([
            app()->isLocale('ru') ? 'Вес' : 'Greutate' => $this->weight,
            app()->isLocale('ru') ? 'Габариты' : 'Dimensiuni' => $this->dimensions,
        ] as $key => $value) {
            if (filled($value) && ! array_key_exists($key, $attributes)) {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    private function isHiddenDisplayAttribute(string $key): bool
    {
        $key = mb_strtolower(trim((string) preg_replace('/[\s:_-]+/u', ' ', $key)));

        return in_array($key, [
            'retail price',
            'price retail',
            'розничная цена',
            'цена розничная',
            'pret retail',
            'preț retail',
            'price source',
            'источник цены',
            'sursa pretului',
            'sursa prețului',
            'warranty',
            'гарантия',
            'garantie',
            'garanție',
        ], true);
    }

    public function getDisplayPackageContentsAttribute(): array
    {
        return collect($this->getAttributeValue('package_contents') ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
    }

    public function getDisplayWarrantyAttribute(): string
    {
        $warranty = trim((string) ($this->warranty ?: '12 luni'));

        if (app()->isLocale('ru')) {
            return str_replace(['luni', 'luna'], ['мес.', 'мес.'], $warranty);
        }

        return $warranty;
    }
}
