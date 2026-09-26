<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\Post;
use App\Services\MediaService;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Communautés publiques / privées : membres, administrateurs, modérateurs, règles, publications, discussions. */
class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $communities = Community::where('is_hidden', false)
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('description', 'like', "%$s%")))
            ->when($request->query('category'), fn ($q, $c) => $q->whereHas('category', fn ($x) => $x->where('slug', $c)))
            ->when($request->boolean('diaspora'), fn ($q) => $q->where('is_diaspora', true))
            ->when($request->query('visibility'), fn ($q, $v) => $q->where('visibility', $v))
            ->orderByDesc('members_count')->paginate(24)->withQueryString();
        $mine = $user ? $user->communities()->get() : collect();

        return view('communities.index', ['communities' => $communities, 'mine' => $mine, 'categories' => Category::allActive()]);
    }

    public function show(Request $request, Community $community)
    {
        $user = $request->user();
        abort_if($community->is_hidden && ! $user?->isStaff(), 404);
        $membership = $community->membershipOf($user);
        $canView = $community->canView($user);
        $tab = in_array($request->query('tab'), ['posts', 'discussions', 'events', 'about'], true) ? $request->query('tab') : 'posts';

        $items = null;
        if ($canView) {
            $items = match ($tab) {
                'discussions' => $community->discussions()->where('is_hidden', false)->with('user')->orderByDesc('is_pinned')->latest()->paginate(20),
                'events' => $community->events()->visibleTo($user)->upcoming()->paginate(12),
                'about' => null,
                default => Post::where('community_id', $community->id)->where('posts.is_hidden', false)->withCardRelations($user)->latest()->paginate(20),
            };
            $items?->withQueryString();
        }
        $admins = $community->memberships()->whereIn('role', ['admin', 'moderator'])->where('status', 'approved')->with('user')->get();

        return view('communities.show', compact('community', 'membership', 'canView', 'tab', 'items', 'admins'));
    }

    public function create()
    {
        return view('communities.form', ['community' => new Community(['visibility' => 'public']), 'categories' => Category::allActive()]);
    }

    private function rules(?Community $c = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:3000'],
            'rules' => ['nullable', 'string', 'max:5000'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'visibility' => ['required', 'in:public,private'],
            'is_diaspora' => ['nullable', 'boolean'],
            'country' => ['nullable', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ];
    }

    public function store(Request $request, MediaService $media)
    {
        $user = $request->user();
        abort_unless($user->hasVerifiedEmail(), 403, __('Vérifiez votre adresse e-mail pour créer une communauté.'));
        $data = $request->validate($this->rules());
        $slug = Str::slug($data['name']) ?: 'communaute';
        $base = $slug;
        $i = 1;
        while (Community::where('slug', $slug)->exists() || $slug === 'create') {
            $slug = $base.'-'.$i++;
        }
        $data['avatar'] = $request->hasFile('avatar') ? $media->storeImage($request->file('avatar'), 'communities', 400) : null;
        $data['cover'] = $request->hasFile('cover') ? $media->storeImage($request->file('cover'), 'communities', 1600) : null;
        $data['is_diaspora'] = $request->boolean('is_diaspora');

        $community = Community::create($data + ['owner_id' => $user->id, 'slug' => $slug, 'members_count' => 1]);
        CommunityMember::create(['community_id' => $community->id, 'user_id' => $user->id, 'role' => 'admin', 'status' => 'approved']);

        return redirect()->route('communities.show', $community)->with('status', __('Communauté créée.'));
    }

    public function edit(Request $request, Community $community)
    {
        abort_unless($community->isAdmin($request->user()), 403);

        return view('communities.form', ['community' => $community, 'categories' => Category::allActive()]);
    }

    public function update(Request $request, Community $community, MediaService $media)
    {
        abort_unless($community->isAdmin($request->user()), 403);
        $data = $request->validate($this->rules($community));
        foreach (['avatar' => 400, 'cover' => 1600] as $f => $size) {
            if ($request->hasFile($f)) {
                $media->delete($community->{$f});
                $data[$f] = $media->storeImage($request->file($f), 'communities', $size);
            } else {
                unset($data[$f]);
            }
        }
        $data['is_diaspora'] = $request->boolean('is_diaspora');
        $community->update($data);

        return redirect()->route('communities.show', $community)->with('status', __('Communauté mise à jour.'));
    }

    public function destroy(Request $request, Community $community)
    {
        abort_unless($request->user()->id === $community->owner_id || $request->user()->hasPermission('communities.manage'), 403);
        $community->delete();

        return redirect()->route('communities.index')->with('status', __('Communauté supprimée.'));
    }

    public function join(Request $request, Community $community, Notifier $notifier)
    {
        $user = $request->user();
        $existing = $community->membershipOf($user);
        abort_if($existing?->status === 'banned', 403, __('Vous avez été exclu de cette communauté.'));
        if ($existing) {
            return back();
        }
        $status = $community->visibility === 'private' ? 'pending' : 'approved';
        CommunityMember::create(['community_id' => $community->id, 'user_id' => $user->id, 'status' => $status]);
        if ($status === 'approved') {
            $community->increment('members_count');
        } else {
            $admins = $community->memberships()->where('role', 'admin')->with('user')->get()->pluck('user');
            $notifier->send($admins, 'system', $user, ':name demande à rejoindre :community', ['name' => $user->name, 'community' => $community->name],
                route('communities.members', $community).'?status=pending', $community);
        }

        return back()->with('status', $status === 'pending' ? __('Demande envoyée aux administrateurs.') : __('Bienvenue dans la communauté !'));
    }

    public function leave(Request $request, Community $community)
    {
        $user = $request->user();
        abort_if($user->id === $community->owner_id, 422, __('Le propriétaire ne peut pas quitter sa communauté.'));
        $m = $community->membershipOf($user);
        if ($m && $m->status !== 'banned') {
            if ($m->status === 'approved') {
                $community->members_count > 0 && $community->decrement('members_count');
            }
            $m->delete();
        }

        return back()->with('status', __('Vous avez quitté la communauté.'));
    }

    public function members(Request $request, Community $community)
    {
        abort_unless($community->canView($request->user()), 403);
        $status = $request->query('status') === 'pending' && $community->isModerator($request->user()) ? 'pending' : 'approved';
        $members = $community->memberships()->where('status', $status)->with('user')
            ->orderByRaw("FIELD(role, 'admin', 'moderator', 'member')")->paginate(40)->withQueryString();

        return view('communities.members', compact('community', 'members', 'status'));
    }

    /** Gestion des membres : approuver, promouvoir admin/modérateur, rétrograder, exclure. */
    public function updateMember(Request $request, Community $community, CommunityMember $member)
    {
        $me = $request->user();
        abort_unless($member->community_id === $community->id && $community->isModerator($me), 403);
        $data = $request->validate(['action' => ['required', 'in:approve,admin,moderator,member,remove,ban']]);
        abort_if($member->user_id === $community->owner_id, 422);
        if (in_array($data['action'], ['admin', 'moderator', 'member'], true)) {
            abort_unless($community->isAdmin($me), 403);
        }

        $wasApproved = $member->status === 'approved';
        switch ($data['action']) {
            case 'approve':
                if ($member->status === 'pending') {
                    $member->update(['status' => 'approved']);
                    $community->increment('members_count');
                }
                break;
            case 'remove':
                $member->delete();
                break;
            case 'ban':
                $member->update(['status' => 'banned', 'role' => 'member']);
                break;
            default:
                $member->update(['role' => $data['action']]);
        }
        if ($wasApproved && in_array($data['action'], ['remove', 'ban'], true) && $community->members_count > 0) {
            $community->decrement('members_count');
        }

        return back()->with('status', __('Membre mis à jour.'));
    }
}
