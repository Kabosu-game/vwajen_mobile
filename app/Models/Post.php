<?php

namespace App\Models;

use App\Models\Concerns\Interactable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\PurgesCompletely;

/** Une « Vwa » : publication du réseau social. */
class Post extends Model
{
    use Interactable, PurgesCompletely;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['edited_at' => 'datetime', 'is_hidden' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function quoteOf(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'quote_of_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('position');
    }

    public function poll(): HasOne
    {
        return $this->hasOne(Poll::class);
    }

    public function edits(): HasMany
    {
        return $this->hasMany(PostEdit::class)->latest();
    }

    public function scopeWithCardRelations(Builder $q, ?User $viewer = null): Builder
    {
        return $q->with(['user', 'media', 'poll.options', 'community', 'quoteOf.user', 'quoteOf.media'])->withViewerState($viewer);
    }

    /** Visibilité : public, abonnés seulement, ou membres de la communauté. */
    public function scopeAudienceFor(Builder $q, ?User $viewer): Builder
    {
        return $q->where(function ($q) use ($viewer) {
            $q->where('visibility', 'public');
            if ($viewer) {
                $q->orWhere('posts.user_id', $viewer->id)
                    ->orWhere(fn ($q) => $q->where('visibility', 'followers')->whereIn('posts.user_id', $viewer->followingIds()))
                    ->orWhere(fn ($q) => $q->where('visibility', 'community')->whereIn('community_id',
                        CommunityMember::where('user_id', $viewer->id)->where('status', 'approved')->select('community_id')));
            }
        })->whereHas('user', function ($q) use ($viewer) {
            $q->where('is_private', false);
            if ($viewer) {
                $q->orWhere('users.id', $viewer->id)->orWhereIn('users.id', $viewer->followingIds());
            }
        });
    }

    public function scopePopular(Builder $q, int $days = 7): Builder
    {
        return $q->where('posts.created_at', '>=', now()->subDays($days))
            ->orderByRaw('(likes_count + comments_count * 2 + reposts_count * 3 + shares_count * 2) DESC')
            ->orderByDesc('posts.created_at');
    }

    public function url(): string
    {
        return route('posts.show', $this->id);
    }

    public function canBeEditedBy(?User $user): bool
    {
        return $user && ($user->id === $this->user_id);
    }

    public function canBeDeletedBy(?User $user): bool
    {
        return $user && ($user->id === $this->user_id || $user->hasPermission('content.delete')
            || ($this->community && $this->community->isModerator($user)));
    }
}
