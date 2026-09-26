@extends('layouts.app')
@section('title', __('Observatoire citoyen'))
@php
    $srcLabels = ['verified' => __('Vérifiées'), 'provided' => __('Fournies par les candidats'), 'unverified' => __('Non vérifiées'), 'disputed' => __('Contestées')];
    $cLabels = ['not_started' => __('Non commencés'), 'in_progress' => __('En cours'), 'partially' => __('Partiellement tenus'), 'fulfilled' => __('Tenus'), 'not_fulfilled' => __('Non tenus')];
@endphp
@section('content')
    <div class="page-head"><div><h1>{{ __('Observatoire citoyen') }}</h1><div class="small faint">{{ __('Indicateurs publics de redevabilité') }}</div></div></div>
    <div class="grid grid-3 mb">
        <div class="stat"><div class="v">{{ $questions }}</div><div class="l">{{ __('Questions posées aux candidats') }}</div></div>
        <div class="stat"><div class="v">{{ $answered }}</div><div class="l">{{ __('Questions répondues') }}</div><div class="d faint">{{ $questions ? round($answered * 100 / $questions) : 0 }} %</div></div>
        <div class="stat"><div class="v">{{ $open }}</div><div class="l">{{ __('Questions en attente') }}</div></div>
    </div>
    <div class="grid grid-2">
        <div class="card"><div class="card-head"><h3>{{ __('Sources') }}</h3></div><div class="card-body">
            @foreach($srcLabels as $k => $l)<div class="row between small"><span>{{ $l }}</span><b>{{ $sources[$k] ?? 0 }}</b></div><div class="progress mb" style="margin:.3rem 0 .6rem"><span style="width:{{ $sources->sum() ? ($sources[$k] ?? 0) * 100 / $sources->sum() : 0 }}%"></span></div>@endforeach
        </div></div>
        <div class="card"><div class="card-head"><h3>{{ __('Engagements des élus') }}</h3></div><div class="card-body">
            @foreach($cLabels as $k => $l)<div class="row between small"><span>{{ $l }}</span><b>{{ $commitments[$k] ?? 0 }}</b></div><div class="progress" style="margin:.3rem 0 .6rem"><span style="width:{{ $commitments->sum() ? ($commitments[$k] ?? 0) * 100 / $commitments->sum() : 0 }}%"></span></div>@endforeach
        </div></div>
    </div>
    <div class="card"><div class="card-head"><h3>{{ __('Thèmes les plus questionnés') }}</h3></div><div class="card-body pills">
        @forelse($topCategories as $c)<a href="{{ route('questions.index', ['category' => $c->slug]) }}" class="pill">{{ $c->{'name_'.app()->getLocale()} ?? $c->name_fr }} <span class="faint">{{ $c->c }}</span></a>@empty<span class="muted small">—</span>@endforelse
    </div></div>
    <div class="card"><div class="card-head"><h3>{{ __('Réponses des candidats aux citoyens') }}</h3><span class="small faint">{{ __('Ordre alphabétique, sans classement') }}</span></div>
        <div class="table-wrap"><table class="table"><thead><tr><th>{{ __('Candidat') }}</th><th>{{ __('Questions reçues') }}</th><th>{{ __('Répondues') }}</th><th>{{ __('Taux') }}</th></tr></thead><tbody>
            @forelse($responsiveness as $r)
                <tr><td><a href="{{ route('candidates.show', $r['username']) }}">{{ $r['name'] }}</a></td><td>{{ $r['received'] }}</td><td>{{ $r['answered'] }}</td><td>{{ $r['rate'] !== null ? $r['rate'].' %' : '—' }}</td></tr>
            @empty<tr><td colspan="4" class="muted">—</td></tr>@endforelse
        </tbody></table></div></div>
    <a href="{{ route('civic.index') }}" class="btn btn-outline"><x-icon name="database"/>{{ __('Données civiques publiques') }}</a>
    <a href="{{ route('developers') }}" class="btn btn-ghost">API</a>
@endsection
