<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'name_ro', 'slug', 'description', 'description_ro', 'icon', 'image',
        'sort_order', 'is_active', 'meta_title', 'meta_description',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name_ro');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function getDisplayNameAttribute(): string
    {
        $translated = __('catalog.categories.'.$this->slug);

        if ($translated !== 'catalog.categories.'.$this->slug) {
            return $translated;
        }

        return $this->name_ro ?: $this->name;
    }

    public function descendantsAndSelfIds(): array
    {
        $ids = [$this->id];

        $this->loadMissing('childrenRecursive');

        foreach ($this->childrenRecursive as $child) {
            $ids = array_merge($ids, $child->descendantsAndSelfIds());
        }

        return array_values(array_unique($ids));
    }
}
