@extends('layouts.app')
@section('title', $community->name)
@php $viewer = auth()->user(); $isMember = $membership?->status === 'approved'; @endphp
@section('content')
    <div class="page-head"><a href="{{ route('communities.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1 class="truncate">{{ $community->name }}</h1></div>
    <div class="card" style="overflow:hidden">
        <div class="cover" style="height:150px;@if($community->coverUrl())background-image:url('{{ $community->coverUrl() }}')@endif"></div>
        <div class="profile-head">
            <img src="{{ $community->avatarUrl() }}" alt="" class="avatar avatar-xl avatar-sq" style="border-radius:20px">
            <div class="profile-actions">
                <button type="button" class="btn btn-outline btn-icon" data-share data-share-url="{{ $community->url() }}" data-share-title="{{ $community->name }}" data-share-type="community" data-share-id="{{ $community->id }}" aria-label="{{ __('Partager') }}"><x-icon name="share"/></button>
                @auth
                    <div class="dropdown"><button class="btn btn-outline btn-icon" data-dropdown aria-label="{{ __('Plus') }}"><x-icon name="more"/></button>
                        <div class="menu">
                            @if($community->isAdmin($viewer))<a href="{{ route('communities.edit', $community) }}"><x-icon name="settings"/>{{ __('Paramètres') }}</a>@endif
                            @if($community->isModerator($viewer))<a href="{{ route('communities.members', [$community, 'status' => 'pending']) }}"><x-icon name="user-check"/>{{ __('Demandes') }}</a>@endif
                            <a href="{{ route('reports.create', ['community', $community->id]) }}" class="danger"><x-icon name="flag"/>{{ __('Signaler') }}</a>
                        </div></div>
                @endauth
                @if($isMember)
                    @if($viewer->id !== $community->owner_id)<form method="POST" action="{{ route('communities.leave', $community) }}" data-confirm="{{ __('Quitter cette communauté ?') }}">@csrf<button class="btn btn-outline">{{ __('Quitter') }}</button></form>@endif
                @elseif($membership?->status === 'pending')
                    <button class="btn btn-outline" disabled>{{ __('Demande envoyée') }}</button>
                @elseif($membership?->status !== 'banned')
                    <form method="POST" action="{{ route('communities.join', $community) }}">@csrf<button class="btn btn-dark" data-auth>{{ __('Rejoindre') }}</button></form>
                @endif
            </div>
            <div class="profile-name">{{ $community->name }}</div>
            <div class="row wrap mt-sm" style="gap:.35rem">
                <span class="badge">@if($community->visibility === 'private')<x-icon name="lock" style="width:12px;height:12px"/> {{ __('Privée') }}@else<x-icon name="globe" style="width:12px;height:12px"/> {{ __('Publique') }}@endif</span>
                @if($community->is_diaspora)<span class="badge badge-info">{{ __('Diaspora') }} @if($community->country)· {{ country_name($community->country) }}@endif</span>@endif
                @if($community->category)<span class="category-chip" style="--c:{{ $community->category->color }}">{{ $community->category->name }}</span>@endif
            </div>
            @if($community->description)<p class="mt-sm">{{ $community->description }}</p>@endif
            <div class="profile-stats"><a href="{{ route('communities.members', $community) }}"><b>{{ short_number($community->members_count) }}</b> {{ __('membres') }}</a></div>
        </div>
        <nav class="tabs">
            @foreach(['posts' => __('Publications'), 'discussions' => __('Discussions'), 'events' => __('Événements'), 'about' => __('Règles & infos')] as $k => $l)
                <a href="{{ route('communities.show', [$community, 'tab' => $k]) }}" class="tab {{ $tab === $k ? 'active' : '' }}">{{ $l }}</a>
            @endforeach
        </nav>
    </div>

    @if(! $canView)
        <div class="card empty"><x-icon name="lock"/><h3>{{ __('Communauté privée') }}</h3><p>{{ __('Rejoignez-la pour voir les publications et discussions.') }}</p></div>
    @elseif($tab === 'posts')
        @if($isMember)@include('partials.composer', ['community' => $community, 'placeholder' => __('Publier dans :name…', ['name' => $community->name])])@endif
        <div class="card feed" id="feed">@forelse($items as $post) @include('partials.post-card', ['post' => $post]) @empty <div class="empty"><p>{{ __('Aucune publication.') }}</p></div> @endforelse</div>
        {{ $items->links() }}
    @elseif($tab === 'discussions')
        @if($isMember)
            <details class="card"><summary class="card-body" style="cursor:pointer"><strong>+ {{ __('Nouvelle discussion') }}</strong></summary>
                <form method="POST" action="{{ route('discussions.store', $community) }}" class="card-body" style="padding-top:0">@csrf
                    <input name="title" class="input mb" placeholder="{{ __('Sujet') }}" required maxlength="200" style="margin-bottom:.5rem" aria-label="{{ __('Sujet') }}">
                    <textarea name="body" class="textarea" required placeholder="{{ __('Développez…') }}" aria-label="{{ __('Message') }}" data-mention></textarea>
                    <button class="btn btn-primary mt-sm">{{ __('Publier') }}</button></form></details>
        @endif
        <div class="card">
            @forelse($items as $d)
                <a href="{{ route('discussions.show', [$community, $d]) }}" class="item" style="color:var(--text)">
                    <img src="{{ $d->user->avatarUrl() }}" alt="" class="avatar avatar-sm">
                    <div class="item-body"><div class="row">@if($d->is_pinned)<x-icon name="pinned" style="width:15px;height:15px;color:var(--primary)"/>@endif @if($d->is_locked)<x-icon name="lock" style="width:14px;height:14px"/>@endif<strong>{{ $d->title }}</strong></div>
                        <div class="small faint">{{ $d->user->name }} · {{ $d->created_at->diffForHumans() }} · {{ trans_choice(':n réponse|:n réponses', $d->comments_count, ['n' => $d->comments_count]) }}</div></div></a>
            @empty <div class="empty"><x-icon name="debate"/><p>{{ __('Aucune discussion.') }}</p></div> @endforelse
        </div>
        {{ $items->links() }}
    @elseif($tab === 'events')
        @if($isMember)<a href="{{ route('events.create', ['community' => $community->id]) }}" class="btn btn-outline btn-sm mb"><x-icon name="plus"/>{{ __('Créer un événement') }}</a>@endif
        <div class="card">@forelse($items as $e) @include('partials.event-row', ['e' => $e]) @empty <div class="empty"><p>{{ __('Aucun événement à venir.') }}</p></div> @endforelse</div>
    @else
        <div class="card"><div class="card-head"><h3>{{ __('Règles de communauté') }}</h3></div><div class="card-body prose">{!! $community->rules ? nl2br(e($community->rules)) : '<span class="muted">'.e(__('Les règles générales de Vwajèn s\'appliquent.')).'</span>' !!}</div></div>
        <div class="card"><div class="card-head"><h3>{{ __('Administrateurs et modérateurs') }}</h3></div>
            @foreach($admins as $m)<div class="user-card"><img src="{{ $m->user->avatarUrl() }}" class="avatar avatar-sm" alt=""><div class="info"><a href="{{ $m->user->profileUrl() }}" class="name">{{ $m->user->name }}</a></div><span class="badge badge-primary">{{ $m->role === 'admin' ? __('Administrateur') : __('Modérateur') }}</span></div>@endforeach
        </div>
        <div class="card card-body small muted">{{ __('Créée le :date par :name', ['date' => $community->created_at->translatedFormat('d F Y'), 'name' => $community->owner->name]) }}</div>
    @endif
@endsection
