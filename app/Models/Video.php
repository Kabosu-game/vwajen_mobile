<?php

namespace App\Models;

use App\Models\Concerns\Interactable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Concerns\PurgesCompletely;
use Illuminate\Support\Facades\Storage;

/** Vidéo longue, Short (vidéo verticale courte) ou replay de live / débat. */
class Video extends Model
{
    use Interactable, PurgesCompletely;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['qualities' => 'array', 'is_hidden' => 'boolean', 'allow_comments' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function subtitles(): HasMany
    {
        return $this->hasMany(VideoSubtitle::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeShorts(Builder $q): Builder
    {
        return $q->where('kind', 'short');
    }

    public function scopeLongs(Builder $q): Builder
    {
        return $q->whereIn('kind', ['long', 'replay']);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('visibility', 'public')->where('processing_status', 'ready');
    }

    public function isShort(): bool
    {
        return $this->kind === 'short';
    }

    public function videoUrl(?string $quality = null): string
    {
        $path = $quality && isset($this->qualities[$quality]) ? $this->qualities[$quality] : $this->path;

        return Storage::disk('public')->url($path);
    }

    /** Liste des sources pour le lecteur : qualité => URL (la plus basse d'abord). */
    public function sources(): array
    {
        $sources = [];
        foreach (collect($this->qualities ?? [])->sortKeys() as $q => $path) {
            $sources[$q.'p'] = Storage::disk('public')->url($path);
        }
        $sources['original'] = Storage::disk('public')->url($this->path);

        return $sources;
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail ? Storage::disk('public')->url($this->thumbnail) : null;
    }

    public function durationLabel(): string
    {
        if (! $this->duration) {
            return '';
        }

        return $this->duration >= 3600 ? gmdate('G:i:s', $this->duration) : gmdate('i:s', $this->duration);
    }

    public function url(): string
    {
        return $this->isShort() ? route('shorts.show', $this->id) : route('videos.show', $this->id);
    }

    public function canBeManagedBy(?User $user): bool
    {
        return $user && ($user->id === $this->user_id || $user->hasPermission('content.delete'));
    }
}
