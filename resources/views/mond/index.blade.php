@extends('layouts.app')
@section('title', 'Vwajèn Mond')
@section('content')
    <div class="hero">
        <h1>Vwajèn Mond 🌍</h1>
        <p>{{ __('La diaspora haïtienne connectée : retrouvez les Haïtiens de votre pays, les communautés, événements, discussions et lives du monde entier.') }}</p>
        @auth<a href="{{ route('mond.country', auth()->user()->country !== 'HT' ? auth()->user()->country : 'US') }}" class="btn" style="background:#fff;color:#0b1f5c">{{ __('Mon pays') }}</a>@endauth
    </div>
    <div class="card"><div class="card-head"><h3>{{ __('Profils par pays') }}</h3></div><div class="card-body pills">
        @forelse($countries as $code => $n)<a href="{{ route('mond.country', $code) }}" class="pill">{{ country_name($code) }} <span class="faint">{{ $n }}</span></a>
        @empty<span class="muted small">{{ __('Aucun membre de la diaspora pour le moment.') }}</span>@endforelse
        @foreach(array_diff_key(config('vwajen.diaspora_countries'), $countries->all()) as $code => $name)<a href="{{ route('mond.country', $code) }}" class="pill" style="opacity:.6">{{ country_name($code) }}</a>@endforeach
    </div></div>

    <div class="section-title"><h2>{{ __('Communautés de diaspora') }}</h2><a href="{{ route('communities.index', ['diaspora' => 1]) }}" class="small">{{ __('Voir tout') }}</a></div>
    <div class="card">@forelse($communities as $c)<a href="{{ $c->url() }}" class="item" style="color:var(--text)"><img src="{{ $c->avatarUrl() }}" alt="" class="avatar avatar-sq"><div class="item-body"><strong>{{ $c->name }}</strong><div class="small faint">{{ country_name($c->country) }} · {{ trans_choice(':n membre|:n membres', $c->members_count, ['n' => $c->members_count]) }}</div></div></a>@empty<div class="empty"><p>{{ __('Aucune communauté.') }}</p>@auth<a href="{{ route('communities.create') }}" class="btn btn-primary btn-sm">{{ __('Créer') }}</a>@endauth</div>@endforelse</div>

    <div class="section-title"><h2>{{ __('Événements internationaux') }}</h2></div>
    <div class="card">@forelse($events as $e) @include('partials.event-row', ['e' => $e]) @empty <div class="empty"><p>{{ __('Aucun événement à venir.') }}</p></div> @endforelse</div>

    @if($lives->isNotEmpty())
        <div class="section-title"><h2>{{ __('Lives de la diaspora') }}</h2></div>
        <div class="grid grid-2">@foreach($lives as $l) @include('partials.live-tile', ['l' => $l]) @endforeach</div>
    @endif

    <div class="section-title"><h2>{{ __('Discussions') }}</h2></div>
    <div class="card">@forelse($discussions as $d)<a href="{{ route('discussions.show', [$d->community, $d]) }}" class="item" style="color:var(--text)"><x-icon name="debate"/><div class="item-body"><strong>{{ $d->title }}</strong><div class="small faint">{{ $d->community->name }} · {{ $d->user->name }}</div></div></a>@empty<div class="empty"><p>{{ __('Aucune discussion.') }}</p></div>@endforelse</div>

    <div class="section-title"><h2>{{ __('Publications de la diaspora') }}</h2></div>
    <div class="card feed">@forelse($posts as $post) @include('partials.post-card', ['post' => $post]) @empty <div class="empty"><p>{{ __('Rien pour le moment.') }}</p></div> @endforelse</div>
@endsection
