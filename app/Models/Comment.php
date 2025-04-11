<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    //
    // fillable
    protected $fillable = [
        'post_id',
        'content',
    ];
    // relationships
    /* public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    } */

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
