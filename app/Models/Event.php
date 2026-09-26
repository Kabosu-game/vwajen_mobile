<?php

namespace App\Models;

use App\Models\Concerns\Interactable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\PurgesCompletely;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use Interactable, PurgesCompletely;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_online' => 'boolean', 'is_hidden' => 'boolean',
            'lat' => 'float', 'lng' => 'float'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where(fn ($q) => $q->where('starts_at', '>=', now())->orWhere('ends_at', '>=', now()))->orderBy('starts_at');
    }

    public function scopePast(Builder $q): Builder
    {
        return $q->where('starts_at', '<', now())->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '<', now()))->orderByDesc('starts_at');
    }

    public function rsvpOf(?User $user): ?string
    {
        return $user ? $this->rsvps()->where('user_id', $user->id)->value('status') : null;
    }

    public function isPast(): bool
    {
        return ($this->ends_at ?? $this->starts_at)->isPast();
    }

    public function localStart()
    {
        return $this->starts_at->copy()->setTimezone($this->timezone);
    }

    public function localEnd()
    {
        return $this->ends_at?->copy()->setTimezone($this->timezone);
    }

    public function coverUrl(): ?string
    {
        return $this->cover ? Storage::disk('public')->url($this->cover) : null;
    }

    public function mapUrl(): ?string
    {
        return $this->lat && $this->lng ? "https://www.openstreetmap.org/?mlat={$this->lat}&mlon={$this->lng}#map=16/{$this->lat}/{$this->lng}" : null;
    }

    public function url(): string
    {
        return route('events.show', $this->id);
    }

    public function canManage(?User $user): bool
    {
        return $user && ($user->id === $this->user_id || $user->hasPermission('content.delete'));
    }
}
