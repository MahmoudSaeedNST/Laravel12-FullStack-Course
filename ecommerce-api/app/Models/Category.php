<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    // fillable 
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'parent_id'
    ];

    // id 1, tech , parent_id = null
    // id 2, laptop, parent_id = 1
    // id 3, phone, parent_id = 1
    // 
    // parent category
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // child categories
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }


    // is active childeren
    public function activeChildren()
    {
        return $this->children()->where('is_active', true);
    }


    // is top level category
    public function isTopLevel(): bool
    {
        return is_null($this->parent_id);
        // parent_id = null = top level category = parent
        // not top level category = child
        // parent_id = 1
    }
}
