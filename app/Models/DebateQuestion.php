<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebateQuestion extends Model
{
    protected $guarded = ['id'];

    public function debate(): BelongsTo
    {
        return $this->belongsTo(Debate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
