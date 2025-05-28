<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'price',
        'subtotal'
    ];

    // Define the relationship with the Order model
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    // Define the relationship with the Product model
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
