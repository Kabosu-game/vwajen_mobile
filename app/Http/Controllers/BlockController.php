<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Follow;
use App\Models\Mute;
use App\Models\User;
use App\Services\FeedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlockController extends Controller
{
    public function block(Request $request, User $user)
    {
        $me = $request->user();
        abort_if($me->id === $user->id, 422);

        DB::transaction(function () use ($me, $user) {
            Block::firstOrCreate(['blocker_id' => $me->id, 'blocked_id' => $user->id]);
            // Un blocage supprime les abonnements dans les deux sens.
            foreach ([[$me, $user], [$user, $me]] as [$a, $b]) {
                $f = Follow::where('follower_id', $a->id)->where('following_id', $b->id)->first();
                if ($f) {
                    if ($f->status === 'accepted') {
                        $a->following_count > 0 && $a->decrement('following_count');
                        $b->followers_count > 0 && $b->decrement('followers_count');
                    }
                    $f->delete();
                }
            }
        });
        FeedService::forget($me);

        return $this->reply($request, ['blocked' => true], __(':name est bloqué.', ['name' => $user->name]));
    }

    public function unblock(Request $request, User $user)
    {
        Block::where('blocker_id', $request->user()->id)->where('blocked_id', $user->id)->delete();

        return $this->reply($request, ['blocked' => false], __(':name est débloqué.', ['name' => $user->name]));
    }

    public function toggleMute(Request $request, User $user)
    {
        $mute = Mute::where('user_id', $request->user()->id)->where('muted_id', $user->id)->first();
        $mute ? $mute->delete() : Mute::create(['user_id' => $request->user()->id, 'muted_id' => $user->id]);
        FeedService::forget($request->user());

        return $this->reply($request, ['muted' => ! $mute], $mute ? __('Compte réactivé.') : __('Compte masqué de votre fil.'));
    }
}
