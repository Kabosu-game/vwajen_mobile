<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitmentUpdate extends Model
{
    protected $guarded = ['id'];

    public function commitment(): BelongsTo
    {
        return $this->belongsTo(Commitment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
