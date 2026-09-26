<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OfficialProfile extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'mandate_start' => 'date',
            'mandate_end' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sources(): MorphMany
    {
        return $this->morphMany(Source::class, 'sourceable');
    }

    public function url(): string
    {
        return route('officials.show', $this->user->username);
    }
}
