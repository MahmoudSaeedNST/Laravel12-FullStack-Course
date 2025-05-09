<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    //fillable attribute
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'sku',
        'is_active',
        'image'
    ];

    // is stock available
    public function insStock()
    {
        return $this->stock > 0;
    }

    // Golbal scope for active products
    protected static function booted()
    {
        static::addGlobalScope('active', function ($query){
            $query->where('is_active', true);
        });
    }
    public function scopePriceBetween($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }
    // fortmatted name

    // formatted_name
    public function getFormattedNameAttribute()
    {
        return ucwords($this->name);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    // get image url attribute (image_url)
    public function getImageUrlAttribute()
    {
        // hexaora.com/storage/products/1.jpg
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}
