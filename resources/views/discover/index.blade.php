@extends('layouts.app')
@section('title', __('Découvrir'))
@php
    $sections = ['all' => __('Tout'), 'users' => __('Utilisateurs'), 'candidates' => __('Candidats'), 'videos' => __('Vidéos'), 'shorts' => 'Shorts',
        'lives' => __('Lives'), 'debates' => __('Débats'), 'events' => __('Événements'), 'questions' => __('Questions'), 'recent' => __('Contenus récents')];
    $more = fn ($s) => $section === 'all' ? '<a href="'.route('discover.index', ['section' => $s]).'" class="small">'.e(__('Voir plus')).'</a>' : '';
@endphp
@section('content')
    <div class="page-head" style="flex-direction:column;align-items:stretch">
        <form action="{{ route('search.index') }}" class="search-box" role="search" data-suggest>
            <x-icon name="search"/><label for="discover-q" class="sr-only">{{ __('Rechercher') }}</label>
            <input id="discover-q" type="search" name="q" class="input" placeholder="{{ __('Rechercher des personnes, candidats, hashtags…') }}" autocomplete="off">
            <div class="suggest" hidden></div>
        </form>
        <nav class="chip-row mt-sm" aria-label="{{ __('Sections') }}">
            @foreach($sections as $k => $l)<a href="{{ route('discover.index', ['section' => $k]) }}" class="pill {{ $section === $k ? 'active' : '' }}">{{ $l }}</a>@endforeach
        </nav>
    </div>

    @if($section === 'all' && $trends->isNotEmpty())
        <div class="card"><div class="card-head"><h3><x-icon name="trending" style="vertical-align:-4px"/> {{ __('Hashtags populaires') }}</h3><a href="{{ route('trends') }}" class="small">{{ __('Tendances') }}</a></div>
            <div class="card-body pills">@foreach($trends as $t)<a href="{{ route('hashtags.show', $t->name) }}" class="pill">#{{ $t->name }} <span class="faint">{{ $t->recent_uses }}</span></a>@endforeach</div></div>
    @endif

    @isset($lives)
        @if($lives->isNotEmpty())
            <div class="section-title"><h2>{{ __('Lives') }}</h2>{!! $more('lives') !!}</div>
            <div class="grid grid-2">@foreach($lives as $l) @include('partials.live-tile', ['l' => $l]) @endforeach</div>
        @endif
    @endisset
    @isset($candidates)
        <div class="section-title"><h2>{{ __('Candidats') }}</h2>{!! $more('candidates') !!}</div>
        <div class="grid grid-3">@forelse($candidates as $u) @include('partials.candidate-card', ['u' => $u]) @empty <p class="muted">{{ __('Aucun candidat.') }}</p> @endforelse</div>
    @endisset
    @isset($users)
        <div class="section-title"><h2>{{ __('Personnes à suivre') }}</h2>{!! $more('users') !!}</div>
        <div class="card">@forelse($users as $u) @include('partials.user-card', ['u' => $u]) @empty <div class="empty"><p>{{ __('Aucune suggestion.') }}</p></div> @endforelse</div>
    @endisset
    @isset($shorts)
        @if($shorts->isNotEmpty())
            <div class="section-title"><h2>Shorts</h2>{!! $more('shorts') !!}</div>
            <div class="grid grid-4">@foreach($shorts as $v) @include('partials.video-tile', ['v' => $v]) @endforeach</div>
        @endif
    @endisset
    @isset($videos)
        @if($videos->isNotEmpty())
            <div class="section-title"><h2>{{ __('Vidéos') }}</h2>{!! $more('videos') !!}</div>
            <div class="grid grid-2">@foreach($videos as $v) @include('partials.video-tile', ['v' => $v]) @endforeach</div>
        @endif
    @endisset
    @isset($debates)
        @if($debates->isNotEmpty())
            <div class="section-title"><h2>{{ __('Débats') }}</h2>{!! $more('debates') !!}</div>
            <div class="grid grid-2">@foreach($debates as $d) @include('partials.debate-tile', ['d' => $d]) @endforeach</div>
        @endif
    @endisset
    @isset($events)
        @if($events->isNotEmpty())
            <div class="section-title"><h2>{{ __('Événements') }}</h2>{!! $more('events') !!}</div>
            <div class="card">@foreach($events as $e) @include('partials.event-row', ['e' => $e]) @endforeach</div>
        @endif
    @endisset
    @isset($questions)
        @if($questions->isNotEmpty())
            <div class="section-title"><h2>{{ __('Questions ouvertes') }}</h2>{!! $more('questions') !!}</div>
            <div class="card">@foreach($questions as $q) @include('partials.question-row', ['q' => $q]) @endforeach</div>
        @endif
    @endisset
    @isset($recent)
        <div class="section-title"><h2>{{ __('Contenus récents') }}</h2>{!! $more('recent') !!}</div>
        <div class="card feed">@forelse($recent as $post) @include('partials.post-card', ['post' => $post]) @empty <div class="empty"><p>{{ __('Rien pour le moment.') }}</p></div> @endforelse</div>
    @endisset
@endsection
