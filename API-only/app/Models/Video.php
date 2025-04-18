<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    //
    // fillable
    protected $fillable = [
        'title',
        'description',
        'url',
        'thumbnail',
        'duration',
    ];

    // relationships
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
