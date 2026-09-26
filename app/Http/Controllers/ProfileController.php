<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Event;
use App\Models\Live;
use App\Models\Post;
use App\Models\Question;
use App\Models\Repost;
use App\Models\User;
use App\Models\Video;
use App\Models\View;
use App\Services\FeedService;
use App\Support\Morph;
use Illuminate\Http\Request;

/** Profils personnel, candidat, organisation, élu. */
class ProfileController extends Controller
{
    public const TABS = ['posts', 'videos', 'shorts', 'lives', 'reposts', 'saved', 'events', 'questions', 'media'];

    public function show(Request $request, User $user, FeedService $feed)
    {
        $viewer = $request->user();
        abort_if(($user->status === 'banned' || $user->deletion_requested_at) && ! $viewer?->isStaff() && $viewer?->id !== $user->id, 404);
        $user->load(['candidateProfile', 'organizationProfile', 'officialProfile']);

        if ($viewer?->id !== $user->id && ! $request->session()->has('viewed.user.'.$user->id)) {
            $request->session()->put('viewed.user.'.$user->id, true);
            View::create(['user_id' => $viewer?->id, 'viewable_type' => 'user', 'viewable_id' => $user->id, 'created_at' => now()]);
        }

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'posts';
        $blocked = $viewer && $viewer->isBlockedBetween($user) && ! $viewer->isStaff();
        $canView = ! $blocked && $user->canBeViewedBy($viewer);
        $items = null;

        if ($canView) {
            $items = match ($tab) {
                'videos' => Video::where('user_id', $user->id)->longs()->published()->visibleTo($viewer)->latest()->paginate(18),
                'shorts' => Video::where('user_id', $user->id)->shorts()->published()->visibleTo($viewer)->latest()->paginate(24),
                'lives' => Live::where('user_id', $user->id)->visibleTo($viewer)->with('replay')->latest()->paginate(18),
                'events' => Event::where('user_id', $user->id)->visibleTo($viewer)->latest('starts_at')->paginate(18),
                'questions' => Question::visibleTo($viewer)->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('candidate_id', $user->id))
                    ->with(['user', 'candidate'])->latest()->paginate(20),
                'reposts' => $this->reposts($user, $viewer, $feed),
                'saved' => $viewer?->id === $user->id ? $this->saved($user, $feed) : abort(403),
                'media' => Post::where('posts.user_id', $user->id)->visibleTo($viewer)->audienceFor($viewer)->whereHas('media')
                    ->withCardRelations($viewer)->latest()->paginate(20),
                default => Post::where('posts.user_id', $user->id)->visibleTo($viewer)->audienceFor($viewer)
                    ->withCardRelations($viewer)->latest()->paginate(20),
            };
            $items->withQueryString();
        }

        $mutualFollowers = $viewer && $viewer->id !== $user->id
            ? User::whereIn('id', $viewer->followingIds())->whereHas('following', fn ($q) => $q->where('users.id', $user->id))->limit(3)->get()
            : collect();

        return view('profile.show', compact('user', 'tab', 'items', 'canView', 'blocked', 'mutualFollowers'));
    }

    private function reposts(User $user, ?User $viewer, FeedService $feed)
    {
        $page = Repost::where('user_id', $user->id)->whereIn('repostable_type', Morph::INTERACTABLE)->latest()->paginate(20);
        $rows = $page->getCollection()->map(fn ($r) => (object) ['kind' => 'repost', 'item_type' => $r->repostable_type,
            'item_id' => $r->repostable_id, 'reposter_id' => $user->id, 'sort_at' => $r->created_at]);
        $page->setCollection($feed->hydrate($rows, $viewer));

        return $page;
    }

    /** Contenu enregistré (visible uniquement par son propriétaire). */
    private function saved(User $user, FeedService $feed)
    {
        $page = Bookmark::where('user_id', $user->id)->latest()->paginate(20);
        $rows = $page->getCollection()->map(fn ($b) => (object) ['kind' => 'post', 'item_type' => $b->bookmarkable_type,
            'item_id' => $b->bookmarkable_id, 'reposter_id' => null, 'sort_at' => $b->created_at]);
        $page->setCollection($feed->hydrate($rows, $user));

        return $page;
    }

    public function followers(Request $request, User $user)
    {
        abort_unless($user->canBeViewedBy($request->user()), 403);
        $users = $user->followers()->where('users.status', 'active')->orderByPivot('created_at', 'desc')->paginate(30);

        return view('social.user-list', ['users' => $users, 'title' => __('Abonnés de :name', ['name' => $user->name]), 'owner' => $user]);
    }

    public function following(Request $request, User $user)
    {
        abort_unless($user->canBeViewedBy($request->user()), 403);
        $users = $user->following()->where('users.status', 'active')->orderByPivot('created_at', 'desc')->paginate(30);

        return view('social.user-list', ['users' => $users, 'title' => __('Abonnements de :name', ['name' => $user->name]), 'owner' => $user]);
    }
}
