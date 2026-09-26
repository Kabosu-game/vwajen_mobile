@extends('layouts.app')
@section('title', __('Accueil'))
@section('content')
    <div class="page-head" style="padding-bottom:0;flex-direction:column;align-items:stretch">
        <div class="row between"><h1>{{ __('Accueil') }}</h1>
            @auth<a href="{{ route('settings.interests') }}" class="btn btn-ghost btn-icon" aria-label="{{ __('Préférences du fil') }}"><x-icon name="filter"/></a>@endauth
        </div>
        <nav class="tabs" style="background:transparent;margin-top:.4rem" aria-label="{{ __('Type de fil') }}">
            @auth
                <a href="{{ route('home', ['feed' => 'personalized']) }}" class="tab {{ $mode === 'personalized' ? 'active' : '' }}">{{ __('Pour vous') }}</a>
                <a href="{{ route('home', ['feed' => 'chronological']) }}" class="tab {{ $mode === 'chronological' ? 'active' : '' }}">{{ __('Abonnements') }}</a>
            @endauth
            <a href="{{ route('home', ['feed' => 'popular']) }}" class="tab {{ $mode === 'popular' ? 'active' : '' }}">{{ __('Populaires') }}</a>
            <a href="{{ route('home', ['feed' => 'recent']) }}" class="tab {{ $mode === 'recent' ? 'active' : '' }}">{{ __('Récentes') }}</a>
        </nav>
    </div>

    @guest
        <div class="hero">
            <h2>{{ __('Bienvenue sur Vwajèn') }}</h2>
            <p>{{ __('Publiez, débattez, comparez les programmes et posez vos questions aux candidats. En kreyòl, en français et en anglais.') }}</p>
            <div class="row wrap"><a href="{{ route('register') }}" class="btn" style="background:#fff;color:#0b1f5c">{{ __('Créer un compte') }}</a>
                <a href="{{ route('candidates.index') }}" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)">{{ __('Voir les candidats') }}</a></div>
        </div>
    @endguest

    @if($liveNow->isNotEmpty())
        <div class="card"><div class="card-body" style="padding:.75rem">
            <div class="row between mb"><strong><span class="badge badge-live">● LIVE</span> {{ __('En direct maintenant') }}</strong><a href="{{ route('lives.index') }}" class="small">{{ __('Tout voir') }}</a></div>
            <div class="chip-row">
                @foreach($liveNow as $l)
                    <a href="{{ $l->url() }}" class="center" style="width:78px;flex-shrink:0;color:var(--text)">
                        <img src="{{ $l->user->avatarUrl() }}" alt="" class="avatar avatar-lg" style="margin:0 auto;border:3px solid var(--live)">
                        <div class="small truncate mt-sm">{{ $l->user->name }}</div>
                    </a>
                @endforeach
            </div>
        </div></div>
    @endif

    @auth
        @include('partials.composer')
    @endauth

    @if($upcomingDebates->isNotEmpty() && $items->currentPage() === 1)
        <div class="card"><div class="card-head"><h3><x-icon name="debate" style="vertical-align:-4px"/> {{ __('Débats à venir') }}</h3><a href="{{ route('debates.index') }}" class="small">{{ __('Tout voir') }}</a></div>
            @foreach($upcomingDebates as $d)
                <a href="{{ $d->url() }}" class="item" style="color:var(--text)">
                    <div class="date-box"><div class="m">{{ $d->scheduled_at->translatedFormat('M') }}</div><div class="d">{{ $d->scheduled_at->format('d') }}</div></div>
                    <div class="item-body"><strong>{{ $d->title }}</strong><div class="small muted">{{ $d->scheduled_at->translatedFormat('l H:i') }}</div></div>
                </a>
            @endforeach
        </div>
    @endif

    <div class="card feed" id="feed">
        @include('partials.feed-items')
    </div>
@endsection
