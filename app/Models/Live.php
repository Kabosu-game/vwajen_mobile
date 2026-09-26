<?php

namespace App\Models;

use App\Models\Concerns\Interactable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/** Vwajèn Live (vidéo) ou Audio Space (audio). */
class Live extends Model
{
    use Interactable, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['stream_key'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime', 'started_at' => 'datetime', 'ended_at' => 'datetime',
            'chat_enabled' => 'boolean', 'chat_followers_only' => 'boolean', 'is_hidden' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(LiveParticipant::class);
    }

    public function viewers(): HasMany
    {
        return $this->hasMany(LiveViewer::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(LiveReaction::class);
    }

    public function chatMessages(): MorphMany
    {
        return $this->morphMany(ChatMessage::class, 'chatable');
    }

    public function replay(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'replay_video_id');
    }

    public function debate(): HasOne
    {
        return $this->hasOne(Debate::class);
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function isAudio(): bool
    {
        return $this->kind === 'audio';
    }

    /** Hôte principal + invités ayant accepté (+ candidats/modérateur du débat associé). */
    public function hostIds(): array
    {
        return collect([$this->user_id])
            ->merge($this->participants()->where('status', 'accepted')->whereIn('role', ['host', 'guest'])->pluck('user_id'))
            ->unique()->values()->all();
    }

    public function isHost(?User $user): bool
    {
        return $user && in_array($user->id, $this->hostIds(), true);
    }

    public function canModerate(?User $user): bool
    {
        return $user && ($user->id === $this->user_id || $user->hasPermission('lives.moderate')
            || $this->participants()->where('user_id', $user->id)->where('status', 'accepted')->where('role', 'moderator')->exists()
            || ($this->debate && $this->debate->moderator_id === $user->id));
    }

    public function currentViewersCount(): int
    {
        return $this->viewers()->where('last_seen_at', '>=', now()->subSeconds(30))->whereNull('kicked_at')->where('is_banned', false)->count();
    }

    public function coverUrl(): ?string
    {
        return $this->cover ? Storage::disk('public')->url($this->cover) : null;
    }

    public function url(): string
    {
        return route('lives.show', $this->id);
    }
}
