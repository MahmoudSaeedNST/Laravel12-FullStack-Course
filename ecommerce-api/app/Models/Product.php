<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //fillable attribute
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'sku',
        'is_active'
    ];

    // is stock available
    public function insStock()
    {
        return $this->stock > 0;
    }
}
