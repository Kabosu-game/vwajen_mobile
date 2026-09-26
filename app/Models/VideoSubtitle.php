<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VideoSubtitle extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_auto' => 'boolean'];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
