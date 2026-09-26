<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Podcast extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_hidden' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(PodcastEpisode::class)->orderByDesc('published_at');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function coverUrl(): string
    {
        return $this->cover ? Storage::disk('public')->url($this->cover)
            : 'https://ui-avatars.com/api/?background=f59e0b&color=fff&size=256&name='.urlencode($this->title);
    }

    public function url(): string
    {
        return route('podcasts.show', $this->id);
    }
}
