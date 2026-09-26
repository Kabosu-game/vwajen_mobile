<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Concerns\PurgesCompletely;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use PurgesCompletely;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_hidden' => 'boolean'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shared(): MorphTo
    {
        return $this->morphTo();
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function mediaUrl(): ?string
    {
        return $this->media_path ? Storage::disk('public')->url($this->media_path) : null;
    }

    public function url(): string
    {
        return route('messages.show', $this->conversation_id).'#m'.$this->id;
    }
}
