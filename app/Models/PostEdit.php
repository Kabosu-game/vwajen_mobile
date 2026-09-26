<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostEdit extends Model
{
    protected $guarded = ['id'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
