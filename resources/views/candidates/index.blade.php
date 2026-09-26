@extends('layouts.app')
@section('title', __('Candidats'))
@section('layout', 'wide')
@section('content')
    <div class="page-head"><h1>{{ __('Candidats') }}</h1>
        <div class="actions">
            <a href="{{ route('compare.index') }}" class="btn btn-outline btn-sm"><x-icon name="compare"/>{{ __('Comparer') }}</a>
            @auth @unless(auth()->user()->isCandidate())<a href="{{ route('candidates.register') }}" class="btn btn-primary btn-sm">{{ __('Je suis candidat') }}</a>@endunless @endauth
        </div>
    </div>
    <div class="alert alert-info"><x-icon name="info"/><div class="small">{{ __('Les candidats sont présentés par ordre alphabétique. Vwajèn ne classe pas les candidats et n\'attribue aucun score politique.') }}</div></div>

    <form method="GET" class="card"><div class="card-body filters mb-0" style="margin:0">
        <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Nom, parti…') }}" aria-label="{{ __('Rechercher un candidat') }}">
        <select name="position_sought" class="select" aria-label="{{ __('Poste recherché') }}">
            <option value="">{{ __('Tous les postes') }}</option>
            @foreach(config('vwajen.positions') as $k => $p)<option value="{{ $k }}" @selected(request('position_sought') === $k)>{{ __($p) }}</option>@endforeach
        </select>
        <select name="department" class="select" aria-label="{{ __('Département') }}">
            <option value="">{{ __('Tous les départements') }}</option>
            @foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}" @selected(request('department') === $k)>{{ $d['name'] }}</option>@endforeach
        </select>
        <select name="party" class="select" aria-label="{{ __('Affiliation') }}">
            <option value="">{{ __('Toutes affiliations') }}</option>
            @foreach($parties as $p)<option value="{{ $p }}" @selected(request('party') === $p)>{{ $p }}</option>@endforeach
        </select>
        <label class="check mb-0"><input type="checkbox" name="verified" value="1" @checked(request('verified'))>{{ __('Vérifiés uniquement') }}</label>
        <button class="btn btn-primary">{{ __('Filtrer') }}</button>
    </div></form>

    <div class="grid grid-auto">
        @forelse($candidates as $u)
            @include('partials.candidate-card', ['u' => $u])
        @empty
            <div class="card empty" style="grid-column:1/-1"><x-icon name="vote"/><p>{{ __('Aucun candidat ne correspond à ces critères.') }}</p></div>
        @endforelse
    </div>
    {{ $candidates->links() }}
@endsection
