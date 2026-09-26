<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_on' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function sizeLabel(): string
    {
        return $this->size ? number_format($this->size / 1048576, 1).' Mo' : '';
    }
}
