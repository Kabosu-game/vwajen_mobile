<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'trusted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
