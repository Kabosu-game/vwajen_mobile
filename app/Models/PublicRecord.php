<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PublicRecord extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'is_hidden' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sources(): MorphMany
    {
        return $this->morphMany(Source::class, 'sourceable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
