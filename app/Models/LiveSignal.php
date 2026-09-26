<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveSignal extends Model
{
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function live(): BelongsTo
    {
        return $this->belongsTo(Live::class);
    }
}
