<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PodcastEpisode extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function podcast(): BelongsTo
    {
        return $this->belongsTo(Podcast::class);
    }

    public function audioUrl(): string
    {
        return Storage::disk('public')->url($this->audio_path);
    }

    public function url(): string
    {
        return route('podcasts.show', $this->podcast_id).'#episode-'.$this->id;
    }
}
