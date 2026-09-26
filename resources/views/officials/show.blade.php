@extends('layouts.app')
@section('title', $user->officialProfile->full_name)
@php
    $o = $user->officialProfile;
    $statusLabels = ['not_started' => __('Non commencé'), 'in_progress' => __('En cours'), 'partially' => __('Partiellement tenu'), 'fulfilled' => __('Tenu'), 'not_fulfilled' => __('Non tenu')];
    $statusColors = ['not_started' => '', 'in_progress' => 'badge-info', 'partially' => 'badge-warning', 'fulfilled' => 'badge-success', 'not_fulfilled' => 'badge-danger'];
    $tabs = ['overview' => __('Profil'), 'posts' => __('Publications'), 'activities' => __('Activités publiques'), 'declarations' => __('Déclarations'), 'documents' => __('Documents publics'),
        'events' => __('Événements'), 'questions' => __('Questions citoyennes'), 'commitments' => __('Engagements'), 'history' => __('Historique public')];
@endphp
@section('content')
    <div class="page-head"><a href="{{ route('officials.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ $o->full_name }}</h1></div>
    <div class="card" style="overflow:hidden">
        <div class="cover" style="height:130px"></div>
        <div class="profile-head">
            <img src="{{ $user->avatarUrl() }}" alt="" class="avatar avatar-xl">
            <div class="profile-actions">
                <a href="{{ route('questions.create', ['to' => $user->username]) }}" class="btn btn-soft"><x-icon name="question"/>{{ __('Poser une question') }}</a>
                @include('partials.follow-button', ['u' => $user, 'size' => ''])
            </div>
            <div class="profile-name">{{ $o->full_name }}@include('partials.verified', ['u' => $user])</div>
            <div class="muted">{{ $o->office }}@if($o->institution) — {{ $o->institution }}@endif</div>
            <div class="profile-stats">
                @foreach($statusLabels as $k => $l)@if($commitmentStats[$k] ?? 0)<span><b>{{ $commitmentStats[$k] }}</b> {{ mb_strtolower($l) }}</span>@endif @endforeach
            </div>
        </div>
        <nav class="tabs">@foreach($tabs as $k => $l)<a href="{{ route('officials.show', [$user->username, 'tab' => $k]) }}" class="tab {{ $tab === $k ? 'active' : '' }}">{{ $l }}</a>@endforeach</nav>
    </div>

    @switch($tab)
        @case('overview')
            <div class="card"><div class="card-body"><dl class="kv">
                <dt>{{ __('Fonction') }}</dt><dd>{{ $o->office }}</dd>
                <dt>{{ __('Institution') }}</dt><dd>{{ $o->institution ?: '—' }}</dd>
                <dt>{{ __('Circonscription') }}</dt><dd>{{ $o->constituency ?: '—' }}</dd>
                <dt>{{ __('Département') }}</dt><dd>{{ department_name($o->department) ?: '—' }}</dd>
                @if($user->show_political)<dt>{{ __('Affiliation') }}</dt><dd>{{ $o->party ?: '—' }}</dd>@endif
                <dt>{{ __('Mandat') }}</dt><dd>{{ $o->mandate_start?->translatedFormat('d M Y') ?? '?' }} → {{ $o->mandate_end?->translatedFormat('d M Y') ?? '?' }}</dd>
            </dl>@if($o->biography)<div class="prose mt">{!! nl2br(e($o->biography)) !!}</div>@endif</div></div>
            <div class="card"><div class="card-head"><h3>{{ __('Sources') }}</h3></div><div class="card-body">
                @include('partials.sources', ['sources' => $o->sources, 'addType' => 'official', 'addId' => $o->id, 'canAdd' => $canDocument])
            </div></div>
            @break
        @case('posts')
            <div class="card feed">@forelse($items as $post) @include('partials.post-card', ['post' => $post]) @empty <div class="empty"><p>{{ __('Aucune publication.') }}</p></div> @endforelse</div>{{ $items->links() }}
            @break
        @case('activities') @case('declarations') @case('documents')
            @if($canDocument)
                <details class="card"><summary class="card-body" style="cursor:pointer"><strong>+ {{ __('Ajouter à l\'historique public') }}</strong></summary>
                    <form method="POST" action="{{ route('officials.records.store', $user) }}" enctype="multipart/form-data" class="card-body" style="padding-top:0">@csrf
                        <div class="grid grid-2">
                            <select name="type" class="select" aria-label="{{ __('Type') }}">@foreach(['activity' => __('Activité publique'), 'declaration' => __('Déclaration'), 'document' => __('Document public'), 'vote' => __('Vote'), 'appointment' => __('Nomination')] as $k => $l)<option value="{{ $k }}" @selected(($tab === 'declarations' && $k === 'declaration') || ($tab === 'documents' && $k === 'document'))>{{ $l }}</option>@endforeach</select>
                            <input type="date" name="occurred_on" class="input" aria-label="{{ __('Date') }}">
                        </div>
                        <input name="title" class="input mt-sm" placeholder="{{ __('Titre') }}" required aria-label="{{ __('Titre') }}">
                        <textarea name="body" class="textarea mt-sm" placeholder="{{ __('Détails') }}" aria-label="{{ __('Détails') }}"></textarea>
                        <div class="grid grid-3 mt-sm"><input name="location" class="input" placeholder="{{ __('Lieu') }}" aria-label="{{ __('Lieu') }}"><input name="source_name" class="input" placeholder="{{ __('Source') }}" aria-label="{{ __('Source') }}"><input name="source_url" type="url" class="input" placeholder="https://" aria-label="{{ __('Lien source') }}"></div>
                        <input type="file" name="file" class="input mt-sm" aria-label="{{ __('Document') }}">
                        <button class="btn btn-primary mt-sm">{{ __('Ajouter') }}</button></form></details>
            @endif
            <div class="card"><div class="card-body"><div class="timeline">
                @forelse($items as $r)
                    <div class="t-item">
                        <div class="small faint">{{ $r->occurred_on?->translatedFormat('d M Y') ?? $r->created_at->translatedFormat('d M Y') }} @if($r->location)· {{ $r->location }}@endif · <span class="badge">{{ __(ucfirst($r->type)) }}</span></div>
                        <strong>{{ $r->title }}</strong>
                        @if($r->body)<div class="small">{!! nl2br(e($r->body)) !!}</div>@endif
                        @foreach($r->documents ?? [] as $doc)<a href="{{ $doc->url() }}" target="_blank" class="small"><x-icon name="file" style="width:13px;height:13px"/> {{ $doc->title }}</a>@endforeach
                        @include('partials.sources', ['sources' => $r->sources])
                    </div>
                @empty<p class="muted">{{ __('Rien de documenté pour le moment.') }}</p>@endforelse
            </div></div></div>{{ $items->links() }}
            @break
        @case('events')
            <div class="card">@forelse($items as $e) @include('partials.event-row', ['e' => $e]) @empty <div class="empty"><p>{{ __('Aucun événement.') }}</p></div> @endforelse</div>{{ $items->links() }}
            @break
        @case('questions')
            <div class="card"><div class="card-body"><a href="{{ route('questions.create', ['to' => $user->username]) }}" class="btn btn-primary btn-sm">{{ __('Poser une question') }}</a></div>
                @forelse($items as $q) @include('partials.question-row', ['q' => $q]) @empty <div class="empty"><p>{{ __('Aucune question.') }}</p></div> @endforelse</div>{{ $items->links() }}
            @break
        @case('commitments')
            <div class="alert alert-info"><x-icon name="info"/><div class="small">{{ __('Suivi des engagements documentés : chaque engagement est lié à une source publique et chaque changement de statut est historisé.') }}</div></div>
            @if($canDocument)
                <details class="card"><summary class="card-body" style="cursor:pointer"><strong>+ {{ __('Documenter un engagement') }}</strong></summary>
                    <form method="POST" action="{{ route('officials.commitments.store', $user) }}" class="card-body" style="padding-top:0">@csrf
                        <input name="title" class="input" placeholder="{{ __('Engagement') }}" required aria-label="{{ __('Engagement') }}">
                        <textarea name="description" class="textarea mt-sm" placeholder="{{ __('Description') }}" aria-label="{{ __('Description') }}"></textarea>
                        <div class="grid grid-3 mt-sm">
                            <select name="category_id" class="select" aria-label="{{ __('Thème') }}"><option value="">{{ __('Thème') }}</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                            <input type="date" name="made_on" class="input" aria-label="{{ __('Date de l\'engagement') }}"><input type="date" name="due_on" class="input" aria-label="{{ __('Échéance') }}">
                        </div>
                        <div class="grid grid-2 mt-sm"><input name="source_name" class="input" placeholder="{{ __('Source (obligatoire)') }}" required aria-label="{{ __('Source') }}"><input name="source_url" type="url" class="input" placeholder="https://" aria-label="{{ __('Lien source') }}"></div>
                        <button class="btn btn-primary mt-sm">{{ __('Ajouter') }}</button></form></details>
            @endif
            @forelse($items as $c)
                <div class="card"><div class="card-body">
                    <div class="row between wrap"><strong>{{ $c->title }}</strong><span class="badge {{ $statusColors[$c->status] }}">{{ $statusLabels[$c->status] }}</span></div>
                    <div class="small faint">@if($c->category){{ $c->category->name }} · @endif{{ __('Engagé le :d', ['d' => $c->made_on?->translatedFormat('d M Y') ?? '?']) }}@if($c->due_on) · {{ __('échéance :d', ['d' => $c->due_on->translatedFormat('d M Y')]) }}@endif</div>
                    @if($c->description)<p class="small mt-sm">{{ $c->description }}</p>@endif
                    @include('partials.sources', ['sources' => $c->sources])
                    <details class="mt-sm"><summary class="small" style="cursor:pointer">{{ __('Historique du suivi') }} ({{ $c->updates->count() }})</summary>
                        <div class="timeline mt-sm">@foreach($c->updates->sortByDesc('created_at') as $u)<div class="t-item"><div class="small faint">{{ $u->created_at->translatedFormat('d M Y') }} · {{ $u->user?->name }}</div><span class="badge {{ $statusColors[$u->status] ?? '' }}">{{ $statusLabels[$u->status] ?? $u->status }}</span> <span class="small">{{ $u->note }}</span></div>@endforeach</div></details>
                    @if($canDocument)
                        <form method="POST" action="{{ route('officials.commitments.update', $c) }}" class="row wrap mt-sm">@csrf
                            <select name="status" class="select input-sm" style="width:auto" aria-label="{{ __('Statut') }}">@foreach($statusLabels as $k => $l)<option value="{{ $k }}" @selected($c->status === $k)>{{ $l }}</option>@endforeach</select>
                            <input name="note" class="input input-sm grow" placeholder="{{ __('Note de suivi (obligatoire)') }}" required aria-label="{{ __('Note') }}">
                            <input name="source_url" type="url" class="input input-sm" placeholder="{{ __('Lien source') }}" aria-label="{{ __('Lien source') }}"><input type="hidden" name="source_name" value="{{ __('Mise à jour') }}">
                            <button class="btn btn-soft btn-sm">{{ __('Mettre à jour') }}</button></form>
                    @endif
                </div></div>
            @empty<div class="card empty"><p>{{ __('Aucun engagement documenté.') }}</p></div>@endforelse
            {{ $items->links() }}
            @break
        @case('history')
            <div class="card"><div class="card-body"><div class="timeline">
                @forelse($items as $log)<div class="t-item"><div class="small faint">{{ $log->created_at->translatedFormat('d M Y H:i') }} · {{ $log->user?->name }}</div><div><strong>{{ __(ucfirst($log->field)) }}</strong> : {{ $log->old_value }} → {{ $log->new_value }}</div>@if($log->note)<div class="small muted">{{ $log->note }}</div>@endif</div>
                @empty<p class="muted">{{ __('Aucun historique.') }}</p>@endforelse
            </div></div></div>{{ $items->links() }}
            @break
    @endswitch
@endsection
