<?php

namespace App\Models;

use App\Enum\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    use HasFactory;
    //fillable attributes
    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'user_id',
        'note',
    ];

    protected $casts = [
        'from_status' => OrderStatus::class,
        'to_status' => OrderStatus::class,
    ];

    // relations 

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class);
    }
}
