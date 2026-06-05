<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRequest extends Model
{
    protected $fillable = ['user_id', 'type', 'prompt', 'response', 'status', 'product_ids'];

    protected $casts = ['product_ids' => 'array'];
}
