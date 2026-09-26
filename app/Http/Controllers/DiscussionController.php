<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Discussion;
use App\Services\AntiSpam;
use App\Services\ContentService;
use Illuminate\Http\Request;

class DiscussionController extends Controller
{
    public function show(Request $request, Community $community, Discussion $discussion)
    {
        $user = $request->user();
        abort_unless($discussion->community_id === $community->id && $community->canView($user), 404);
        abort_if($discussion->is_hidden && ! $community->isModerator($user), 404);
        $discussion->load('user');
        $comments = $discussion->rootComments()->where('is_hidden', false)->with(['user', 'replies.user'])
            ->withExists(['likes as liked' => fn ($q) => $q->where('user_id', $user?->id)])->oldest()->paginate(30);

        return view('communities.discussion', compact('community', 'discussion', 'comments'));
    }

    public function store(Request $request, Community $community, AntiSpam $spam, ContentService $content)
    {
        $user = $request->user();
        abort_unless($community->isMember($user), 403, __('Rejoignez la communauté pour participer.'));
        $data = $request->validate(['title' => ['required', 'string', 'max:200'], 'body' => ['required', 'string', 'max:10000']]);
        $spam->checkContent($user, $data['title'].' '.$data['body'], 'body');
        $discussion = $community->discussions()->create($data + ['user_id' => $user->id]);
        $content->syncAll($discussion, $discussion->body, $user);

        return redirect()->route('discussions.show', [$community, $discussion]);
    }

    public function moderate(Request $request, Community $community, Discussion $discussion)
    {
        abort_unless($discussion->community_id === $community->id && $community->isModerator($request->user()), 403);
        $action = $request->validate(['action' => ['required', 'in:pin,lock,hide']])['action'];
        $field = ['pin' => 'is_pinned', 'lock' => 'is_locked', 'hide' => 'is_hidden'][$action];
        $discussion->update([$field => ! $discussion->{$field}]);

        return back()->with('status', __('Discussion mise à jour.'));
    }

    public function destroy(Request $request, Community $community, Discussion $discussion)
    {
        abort_unless($discussion->community_id === $community->id
            && ($discussion->user_id === $request->user()->id || $community->isModerator($request->user())), 403);
        $discussion->delete();

        return redirect()->route('communities.show', [$community, 'tab' => 'discussions'])->with('status', __('Discussion supprimée.'));
    }
}
