@extends('layouts.app')
@section('title', __('Responsables élus'))
@section('content')
    <div class="page-head"><div><h1>{{ __('Suivi des responsables publics') }}</h1><div class="small faint">{{ __('Activités, déclarations, documents et engagements documentés des élus') }}</div></div></div>
    <form method="GET" class="filters">
        <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Nom, fonction, institution') }}" aria-label="{{ __('Rechercher') }}">
        <select name="department" class="select" aria-label="{{ __('Département') }}"><option value="">{{ __('Tous les départements') }}</option>@foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}" @selected(request('department') === $k)>{{ $d['name'] }}</option>@endforeach</select>
        <button class="btn btn-outline">{{ __('Filtrer') }}</button>
    </form>
    <div class="card">
        @forelse($officials as $u)
            <a href="{{ route('officials.show', $u->username) }}" class="item" style="color:var(--text)">
                <img src="{{ $u->avatarUrl() }}" alt="" class="avatar avatar-lg">
                <div class="item-body"><strong>{{ $u->officialProfile->full_name }}</strong>@include('partials.verified', ['u' => $u])
                    <div class="small muted">{{ $u->officialProfile->office }}@if($u->officialProfile->institution) · {{ $u->officialProfile->institution }}@endif</div>
                    <div class="small faint">{{ $u->officialProfile->constituency }} @if($u->officialProfile->mandate_end)· {{ __('mandat jusqu\'en :y', ['y' => $u->officialProfile->mandate_end->year]) }}@endif</div></div>
                <x-icon name="chevron-right"/>
            </a>
        @empty <div class="empty"><x-icon name="landmark"/><p>{{ __('Aucun responsable élu référencé.') }}</p></div> @endforelse
    </div>
    {{ $officials->links() }}
@endsection
