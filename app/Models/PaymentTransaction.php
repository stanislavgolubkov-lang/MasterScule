<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'provider_transaction_id',
        'status',
        'amount',
        'currency',
        'request_payload_json',
        'response_payload_json',
        'callback_payload_json',
        'callback_signature',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload_json' => 'array',
        'response_payload_json' => 'array',
        'callback_payload_json' => 'array',
        'processed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
