<?php

namespace App\Models;

use App\Models\Concerns\Interactable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Debate extends Model
{
    use Interactable, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime', 'reminder_sent_at' => 'datetime', 'archived_at' => 'datetime',
            'public_questions' => 'boolean', 'chat_enabled' => 'boolean', 'is_hidden' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function live(): BelongsTo
    {
        return $this->belongsTo(Live::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function replay(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'replay_video_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(DebateParticipant::class)->orderBy('speaking_order');
    }

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'debate_participants')->withPivot('status', 'speaking_order')->withTimestamps();
    }

    public function publicQuestions(): HasMany
    {
        return $this->hasMany(DebateQuestion::class);
    }

    public function canManage(?User $user): bool
    {
        return $user && ($user->id === $this->user_id || $user->id === $this->moderator_id || $user->hasPermission('debates.manage'));
    }

    public function isParticipant(?User $user): bool
    {
        return $user && $this->participants->where('user_id', $user->id)->where('status', 'accepted')->isNotEmpty();
    }

    public function coverUrl(): ?string
    {
        return $this->cover ? Storage::disk('public')->url($this->cover) : null;
    }

    public function endsAt()
    {
        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
    }

    public function url(): string
    {
        return route('debates.show', $this->id);
    }
}
