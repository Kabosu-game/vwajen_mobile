<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Concerns\PurgesCompletely;

class Comment extends Model
{
    use PurgesCompletely;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['edited_at' => 'datetime', 'is_hidden' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->where('is_hidden', false)->oldest();
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    public function isLikedBy(?User $user): bool
    {
        return $user && ($this->liked ?? $this->likes()->where('user_id', $user->id)->exists());
    }

    public function url(): string
    {
        return ($this->commentable?->url() ?? url('/')).'#comment-'.$this->id;
    }
}
