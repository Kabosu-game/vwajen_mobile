<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveViewer extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'kicked_at' => 'datetime',
            'is_banned' => 'boolean',
        ];
    }

    public function live(): BelongsTo
    {
        return $this->belongsTo(Live::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
