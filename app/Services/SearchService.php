<?php

namespace App\Services;

use App\Models\Community;
use App\Models\Debate;
use App\Models\Event;
use App\Models\Hashtag;
use App\Models\Live;
use App\Models\Post;
use App\Models\Question;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;

/** Recherche globale et par type, avec filtres et recherche intelligente (termes étendus par IA). */
class SearchService
{
    public const TYPES = ['all', 'users', 'candidates', 'posts', 'videos', 'shorts', 'lives', 'events', 'debates', 'questions', 'hashtags', 'communities'];

    public function __construct(private AiService $ai) {}

    public function terms(string $q, bool $smart): array
    {
        $terms = [trim($q)];
        if ($smart) {
            $terms = array_merge($terms, $this->ai->expandSearch($q));
        }

        return array_values(array_unique(array_filter($terms)));
    }

    private function like(Builder $query, array $columns, array $terms): Builder
    {
        return $query->where(function ($w) use ($columns, $terms) {
            foreach ($terms as $t) {
                $t = str_replace(['%', '_'], ['\%', '\_'], $t);
                foreach ($columns as $c) {
                    $w->orWhere($c, 'like', "%{$t}%");
                }
            }
        });
    }

    public function search(string $type, string $q, array $filters, ?User $viewer, bool $smart = false, int $perPage = 20)
    {
        $q = ltrim(trim($q));
        $isTag = str_starts_with($q, '#');
        $isUser = str_starts_with($q, '@');
        $terms = $this->terms(ltrim($q, '#@'), $smart && ! $isTag && ! $isUser);

        $since = match ($filters['period'] ?? null) {
            'day' => now()->subDay(), 'week' => now()->subWeek(), 'month' => now()->subMonth(), 'year' => now()->subYear(), default => null,
        };
        $sort = $filters['sort'] ?? 'relevance';

        switch ($type) {
            case 'users':
            case 'candidates':
                $query = User::query()->where('status', 'active')->where('searchable', true);
                if ($type === 'candidates') {
                    $query->where('account_type', 'candidate')->with('candidateProfile');
                    if (! empty($filters['department'])) {
                        $query->whereHas('candidateProfile', fn ($c) => $c->where('department', $filters['department']));
                    }
                    if (! empty($filters['position'])) {
                        $query->whereHas('candidateProfile', fn ($c) => $c->where('position_sought', $filters['position']));
                    }
                } elseif (! empty($filters['account_type'])) {
                    $query->where('account_type', $filters['account_type']);
                }
                if (! empty($filters['verified'])) {
                    $query->where('is_verified', true);
                }
                if (! empty($filters['country'])) {
                    $query->where('country', $filters['country']);
                }
                if ($viewer) {
                    $query->whereNotIn('id', $viewer->blockedIdsBothWays());
                }
                $this->like($query, $isUser ? ['username'] : ['name', 'username', 'bio'], $terms);

                return $query->orderByDesc('is_verified')->orderByDesc('followers_count')->paginate($perPage)->withQueryString();

            case 'posts':
                $query = Post::query()->visibleTo($viewer)->audienceFor($viewer)->withCardRelations($viewer);
                $isTag ? $query->whereHas('hashtags', fn ($h) => $h->whereIn('name', array_map('mb_strtolower', $terms)))
                    : $this->like($query, ['body', 'link_title'], $terms);
                if (! empty($filters['media'])) {
                    $query->whereHas('media');
                }
                if (! empty($filters['from'])) {
                    $query->whereHas('user', fn ($u) => $u->where('username', ltrim($filters['from'], '@')));
                }
                break;

            case 'videos':
            case 'shorts':
                $query = Video::query()->visibleTo($viewer)->published()->with('user')->withViewerState($viewer);
                $type === 'shorts' ? $query->shorts() : $query->longs();
                $isTag ? $query->whereHas('hashtags', fn ($h) => $h->whereIn('name', array_map('mb_strtolower', $terms)))
                    : $this->like($query, ['title', 'description'], $terms);
                break;

            case 'lives':
                $query = Live::query()->visibleTo($viewer)->with('user')->where('status', '!=', 'cancelled');
                $this->like($query, ['title', 'description'], $terms);
                if (! empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
                break;

            case 'events':
                $query = Event::query()->visibleTo($viewer)->with('user');
                $this->like($query, ['title', 'description', 'city', 'location_name'], $terms);
                if (! empty($filters['department'])) {
                    $query->where('department', $filters['department']);
                }
                if (($filters['when'] ?? null) === 'upcoming') {
                    $query->where('starts_at', '>=', now());
                } elseif (($filters['when'] ?? null) === 'past') {
                    $query->where('starts_at', '<', now());
                }
                break;

            case 'debates':
                $query = Debate::query()->visibleTo($viewer)->with(['user', 'participants.user']);
                $this->like($query, ['title', 'description', 'constituency'], $terms);
                break;

            case 'questions':
                $query = Question::query()->visibleTo($viewer)->with(['user', 'candidate']);
                $this->like($query, ['title', 'body'], $terms);
                if (! empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
                break;

            case 'hashtags':
                $query = Hashtag::query()->where('is_blocked', false);
                $this->like($query, ['name'], array_map('mb_strtolower', $terms));

                return $query->orderByDesc('uses_count')->paginate($perPage)->withQueryString();

            case 'communities':
                $query = Community::query()->where('is_hidden', false);
                $this->like($query, ['name', 'description'], $terms);

                return $query->orderByDesc('members_count')->paginate($perPage)->withQueryString();

            default:
                return null;
        }

        $table = $query->getModel()->getTable();
        if ($since) {
            $query->where("$table.created_at", '>=', $since);
        }
        if ($sort === 'recent') {
            $query->latest("$table.created_at");
        } elseif ($sort === 'popular' && in_array($type, ['posts', 'videos', 'shorts'], true)) {
            $query->orderByDesc("$table.likes_count");
        } else {
            $query->latest("$table.created_at");
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /** Recherche globale : quelques résultats de chaque type. */
    public function global(string $q, ?User $viewer, bool $smart = false): array
    {
        $out = [];
        foreach (['users', 'candidates', 'posts', 'videos', 'shorts', 'lives', 'events', 'debates', 'questions', 'hashtags', 'communities'] as $t) {
            $out[$t] = $this->search($t, $q, [], $viewer, $smart, 5);
        }

        return $out;
    }
}
