<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal', 'discount_total', 'shipping_total', 'total',
        'currency', 'payment_method', 'payment_status', 'payment_reference', 'payment_url',
        'paid_at', 'stock_deducted_at', 'shipping_method', 'customer_name',
        'customer_email', 'customer_phone', 'company_name', 'vat_number', 'shipping_country',
        'shipping_city', 'shipping_address', 'shipping_postcode', 'comment', 'admin_note',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'stock_deducted_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
