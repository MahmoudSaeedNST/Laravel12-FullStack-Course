<?php

namespace App\Models;

use App\Enum\PaymentProvider;
use App\Enum\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
     protected $fillable = [
        'order_id',
        'user_id',
        'provider',
        'session_id',
        'payment_intent_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'completed_at'
    ];
    protected $casts = [
        'provider' => PaymentProvider::class,
        'status' => PaymentStatus::class,
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // mark as completed
    public function markAsCompleted($paymentIntentId, $metadata){
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'payment_intent_id' => $paymentIntentId,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
            'completed_at' => now(),
        ]);

        // Update the order status to paid
        $this->order->markAsPaid($paymentIntentId);
    }

    // mark as failed
    public function markAsFailed($metadata){
        $this->update([
            'status' => PaymentStatus::FAILED,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);

        $this->order->markAsPaymentFailed();
    }

}
