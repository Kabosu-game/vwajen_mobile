<?php

namespace App\Models\Concerns;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Hashtag;
use App\Models\HiddenContent;
use App\Models\Like;
use App\Models\Mention;
use App\Models\Report;
use App\Models\Repost;
use App\Models\Share;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

/**
 * Likes, commentaires, reposts, partages, enregistrements, signalements,
 * hashtags et mentions pour tout contenu (Vwa, vidéo, short, live, débat, question, événement…).
 */
trait Interactable
{
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function rootComments(): MorphMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    public function reposts(): MorphMany
    {
        return $this->morphMany(Repost::class, 'repostable');
    }

    public function shares(): MorphMany
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function hashtags(): MorphToMany
    {
        return $this->morphToMany(Hashtag::class, 'hashtaggable', 'hashtaggables');
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    /** Ajoute liked / bookmarked / reposted pour l'utilisateur courant (évite le N+1). */
    public function scopeWithViewerState(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query;
        }

        return $query->withExists([
            'likes as liked' => fn ($q) => $q->where('user_id', $user->id),
            'bookmarks as bookmarked' => fn ($q) => $q->where('user_id', $user->id),
            'reposts as reposted' => fn ($q) => $q->where('user_id', $user->id),
        ]);
    }

    /** Exclut le contenu masqué par la modération, par l'utilisateur, ou venant de comptes bloqués/suspendus. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $table = $this->getTable();
        $query->where("$table.is_hidden", false)
            ->whereHas('user', fn ($q) => $q->where('status', 'active'));

        if ($user) {
            $blocked = $user->blockedIdsBothWays();
            if ($blocked) {
                $query->whereNotIn("$table.user_id", $blocked);
            }
            $query->whereNotIn("$table.id", HiddenContent::query()
                ->select('hideable_id')
                ->where('user_id', $user->id)
                ->where('hideable_type', $this->getMorphClass()));
        }

        return $query;
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->liked ?? $this->likes()->where('user_id', $user->id)->exists();
    }

    public function isBookmarkedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->bookmarked ?? $this->bookmarks()->where('user_id', $user->id)->exists();
    }

    public function isRepostedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->reposted ?? $this->reposts()->where('user_id', $user->id)->exists();
    }

    public function morphKey(): string
    {
        return $this->getMorphClass();
    }

    public function shareTitle(): string
    {
        return (string) ($this->title ?? Str::limit(strip_tags((string) ($this->body ?? '')), 80));
    }

    abstract public function url(): string;
}
