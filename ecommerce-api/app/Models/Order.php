<?php

namespace App\Models;

use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use App\Events\OrderStatusChanged;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    use HasFactory;
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

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function transitionTo(OrderStatus $newStatus, ?User $changedBy = null, ?string $notes = null)
    {
        // don't allow transition to the same status
        /* if ($this->status === $newStatus) {
            return true;
        }

        if (!$this->status->canTransitionTo($newStatus)) {
            return false;
        } */

        // store old status
        $oldStatus = $this->status; // current status
        // update the order status
        $this->update(['status' => $newStatus]);

        $this->statusHistory()->create([
            'order_id' => $this->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => $changedBy?->id ?? Auth::id(), // use the authenticated user or null
            'notes' => $notes,
        ]);

    
        OrderStatusChanged::dispatch(
            $this,
            $oldStatus->value,
            $changedBy?->name ?? Auth::user()?->name
        );

        return true;
    }

    // get allowed transitions for the current status
    public function getAllowedTransitions(): array
    {
        return $this->status->getAllowedTransitions();
    }

    public function getLatestStatusChange()
    {
        return $this->statusHistory()->first();
    }

    // generate a unique order number
    public static function generateOrderNumber()
    {
        $year = date('Y');


        $randomNumber = strtoupper(substr(uniqid(), -6));
        return "ORD-{$year}-{$randomNumber}"; // e.g., ORD-2025-ABC123
    }

    public function canBeCancelled()
    {
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

    /**
     * Check if the order can accept a payment
     * 
     * @return bool
     */
    public function canAcceptPayment(): bool
    {
        return $this->payment_status === PaymentStatus::PENDING ||
            $this->payment_status === PaymentStatus::FAILED;
    }
}
