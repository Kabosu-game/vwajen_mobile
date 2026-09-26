<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceVerification extends Model
{
    protected $guarded = ['id'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
