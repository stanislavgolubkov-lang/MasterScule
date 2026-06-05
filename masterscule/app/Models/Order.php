<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal', 'discount_total', 'shipping_total', 'total',
        'currency', 'payment_method', 'payment_status', 'shipping_method', 'customer_name',
        'customer_email', 'customer_phone', 'company_name', 'vat_number', 'shipping_country',
        'shipping_city', 'shipping_address', 'shipping_postcode', 'comment', 'admin_note',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
