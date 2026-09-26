@extends('layouts.app')
@section('title', $profile->full_name.' — '.__('Candidat'))
@section('og_image', $profile->photoUrl())
@section('og_description', \Illuminate\Support\Str::limit($profile->biography, 180))
@php
    $viewer = auth()->user();
    $tabs = ['about' => __('Profil'), 'program' => __('Programme'), 'posts' => __('Publications'), 'videos' => __('Vidéos'), 'lives' => __('Lives'),
        'events' => __('Événements'), 'debates' => __('Débats'), 'questions' => __('Questions'), 'sources' => __('Sources'), 'history' => __('Historique')];
@endphp
@section('content')
    <div class="page-head"><a href="{{ route('candidates.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ $profile->full_name }}</h1></div>

    <div class="card" style="overflow:hidden">
        <div class="cover" @if($user->coverUrl()) style="background-image:url('{{ $user->coverUrl() }}')" @endif></div>
        <div class="profile-head">
            <img src="{{ $profile->photoUrl() }}" alt="{{ $profile->full_name }}" class="avatar avatar-xl" data-lightbox>
            <div class="profile-actions">
                <button type="button" class="btn btn-outline btn-icon" data-share data-share-url="{{ route('candidates.show', $user->username) }}" data-share-title="{{ $profile->full_name }}" data-share-type="user" data-share-id="{{ $user->id }}" aria-label="{{ __('Partager') }}"><x-icon name="share"/></button>
                <a href="{{ route('questions.create', ['to' => $user->username]) }}" class="btn btn-soft"><x-icon name="question"/>{{ __('Poser une question') }}</a>
                @include('partials.follow-button', ['u' => $user, 'size' => ''])
            </div>
            <div class="profile-name">{{ $profile->full_name }}@include('partials.verified', ['u' => $user])</div>
            <div class="handle"><a href="{{ $user->profileUrl() }}" class="handle">{{ '@'.$user->username }}</a></div>
            <div class="row wrap mt-sm" style="gap:.35rem">
                @if($user->is_verified)<span class="badge badge-gold"><x-icon name="badge-check" style="width:13px;height:13px"/>{{ __('Candidat vérifié') }}</span>
                @else<span class="badge badge-warning">{{ __('Vérification en cours') }}</span>@endif
                <span class="badge badge-primary">{{ position_name($profile->position_sought) }}</span>
                @if($profile->election_year)<span class="badge">{{ __('Élections :y', ['y' => $profile->election_year]) }}</span>@endif
            </div>
            <div class="profile-stats">
                <span><b data-followers-count>{{ short_number($user->followers_count) }}</b> {{ __('abonnés') }}</span>
                <span><b>{{ $stats['proposals'] }}</b> {{ __('propositions') }}</span>
                <span><b>{{ $stats['answered'] }}/{{ $stats['questions'] }}</b> {{ __('questions répondues') }}</span>
            </div>
        </div>
        <nav class="tabs">
            @foreach($tabs as $k => $label)<a href="{{ route('candidates.show', [$user->username, 'tab' => $k]) }}" class="tab {{ $tab === $k ? 'active' : '' }}">{{ $label }}</a>@endforeach
        </nav>
    </div>

    @switch($tab)
        @case('about')
            <div class="card"><div class="card-body">
                <dl class="kv">
                    <dt>{{ __('Nom') }}</dt><dd>{{ $profile->full_name }}</dd>
                    <dt>{{ __('Poste recherché') }}</dt><dd>{{ position_name($profile->position_sought) }}</dd>
                    <dt>{{ __('Circonscription') }}</dt><dd>{{ $profile->constituency ?: '—' }}</dd>
                    <dt>{{ __('Département') }}</dt><dd>{{ department_name($profile->department) ?: '—' }}</dd>
                    <dt>{{ __('Affiliation politique déclarée') }}</dt><dd>{{ $user->show_political ? ($profile->party ?: __('Non déclarée')) : __('Non publique') }}</dd>
                    @if($profile->website)<dt>{{ __('Site web') }}</dt><dd><a href="{{ $profile->website }}" target="_blank" rel="noopener nofollow">{{ $profile->website }}</a></dd>@endif
                </dl>
            </div></div>
            <div class="card"><div class="card-head"><h2>{{ __('Biographie') }}</h2></div><div class="card-body prose">{!! nl2br(e($profile->biography)) !!}</div></div>
            <div class="card"><div class="card-head"><h2>{{ __('Parcours') }}</h2></div><div class="card-body prose">
                @if($profile->career){!! nl2br(e($profile->career)) !!}@else<div class="missing"><x-icon name="alert"/>{{ __('Information non fournie par le candidat.') }}</div>@endif
            </div></div>
            <div class="card"><div class="card-head"><h2>{{ __('Sources publiques') }}</h2></div><div class="card-body">
                @include('partials.sources', ['sources' => $profile->sources, 'addType' => 'candidate', 'addId' => $profile->id, 'canAdd' => $viewer && ($viewer->id === $user->id || $viewer->hasPermission('sources.verify'))])
                @if($profile->sources->isEmpty())<p class="muted small">{{ __('Aucune source publiée.') }}</p>@endif
            </div></div>
            @if($program)
                <a href="{{ route('candidates.show', [$user->username, 'tab' => 'program']) }}" class="card row" style="padding:1rem;color:var(--text)"><x-icon name="program"/>
                    <div class="grow"><strong>{{ $program->title }}</strong><div class="small muted">{{ __('Version :v', ['v' => $program->currentVersion?->version_number]) }} · {{ trans_choice(':n proposition|:n propositions', $stats['proposals'], ['n' => $stats['proposals']]) }}</div></div><x-icon name="chevron-right"/></a>
            @endif
            @break

        @case('program')
            @if($program)
                @include('programs.body', ['program' => $program, 'version' => $program->currentVersion, 'categories' => $categories])
            @else
                <div class="card empty"><x-icon name="program"/><h3>{{ __('Programme non publié') }}</h3><p>{{ __('Ce candidat n\'a pas encore publié de programme.') }}</p></div>
            @endif
            @break

        @case('posts')
            <div class="card feed">@forelse($data as $post) @include('partials.post-card', ['post' => $post]) @empty <div class="empty"><p>{{ __('Aucune publication.') }}</p></div> @endforelse</div>{{ $data->links() }}
            @break
        @case('videos')
            <div class="grid grid-2">@forelse($data as $v) @include('partials.video-tile', ['v' => $v, 'hideAuthor' => true]) @empty <div class="card empty" style="grid-column:1/-1"><p>{{ __('Aucune vidéo.') }}</p></div> @endforelse</div>{{ $data->links() }}
            @break
        @case('lives')
            <div class="grid grid-2">@forelse($data as $l) @include('partials.live-tile', ['l' => $l]) @empty <div class="card empty" style="grid-column:1/-1"><p>{{ __('Aucun live.') }}</p></div> @endforelse</div>{{ $data->links() }}
            @break
        @case('events')
            <div class="card">@forelse($data as $e) @include('partials.event-row', ['e' => $e]) @empty <div class="empty"><p>{{ __('Aucun événement.') }}</p></div> @endforelse</div>{{ $data->links() }}
            @break
        @case('debates')
            <div class="grid grid-2">@forelse($data as $d) @include('partials.debate-tile', ['d' => $d]) @empty <div class="card empty" style="grid-column:1/-1"><p>{{ __('Aucun débat.') }}</p></div> @endforelse</div>{{ $data->links() }}
            @break
        @case('questions')
            <div class="card"><div class="card-body row wrap">
                <div class="pills">
                    <a href="?tab=questions" class="pill {{ ! request('status') ? 'active' : '' }}">{{ __('Toutes') }}</a>
                    <a href="?tab=questions&status=open" class="pill {{ request('status') === 'open' ? 'active' : '' }}">{{ __('Ouvertes') }}</a>
                    <a href="?tab=questions&status=answered" class="pill {{ request('status') === 'answered' ? 'active' : '' }}">{{ __('Répondues') }}</a>
                    <a href="?tab=questions&status=closed" class="pill {{ request('status') === 'closed' ? 'active' : '' }}">{{ __('Fermées') }}</a>
                </div>
                <a href="{{ route('questions.create', ['to' => $user->username]) }}" class="btn btn-primary btn-sm right">{{ __('Poser une question') }}</a>
            </div>
                @forelse($data as $q) @include('partials.question-row', ['q' => $q]) @empty <div class="empty"><p>{{ __('Aucune question pour le moment.') }}</p></div> @endforelse
            </div>{{ $data->links() }}
            @break
        @case('sources')
            <div class="card"><div class="card-body">
                <p class="small muted">{{ __('Toutes les sources liées au profil, au programme et aux propositions du candidat.') }}</p>
                @include('partials.sources', ['sources' => $data])
                @if($data->isEmpty())<p class="muted">{{ __('Aucune source.') }}</p>@endif
            </div></div>{{ $data->links() }}
            @break
        @case('history')
            <div class="card"><div class="card-head"><h2>{{ __('Historique des modifications importantes') }}</h2></div><div class="card-body">
                <div class="timeline">
                    @forelse($data as $log)
                        <div class="t-item">
                            <div class="small faint">{{ $log->created_at->translatedFormat('d M Y, H:i') }} · {{ $log->user?->name ?? __('Système') }}</div>
                            <div><strong>{{ __(ucfirst(str_replace('_', ' ', $log->field))) }}</strong>@if($log->note) — {{ $log->note }}@endif</div>
                            @if($log->old_value !== null || $log->new_value !== null)
                                <div class="small"><span style="text-decoration:line-through;color:var(--danger)">{{ \Illuminate\Support\Str::limit($log->old_value, 160) ?: '∅' }}</span> → <span style="color:var(--success)">{{ \Illuminate\Support\Str::limit($log->new_value, 160) ?: '∅' }}</span></div>
                            @endif
                        </div>
                    @empty
                        <p class="muted">{{ __('Aucune modification enregistrée.') }}</p>
                    @endforelse
                </div>
            </div></div>{{ $data->links() }}
            @break
    @endswitch
@endsection
