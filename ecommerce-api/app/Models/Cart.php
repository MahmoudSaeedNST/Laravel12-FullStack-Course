<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    //fillable properties
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
    ];

    // relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // relationship with Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
