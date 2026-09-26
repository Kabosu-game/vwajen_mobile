@extends('layouts.app')
@section('title', __('Membres — :name', ['name' => $community->name]))
@php $viewer = auth()->user(); $canMod = $community->isModerator($viewer); $canAdmin = $community->isAdmin($viewer); @endphp
@section('content')
    <div class="page-head"><a href="{{ $community->url() }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Membres') }}</h1></div>
    @if($canMod)
        <nav class="tabs card" style="margin-bottom:.75rem"><a href="?" class="tab {{ $status === 'approved' ? 'active' : '' }}">{{ __('Membres') }}</a><a href="?status=pending" class="tab {{ $status === 'pending' ? 'active' : '' }}">{{ __('Demandes en attente') }}</a></nav>
    @endif
    <div class="card">
        @forelse($members as $m)
            <div class="user-card">
                <img src="{{ $m->user->avatarUrl() }}" alt="" class="avatar">
                <div class="info"><a href="{{ $m->user->profileUrl() }}" class="name">{{ $m->user->name }}</a>@include('partials.verified', ['u' => $m->user])<div class="handle small">{{ '@'.$m->user->username }}</div></div>
                @if($m->role !== 'member')<span class="badge badge-primary">{{ $m->role === 'admin' ? __('Administrateur') : __('Modérateur') }}</span>@endif
                @if($canMod && $m->user_id !== $community->owner_id && $m->user_id !== $viewer->id)
                    <div class="dropdown"><button class="btn btn-ghost btn-icon btn-sm" data-dropdown aria-label="{{ __('Gérer') }}"><x-icon name="more"/></button>
                        <div class="menu">
                            @php $actions = $status === 'pending' ? ['approve' => __('Approuver'), 'remove' => __('Refuser')] : array_merge($canAdmin ? ['admin' => __('Nommer administrateur'), 'moderator' => __('Nommer modérateur'), 'member' => __('Membre simple')] : [], ['remove' => __('Retirer'), 'ban' => __('Exclure définitivement')]); @endphp
                            @foreach($actions as $a => $l)
                                <form method="POST" action="{{ route('communities.members.update', [$community, $m]) }}">@csrf<input type="hidden" name="action" value="{{ $a }}"><button class="{{ in_array($a, ['remove', 'ban']) ? 'danger' : '' }}">{{ $l }}</button></form>
                            @endforeach
                        </div></div>
                @endif
            </div>
        @empty <div class="empty"><p>{{ __('Personne ici.') }}</p></div> @endforelse
    </div>
    {{ $members->links() }}
@endsection
