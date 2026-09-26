<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['last_read_at' => 'datetime', 'cleared_at' => 'datetime', 'left_at' => 'datetime', 'is_admin' => 'boolean', 'muted' => 'boolean'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unreadCount(): int
    {
        return Message::where('conversation_id', $this->conversation_id)
            ->where('user_id', '!=', $this->user_id)
            ->when($this->last_read_at, fn ($q) => $q->where('created_at', '>', $this->last_read_at))
            ->when($this->cleared_at, fn ($q) => $q->where('created_at', '>', $this->cleared_at))
            ->count();
    }
}
