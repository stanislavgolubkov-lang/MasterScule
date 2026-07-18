<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategoryDecision extends Model
{
    protected $fillable = [
        'product_id',
        'previous_category_id',
        'selected_category_id',
        'taxonomy_version',
        'input_hash',
        'mode',
        'status',
        'model',
        'verifier_model',
        'classifier_confidence',
        'verifier_confidence',
        'evidence',
        'alternatives',
        'validation_errors',
        'applied_at',
    ];

    protected $casts = [
        'classifier_confidence' => 'float',
        'verifier_confidence' => 'float',
        'evidence' => 'array',
        'alternatives' => 'array',
        'validation_errors' => 'array',
        'applied_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function previousCategory()
    {
        return $this->belongsTo(Category::class, 'previous_category_id');
    }

    public function selectedCategory()
    {
        return $this->belongsTo(Category::class, 'selected_category_id');
    }
}
