<?php

namespace App\Models;

use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'shipping_name',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_zipcode',
        'shipping_country',
        'shipping_phone',
        'subtotal',
        'tax',
        'shipping_cost',
        'total',
        'payment_method',
        'payment_status',
        'transaction_id',
        'paid_at',
        'order_number',
        'notes',
        'transaction_id',
        'paid_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'paid_at' => 'datetime',
    ];


    // Define the relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // Define the relationship with the OrderItem model
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    // generate a unique order number
    public static function generateOrderNumber()
    {
        $year = date('Y');


        $randomNumber = strtoupper(substr(uniqid(), -6));
        return "ORD-{$year}-{$randomNumber}"; // e.g., ORD-2025-ABC123
    }

     public function canBeCancelled() {
        return in_array($this->status, [
            OrderStatus::PENDING,
            OrderStatus::PAID,
        ]);
    }

    // mark as paid
    public function markAsPaid($transactionId)
    {
       $this->update([
            'status' => OrderStatus::PAID,
            'payment_status' => PaymentStatus::COMPLETED,
            'transaction_id' => $transactionId,
            'paid_at' => now(),
        ]);
    }

    // mark as faild
    public function markAsFailed()
    {
        $this->update([
            'payment_status' => PaymentStatus::FAILED,
        ]);
    }
    
}
