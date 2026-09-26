@extends('layouts.app')
@section('title', 'Vwajèn Mond — '.country_name($country))
@section('content')
    <div class="page-head"><a href="{{ route('mond.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a>
        <div><h1>{{ country_name($country) }}</h1><div class="small faint">{{ trans_choice(':n membre|:n membres', $members, ['n' => $members]) }}</div></div></div>
    @if($cities->isNotEmpty())
        <div class="chip-row mb">@foreach($cities as $city => $n)<a href="{{ route('mond.country', [$country, 'tab' => 'people', 'city' => $city]) }}" class="pill">{{ $city }} <span class="faint">{{ $n }}</span></a>@endforeach</div>
    @endif
    <nav class="tabs card" style="margin-bottom:.75rem">
        @foreach(['posts' => __('Publications'), 'people' => __('Profils'), 'communities' => __('Communautés'), 'events' => __('Événements'), 'lives' => __('Lives')] as $k => $l)
            <a href="{{ route('mond.country', [$country, 'tab' => $k]) }}" class="tab {{ $tab === $k ? 'active' : '' }}">{{ $l }}</a>
        @endforeach
    </nav>
    @switch($tab)
        @case('people')<div class="card">@forelse($items as $u) @include('partials.user-card', ['u' => $u]) @empty <div class="empty"><p>{{ __('Aucun profil.') }}</p></div> @endforelse</div>@break
        @case('communities')<div class="card">@forelse($items as $c)<a href="{{ $c->url() }}" class="item" style="color:var(--text)"><img src="{{ $c->avatarUrl() }}" alt="" class="avatar avatar-sq"><div class="item-body"><strong>{{ $c->name }}</strong><div class="small faint">{{ trans_choice(':n membre|:n membres', $c->members_count, ['n' => $c->members_count]) }}</div></div></a>@empty<div class="empty"><p>{{ __('Aucune communauté.') }}</p></div>@endforelse</div>@break
        @case('events')<div class="card">@forelse($items as $e) @include('partials.event-row', ['e' => $e]) @empty <div class="empty"><p>{{ __('Aucun événement.') }}</p></div> @endforelse</div>@break
        @case('lives')<div class="grid grid-2">@forelse($items as $l) @include('partials.live-tile', ['l' => $l]) @empty <div class="card empty" style="grid-column:1/-1"><p>{{ __('Aucun live.') }}</p></div> @endforelse</div>@break
        @default<div class="card feed">@forelse($items as $post) @include('partials.post-card', ['post' => $post]) @empty <div class="empty"><p>{{ __('Aucune publication.') }}</p></div> @endforelse</div>
    @endswitch
    {{ $items->links() }}
@endsection
