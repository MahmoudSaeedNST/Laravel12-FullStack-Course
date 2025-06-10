<?php

namespace App\Models;

use App\Enum\PaymentProvider;
use App\Enum\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    //attributes
    protected $fillable = [
        'order_id',
        'user_id',
        'provider',
        'payment_intent_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'completed_at'
    ];

    //casts
    protected $casts = [
        'metadata' => 'array',
        'completed_at' => 'datetime',
        'amount' => 'decimal:2',
        'provider' => PaymentProvider::class,
        'status' => PaymentStatus::class,
    ];

    // defining relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // marke as completed
    public function markAsCompleted($paymentIntentId, $metadata = [])
    {
        // arry1 = ['ky1' => 'value1', 'key2' => 'value2']
        // array2 = ['key2' => 'value overrited', 'key3' => 'value3', 'key4' => 'value4']
        // array_merge(array1, array2) = ['key1' => 'value1', 'key2' => 'value overrited', 'key3' => 'value3', 'key4' => 'value4']
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'payment_intent_id' => $paymentIntentId,
            'completed_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], $metadata)

        ]);

        $this->order->markAsPaid($paymentIntentId);
    }

    // mark as failed
    public function markAsFailed($metadata = [])
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'metadata' => array_merge($this->metadata ?? [], $metadata)
        ]);

        $this->order->markAsFailed();
    }

    // is fianl
    public function isFinal()
    {
        return in_array($this->status, [
            PaymentStatus::COMPLETED,
            PaymentStatus::FAILED,
            PaymentStatus::REFUNDED,
        ]);
    }
}
