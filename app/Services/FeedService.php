<?php

namespace App\Services;

use App\Models\CommunityMember;
use App\Models\Mute;
use App\Models\Post;
use App\Models\Repost;
use App\Models\User;
use App\Support\Morph;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Fils d'actualité :
 *  - personalized : abonnements + hashtags suivis + communautés + contenus populaires, classés par score
 *  - chronological : abonnements uniquement, du plus récent au plus ancien
 *  - popular : publications les plus engageantes des 7 derniers jours
 *  - recent : toutes les publications publiques récentes
 * Chaque élément : ['kind' => 'post'|'repost', 'model' => Model, 'reposter' => ?User, 'at' => Carbon]
 */
class FeedService
{
    public const PER_PAGE = 20;

    public function feed(string $mode, ?User $user, int $page = 1): LengthAwarePaginator
    {
        return match ($mode) {
            'chronological' => $user ? $this->chronological($user, $page) : $this->recent(null, $page),
            'popular' => $this->popular($user, $page),
            'recent' => $this->recent($user, $page),
            default => $user ? $this->personalized($user, $page) : $this->popular(null, $page),
        };
    }

    private function basePosts(?User $user)
    {
        $q = Post::query()->visibleTo($user)->audienceFor($user);
        if ($user) {
            $q->whereNotIn('posts.user_id', Mute::where('user_id', $user->id)->select('muted_id'));
        }
        $langs = $user?->content_prefs['languages'] ?? null;
        if ($langs) {
            $q->where(fn ($q) => $q->whereNull('lang')->orWhereIn('lang', $langs));
        }

        return $q;
    }

    public function chronological(User $user, int $page): LengthAwarePaginator
    {
        $ids = array_merge($user->followingIds(), [$user->id]);
        $hideReposts = (bool) ($user->content_prefs['hide_reposts'] ?? false);

        $posts = $this->basePosts($user)->whereIn('posts.user_id', $ids)
            ->selectRaw("'post' as kind, posts.id as item_id, 'post' as item_type, posts.created_at as sort_at, NULL as reposter_id");

        $union = $posts;
        if (! $hideReposts) {
            $reposts = Repost::query()->whereIn('user_id', $user->followingIds())
                ->whereIn('repostable_type', Morph::INTERACTABLE)
                ->selectRaw("'repost' as kind, repostable_id as item_id, repostable_type as item_type, created_at as sort_at, user_id as reposter_id");
            $union = $posts->unionAll($reposts);
        }

        $total = DB::query()->fromSub($union, 'f')->count();
        $rows = DB::query()->fromSub($union, 'f')->orderByDesc('sort_at')
            ->forPage($page, self::PER_PAGE)->get();

        return $this->paginate($this->hydrate($rows, $user), $total, $page);
    }

    public function personalized(User $user, int $page): LengthAwarePaginator
    {
        $items = Cache::remember("feed.p.{$user->id}", now()->addMinutes(3), function () use ($user) {
            $following = $user->followingIds();
            $tagIds = $user->followedHashtags()->pluck('hashtags.id')->all();
            $communityIds = CommunityMember::where('user_id', $user->id)->where('status', 'approved')->pluck('community_id')->all();
            $since = now()->subDays(10);

            $candidates = $this->basePosts($user)->where('posts.created_at', '>=', $since)
                ->where(function ($q) use ($following, $user, $tagIds, $communityIds) {
                    $q->whereIn('posts.user_id', array_merge($following, [$user->id]))
                        ->orWhereIn('posts.community_id', $communityIds ?: [0])
                        ->orWhereHas('hashtags', fn ($h) => $h->whereIn('hashtags.id', $tagIds ?: [0]))
                        ->orWhere(fn ($q) => $q->where('likes_count', '>=', 3)->where('visibility', 'public'));
                })
                ->latest('posts.created_at')->limit(400)
                ->get(['posts.id', 'posts.user_id', 'posts.community_id', 'posts.created_at', 'likes_count', 'comments_count', 'reposts_count', 'shares_count', 'country']);

            $interests = collect($user->interests ?? []);
            $scored = $candidates->map(function ($p) use ($following, $user, $communityIds) {
                $hours = max(1, $p->created_at->diffInMinutes(now()) / 60);
                $engagement = log(2 + $p->likes_count + 2 * $p->comments_count + 3 * $p->reposts_count + 2 * $p->shares_count);
                $affinity = 1.0;
                if ($p->user_id === $user->id || in_array($p->user_id, $following, true)) {
                    $affinity += 2.0;
                }
                if (in_array($p->community_id, $communityIds, true)) {
                    $affinity += 1.0;
                }
                if ($p->country && $p->country === $user->country) {
                    $affinity += 0.3;
                }

                return ['kind' => 'post', 'type' => 'post', 'id' => $p->id, 'reposter_id' => null, 'at' => $p->created_at->timestamp,
                    'score' => $affinity * $engagement / pow($hours + 2, 1.3)];
            });

            if (! ($user->content_prefs['hide_reposts'] ?? false)) {
                $reposts = Repost::whereIn('user_id', $following)->where('created_at', '>=', $since)
                    ->whereIn('repostable_type', Morph::INTERACTABLE)->latest()->limit(100)->get();
                foreach ($reposts as $r) {
                    $hours = max(1, $r->created_at->diffInMinutes(now()) / 60);
                    $scored->push(['kind' => 'repost', 'type' => $r->repostable_type, 'id' => $r->repostable_id, 'reposter_id' => $r->user_id,
                        'at' => $r->created_at->timestamp, 'score' => 2.5 / pow($hours + 2, 1.3)]);
                }
            }

            return $scored->sortByDesc('score')->unique(fn ($i) => $i['type'].$i['id'])->values()->all();
        });

        $slice = collect($items)->forPage($page, self::PER_PAGE)->map(fn ($i) => (object) [
            'kind' => $i['kind'], 'item_type' => $i['type'], 'item_id' => $i['id'], 'reposter_id' => $i['reposter_id'],
            'sort_at' => date('Y-m-d H:i:s', $i['at']),
        ]);

        return $this->paginate($this->hydrate($slice, $user), count($items), $page);
    }

    public function popular(?User $user, int $page): LengthAwarePaginator
    {
        $p = $this->basePosts($user)->where('visibility', 'public')->popular(7)->withCardRelations($user)
            ->paginate(self::PER_PAGE, ['posts.*'], 'page', $page);

        return $this->wrapPosts($p);
    }

    public function recent(?User $user, int $page): LengthAwarePaginator
    {
        $p = $this->basePosts($user)->where('visibility', 'public')->latest('posts.created_at')->withCardRelations($user)
            ->paginate(self::PER_PAGE, ['posts.*'], 'page', $page);

        return $this->wrapPosts($p);
    }

    private function wrapPosts(LengthAwarePaginator $p): LengthAwarePaginator
    {
        $p->setCollection($p->getCollection()->map(fn ($post) => ['kind' => 'post', 'model' => $post, 'reposter' => null, 'at' => $post->created_at]));

        return $p->withQueryString();
    }

    /** Charge les modèles des lignes du fil, en respectant visibilité et blocages. */
    public function hydrate(Collection $rows, ?User $user): Collection
    {
        $models = [];
        foreach ($rows->groupBy('item_type') as $type => $group) {
            $class = Morph::classFor($type);
            if (! $class) {
                continue;
            }
            $q = $class::query()->whereIn('id', $group->pluck('item_id'))->visibleTo($user)->withViewerState($user);
            $q = $type === 'post' ? $q->withCardRelations($user) : $q->with('user');
            foreach ($q->get() as $m) {
                $models[$type.':'.$m->id] = $m;
            }
        }
        $reposters = User::whereIn('id', $rows->pluck('reposter_id')->filter()->unique())->get()->keyBy('id');

        return $rows->map(function ($r) use ($models, $reposters) {
            $model = $models[$r->item_type.':'.$r->item_id] ?? null;

            return $model ? [
                'kind' => $r->kind, 'model' => $model,
                'reposter' => $r->reposter_id ? $reposters->get($r->reposter_id) : null,
                'at' => Carbon::parse($r->sort_at),
            ] : null;
        })->filter()->values();
    }

    private function paginate(Collection $items, int $total, int $page): LengthAwarePaginator
    {
        return (new LengthAwarePaginator($items, $total, self::PER_PAGE, $page, ['path' => request()->url()]))->withQueryString();
    }

    public static function forget(User $user): void
    {
        Cache::forget("feed.p.{$user->id}");
    }
}
