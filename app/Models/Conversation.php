<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Conversation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('is_admin', 'last_read_at', 'cleared_at', 'left_at')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function participantFor(User $user): ?ConversationParticipant
    {
        return $this->participants->firstWhere('user_id', $user->id)
            ?? $this->participants()->where('user_id', $user->id)->first();
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function otherUser(User $me): ?User
    {
        return $this->users->firstWhere('id', '!=', $me->id);
    }

    public function titleFor(User $me): string
    {
        if ($this->isGroup()) {
            return $this->name ?: $this->users->where('id', '!=', $me->id)->pluck('name')->take(3)->implode(', ');
        }

        return $this->otherUser($me)?->name ?? __('Conversation');
    }

    public function avatarFor(User $me): string
    {
        if ($this->isGroup()) {
            return $this->avatar ? Storage::disk('public')->url($this->avatar)
                : 'https://ui-avatars.com/api/?background=7c3aed&color=fff&name='.urlencode($this->name ?: 'G');
        }

        return $this->otherUser($me)?->avatarUrl() ?? '';
    }

    public function url(): string
    {
        return route('messages.show', $this->id);
    }
}
