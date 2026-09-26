@extends('layouts.app')
@section('title', $q ? __('Recherche : :q', ['q' => $q]) : __('Recherche'))
@php
    $types = ['all' => __('Tout'), 'users' => __('Utilisateurs'), 'candidates' => __('Candidats'), 'posts' => __('Publications'), 'videos' => __('Vidéos'), 'shorts' => 'Shorts',
        'lives' => __('Lives'), 'events' => __('Événements'), 'debates' => __('Débats'), 'questions' => __('Questions'), 'hashtags' => __('Hashtags'), 'communities' => __('Communautés')];
    $link = fn ($t) => route('search.index', array_merge(request()->except('page', 'type'), ['type' => $t]));
@endphp
@section('content')
    <div class="page-head" style="flex-direction:column;align-items:stretch">
        <form action="{{ route('search.index') }}" method="GET" class="search-box" role="search" data-suggest>
            <x-icon name="search"/><label for="search-q" class="sr-only">{{ __('Rechercher') }}</label>
            <input id="search-q" type="search" name="q" value="{{ $q }}" class="input" placeholder="{{ __('Nom, @username, #hashtag, mot-clé…') }}" autocomplete="off" autofocus>
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="suggest" hidden></div>
        </form>
        <nav class="chip-row mt-sm" aria-label="{{ __('Type de résultats') }}">
            @foreach($types as $k => $l)<a href="{{ $link($k) }}" class="pill {{ $type === $k ? 'active' : '' }}">{{ $l }}</a>@endforeach
        </nav>
    </div>

    @if($q && $type !== 'all')
        <details class="card" @if(array_filter($filters)) open @endif><summary class="card-body" style="cursor:pointer"><x-icon name="filter" style="vertical-align:-4px"/> {{ __('Filtres de recherche') }}</summary>
            <form method="GET" class="card-body filters" style="padding-top:0">
                <input type="hidden" name="q" value="{{ $q }}"><input type="hidden" name="type" value="{{ $type }}">
                @if(in_array($type, ['posts', 'videos', 'shorts', 'events', 'debates', 'questions', 'lives']))
                    <select name="period" class="select" aria-label="{{ __('Période') }}"><option value="">{{ __('Toutes dates') }}</option>
                        @foreach(['day' => __('24 heures'), 'week' => __('7 jours'), 'month' => __('30 jours'), 'year' => __('1 an')] as $k => $l)<option value="{{ $k }}" @selected(($filters['period'] ?? '') === $k)>{{ $l }}</option>@endforeach</select>
                    <select name="sort" class="select" aria-label="{{ __('Tri') }}"><option value="">{{ __('Pertinence') }}</option><option value="recent" @selected(($filters['sort'] ?? '') === 'recent')>{{ __('Plus récents') }}</option><option value="popular" @selected(($filters['sort'] ?? '') === 'popular')>{{ __('Plus populaires') }}</option></select>
                @endif
                @if($type === 'users')
                    <select name="account_type" class="select" aria-label="{{ __('Type de compte') }}"><option value="">{{ __('Tous les comptes') }}</option>
                        @foreach(['personal' => __('Citoyens'), 'candidate' => __('Candidats'), 'organization' => __('Organisations'), 'official' => __('Élus')] as $k => $l)<option value="{{ $k }}" @selected(($filters['account_type'] ?? '') === $k)>{{ $l }}</option>@endforeach</select>
                    <select name="country" class="select" aria-label="{{ __('Pays') }}"><option value="">{{ __('Tous les pays') }}</option>@foreach(all_countries() as $c => $n)<option value="{{ $c }}" @selected(($filters['country'] ?? '') === $c)>{{ $n }}</option>@endforeach</select>
                @endif
                @if(in_array($type, ['users', 'candidates']))<label class="check mb-0"><input type="checkbox" name="verified" value="1" @checked($filters['verified'] ?? false)>{{ __('Vérifiés') }}</label>@endif
                @if(in_array($type, ['candidates', 'events']))
                    <select name="department" class="select" aria-label="{{ __('Département') }}"><option value="">{{ __('Tous les départements') }}</option>@foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}" @selected(($filters['department'] ?? '') === $k)>{{ $d['name'] }}</option>@endforeach</select>
                @endif
                @if($type === 'candidates')
                    <select name="position" class="select" aria-label="{{ __('Poste') }}"><option value="">{{ __('Tous les postes') }}</option>@foreach(config('vwajen.positions') as $k => $p)<option value="{{ $k }}" @selected(($filters['position'] ?? '') === $k)>{{ __($p) }}</option>@endforeach</select>
                @endif
                @if($type === 'posts')
                    <input name="from" class="input" placeholder="{{ __('De : @username') }}" value="{{ $filters['from'] ?? '' }}" aria-label="{{ __('Auteur') }}">
                    <label class="check mb-0"><input type="checkbox" name="media" value="1" @checked($filters['media'] ?? false)>{{ __('Avec médias') }}</label>
                @endif
                @if($type === 'questions')
                    <select name="status" class="select" aria-label="{{ __('Statut') }}"><option value="">{{ __('Tous statuts') }}</option>@foreach(['open' => __('Ouvertes'), 'answered' => __('Répondues'), 'closed' => __('Fermées')] as $k => $l)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>@endforeach</select>
                @endif
                @if($type === 'events')
                    <select name="when" class="select" aria-label="{{ __('Quand') }}"><option value="">{{ __('Tous') }}</option><option value="upcoming" @selected(($filters['when'] ?? '') === 'upcoming')>{{ __('À venir') }}</option><option value="past" @selected(($filters['when'] ?? '') === 'past')>{{ __('Passés') }}</option></select>
                @endif
                <button class="btn btn-primary">{{ __('Appliquer') }}</button>
            </form>
        </details>
    @endif

    @if($aiEnabled && $q)
        <div class="row small mb">
            <a href="{{ route('search.index', array_merge(request()->query(), ['smart' => $smart ? 0 : 1])) }}" class="btn btn-sm {{ $smart ? 'btn-soft' : 'btn-outline' }}"><x-icon name="sparkles"/>{{ __('Recherche intelligente') }}</a>
            @if($smart && count($terms) > 1)<span class="faint">{{ __('Termes inclus :') }} {{ implode(', ', array_slice($terms, 1)) }}</span>@endif
        </div>
    @endif

    @if(! $q)
        <div class="card empty"><x-icon name="search"/><h3>{{ __('Recherche globale') }}</h3><p>{{ __('Trouvez des utilisateurs, candidats, publications, vidéos, Shorts, événements, débats, questions et hashtags.') }}</p></div>
    @elseif($type === 'all')
        @php $any = collect($results)->contains(fn ($r) => $r && $r->count()); @endphp
        @unless($any)<div class="card empty"><x-icon name="search"/><p>{{ __('Aucun résultat pour « :q ».', ['q' => $q]) }}</p></div>@endunless
        @foreach($results as $t => $res)
            @continue(! $res || ! $res->count())
            <div class="section-title"><h2>{{ $types[$t] }}</h2><a href="{{ $link($t) }}" class="small">{{ __('Voir tout') }}</a></div>
            @include('search.results', ['t' => $t, 'res' => $res])
        @endforeach
    @else
        @if($results && $results->count())
            @include('search.results', ['t' => $type, 'res' => $results])
            {{ $results->links() }}
        @else
            <div class="card empty"><x-icon name="search"/><p>{{ __('Aucun résultat pour « :q ».', ['q' => $q]) }}</p></div>
        @endif
    @endif
@endsection
