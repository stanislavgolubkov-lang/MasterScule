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
        return $this->hasMany(Category::class, 'parent_id');
    }
}
