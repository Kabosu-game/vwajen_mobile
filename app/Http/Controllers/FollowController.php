<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use App\Services\FeedService;
use App\Services\Notifier;
use Illuminate\Http\Request;

/** Suivre / ne plus suivre, demandes d'abonnement pour les profils privés. */
class FollowController extends Controller
{
    public function __construct(private Notifier $notifier) {}

    public function follow(Request $request, User $user)
    {
        $me = $request->user();
        abort_if($me->id === $user->id, 422);
        abort_if($me->isBlockedBetween($user), 403, __('Action impossible.'));

        $needsApproval = $user->is_private && $user->account_type === 'personal';
        $follow = Follow::firstOrCreate(
            ['follower_id' => $me->id, 'following_id' => $user->id],
            ['status' => $needsApproval ? 'pending' : 'accepted'],
        );

        if ($follow->wasRecentlyCreated) {
            if ($follow->status === 'accepted') {
                $me->increment('following_count');
                $user->increment('followers_count');
                $this->notifier->send($user, 'follow', $me, ':name a commencé à vous suivre', ['name' => $me->name], $me->profileUrl(), $me);
            } else {
                $this->notifier->send($user, 'follow', $me, ':name souhaite vous suivre', ['name' => $me->name], route('follow.requests'), $me);
            }
        }
        FeedService::forget($me);

        return $this->reply($request, ['state' => $follow->status, 'followers' => $user->fresh()->followers_count],
            $follow->status === 'pending' ? __('Demande envoyée.') : __('Vous suivez :name.', ['name' => $user->name]));
    }

    public function unfollow(Request $request, User $user)
    {
        $me = $request->user();
        $follow = Follow::where('follower_id', $me->id)->where('following_id', $user->id)->first();
        if ($follow) {
            if ($follow->status === 'accepted') {
                $me->following_count > 0 && $me->decrement('following_count');
                $user->followers_count > 0 && $user->decrement('followers_count');
            }
            $follow->delete();
        }
        FeedService::forget($me);

        return $this->reply($request, ['state' => 'none', 'followers' => $user->fresh()->followers_count], __('Vous ne suivez plus :name.', ['name' => $user->name]));
    }

    /** Recevoir une notification à chaque publication / live de ce compte. */
    public function toggleNotify(Request $request, User $user)
    {
        $follow = Follow::where('follower_id', $request->user()->id)->where('following_id', $user->id)->where('status', 'accepted')->firstOrFail();
        $follow->update(['notify' => ! $follow->notify]);

        return $this->reply($request, ['active' => $follow->notify],
            $follow->notify ? __('Notifications activées pour ce compte.') : __('Notifications désactivées pour ce compte.'));
    }

    public function requests(Request $request)
    {
        $requests = Follow::with('follower')->where('following_id', $request->user()->id)->where('status', 'pending')->latest()->paginate(30);

        return view('social.follow-requests', compact('requests'));
    }

    public function accept(Request $request, Follow $follow)
    {
        abort_unless($follow->following_id === $request->user()->id && $follow->status === 'pending', 403);
        $follow->update(['status' => 'accepted']);
        $request->user()->increment('followers_count');
        $follow->follower->increment('following_count');
        $this->notifier->send($follow->follower, 'follow', $request->user(), ':name a accepté votre demande d\'abonnement', ['name' => $request->user()->name], $request->user()->profileUrl());

        return back()->with('status', __('Demande acceptée.'));
    }

    public function decline(Request $request, Follow $follow)
    {
        abort_unless($follow->following_id === $request->user()->id, 403);
        $follow->delete();

        return back()->with('status', __('Demande refusée.'));
    }
}
