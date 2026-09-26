@extends('layouts.app')
@section('title', __('Comparaison des programmes'))
@section('layout', 'wide')
@section('content')
    <div class="page-head"><h1>{{ __('Comparaison informative') }}</h1></div>
    <div class="alert alert-info"><x-icon name="scale"/><div class="small">{{ __('Cette comparaison présente les programmes côte à côte, tels que publiés par les candidats, avec leurs sources. Elle n\'établit aucun classement et n\'attribue aucun score politique. Les candidats sont affichés par ordre alphabétique.') }}</div></div>

    <form method="GET" class="card"><div class="card-body">
        <div class="field">
            <label class="label">{{ __('Sélectionnez 2 à 4 candidats') }}</label>
            <div class="pills" style="max-height:180px;overflow-y:auto">
                @foreach($allCandidates as $c)
                    <label><input type="checkbox" name="c[]" value="{{ $c->username }}" class="pill-check" @checked(in_array($c->username, $usernames))><span class="pill">{{ $c->candidateProfile->full_name }}</span></label>
                @endforeach
            </div>
        </div>
        <div class="field">
            <label class="label">{{ __('Filtrer par thème') }}</label>
            <div class="pills">
                @foreach($categories as $cat)
                    <label><input type="checkbox" name="t[]" value="{{ $cat->slug }}" class="pill-check" @checked(in_array($cat->slug, $categorySlugs))><span class="pill">{{ $cat->name }}</span></label>
                @endforeach
            </div>
        </div>
        <button class="btn btn-primary"><x-icon name="compare"/>{{ __('Comparer') }}</button>
        @if($usernames)<a href="{{ route('compare.index') }}" class="btn btn-ghost">{{ __('Réinitialiser') }}</a>@endif
    </div></form>

    @if($candidates->count() >= 1)
        <div class="compare-wrap">
            <table class="compare">
                <thead>
                    <tr>
                        <th class="theme-cell">{{ __('Thème') }}</th>
                        @foreach($candidates as $c)
                            @php $p = $c->candidateProfile; $prog = $c->programs->firstWhere('status', 'published') ?? $c->programs->first(); @endphp
                            <th scope="col">
                                <div class="row"><img src="{{ $p->photoUrl() }}" alt="" class="avatar">
                                    <div><a href="{{ route('candidates.show', $c->username) }}" class="name">{{ $p->full_name }}</a>@include('partials.verified', ['u' => $c])
                                        <div class="small muted">{{ position_name($p->position_sought) }}</div>
                                        @if($c->show_political && $p->party)<div class="small faint">{{ $p->party }}</div>@endif</div></div>
                                @if($prog)<div class="small mt-sm"><a href="{{ $prog->url() }}">{{ $prog->title }}</a> · v{{ $prog->currentVersion?->version_number }}</div>@endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th class="theme-cell">{{ __('Informations manquantes') }}</th>
                        @foreach($candidates as $c)
                            <td>@forelse($missing[$c->id] as $m)<div class="missing mb" style="margin-bottom:.3rem"><x-icon name="alert"/>{{ $m }}</div>@empty<span class="badge badge-success">{{ __('Profil complet') }}</span>@endforelse</td>
                        @endforeach
                    </tr>
                    @foreach($shownCategories as $cat)
                        <tr>
                            <th class="theme-cell" scope="row"><span class="category-chip" style="--c:{{ $cat->color }}">{{ $cat->name }}</span></th>
                            @foreach($candidates as $c)
                                <td>
                                    @forelse($matrix[$cat->id][$c->id] ?? [] as $prop)
                                        <div class="mb" style="margin-bottom:.8rem">
                                            <strong>{{ $prop->title }}</strong>
                                            <div class="small muted clamp-3">{{ $prop->description }}</div>
                                            @if($prop->timeline || $prop->budget)<div class="small faint">{{ collect([$prop->timeline, $prop->budget])->filter()->implode(' · ') }}</div>@endif
                                            @foreach($prop->sources as $s)
                                                <div class="small"><x-icon name="link" style="width:12px;height:12px;vertical-align:-1px"/>
                                                    @if($s->url)<a href="{{ $s->url }}" target="_blank" rel="noopener nofollow">{{ $s->name }}</a>@else{{ $s->name }}@endif
                                                    <span class="badge badge-{{ $s->statusColor() }}" style="font-size:.65rem">{{ $s->statusLabel() }}</span></div>
                                            @endforeach
                                            @if($prop->sources->isEmpty())<div class="small faint">{{ __('Sans source') }}</div>@endif
                                        </div>
                                    @empty
                                        <div class="missing"><x-icon name="alert"/>{{ __('Aucune proposition sur ce thème') }}</div>
                                    @endforelse
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card empty"><x-icon name="compare"/><h3>{{ __('Choisissez des candidats') }}</h3><p>{{ __('Sélectionnez au moins deux candidats ci-dessus pour comparer leurs propositions par thème.') }}</p></div>
    @endif
@endsection
