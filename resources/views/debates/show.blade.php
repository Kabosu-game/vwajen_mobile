@extends('layouts.app')
@section('title', $debate->title)
@section('layout', 'wide')
@php
    $viewer = auth()->user();
    $live = $debate->live;
    $isHost = $live && $live->isHost($viewer);
    $canModerate = $live && $live->canModerate($viewer);
@endphp
@section('content')
    <div class="page-head"><a href="{{ route('debates.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a>
        <div class="grow" style="min-width:0"><h1 class="truncate">{{ $debate->title }}</h1><div class="small faint">{{ $debate->scheduled_at->translatedFormat('l d F Y · H:i') }} · {{ $debate->duration_minutes }} min</div></div>
        <div class="actions">
            @if($debate->status === 'scheduled')
                <button type="button" class="btn {{ $reminded ? 'btn-soft' : 'btn-outline' }} btn-sm" data-action="{{ route('debates.remind', $debate) }}" data-auth data-reload><x-icon name="bell"/>{{ $reminded ? __('Rappels activés') : __('Me notifier') }}</button>
            @endif
            <button type="button" class="btn btn-outline btn-sm" data-share data-share-url="{{ $debate->url() }}" data-share-title="{{ $debate->title }}" data-share-type="debate" data-share-id="{{ $debate->id }}"><x-icon name="share"/>{{ __('Partager') }}</button>
            @if($debate->canManage($viewer))
                <div class="dropdown"><button class="btn btn-outline btn-sm" data-dropdown><x-icon name="settings"/>{{ __('Gérer') }}</button>
                    <div class="menu">
                        <a href="{{ route('debates.edit', $debate) }}"><x-icon name="edit"/>{{ __('Modifier') }}</a>
                        <form method="POST" action="{{ route('debates.archive', $debate) }}">@csrf<button><x-icon name="archive"/>{{ $debate->archived_at ? __('Désarchiver') : __('Archiver') }}</button></form>
                        @if($debate->status === 'ended')<form method="POST" action="{{ route('debates.summary', $debate) }}">@csrf<button><x-icon name="sparkles"/>{{ __('Générer un résumé (IA)') }}</button></form>@endif
                        <form method="POST" action="{{ route('debates.destroy', $debate) }}" data-confirm="{{ __('Supprimer ce débat ?') }}">@csrf @method('DELETE')<button class="danger"><x-icon name="trash"/>{{ __('Supprimer') }}</button></form>
                    </div></div>
            @endif
        </div>
    </div>

    @if($myParticipation && $myParticipation->status === 'invited')
        <div class="alert alert-info"><x-icon name="debate"/><div class="grow">{{ __('Vous êtes invité à participer à ce débat.') }}</div>
            <form method="POST" action="{{ route('debates.respond', $debate) }}">@csrf<input type="hidden" name="answer" value="accept"><button class="btn btn-success btn-sm">{{ __('Accepter') }}</button></form>
            <form method="POST" action="{{ route('debates.respond', $debate) }}">@csrf<input type="hidden" name="answer" value="decline"><button class="btn btn-outline btn-sm">{{ __('Décliner') }}</button></form></div>
    @endif

    @if($live)
        @include('lives.stage', ['live' => $live, 'debate' => $debate, 'isHost' => $isHost, 'canModerate' => $canModerate])
    @endif

    <div class="split-layout" style="margin-top:1rem">
        <div>
            <div class="card" id="debate-{{ $debate->id }}" data-item="debate:{{ $debate->id }}"><div class="card-body">
                <div class="row wrap" style="gap:.35rem">
                    <span class="badge {{ $debate->status === 'live' ? 'badge-live' : '' }}">{{ ['scheduled' => __('Programmé'), 'live' => __('En direct'), 'ended' => __('Terminé'), 'cancelled' => __('Annulé')][$debate->status] ?? $debate->status }}</span>
                    @if($debate->category)<span class="category-chip" style="--c:{{ $debate->category->color }}">{{ $debate->category->name }}</span>@endif
                    @if($debate->constituency)<span class="badge">{{ $debate->constituency }}</span>@endif
                    @if($debate->archived_at)<span class="badge">{{ __('Archivé') }}</span>@endif
                </div>
                @if($debate->description)<div class="post-text mt" data-text>{!! render_text($debate->description) !!}</div>@endif
                <div class="small muted">{{ __('Organisé par') }} <a href="{{ $debate->user->profileUrl() }}">{{ $debate->user->name }}</a> · {{ __('Modérateur') }} : <b>{{ $debate->moderator?->name ?? '—' }}</b></div>
                @include('partials.actions', ['model' => $debate, 'type' => 'debate'])
            </div></div>

            @if($debate->summary)
                <div class="card"><div class="card-head"><h3><x-icon name="sparkles" style="vertical-align:-4px"/> {{ __('Résumé du débat') }}</h3><span class="badge badge-info">{{ __('Généré automatiquement') }}</span></div>
                    <div class="card-body prose">{!! nl2br(e($debate->summary)) !!}</div>
                    <div class="card-foot small faint">{{ __('Résumé neutre généré par IA à partir de la transcription et des questions. Il peut contenir des erreurs ; référez-vous au replay.') }}</div></div>
            @endif

            @if($debate->rules)
                <details class="card"><summary class="card-body" style="cursor:pointer"><strong>{{ __('Règles du débat') }}</strong></summary><div class="card-body prose" style="padding-top:0">{!! nl2br(e($debate->rules)) !!}</div></details>
            @endif

            <div class="card"><div class="card-head"><h3>{{ __('Questions du public') }} ({{ $questions->count() }})</h3></div>
                @if($debate->public_questions && in_array($debate->status, ['scheduled', 'live']))
                    @auth
                        <form method="POST" action="{{ route('debates.questions.store', $debate) }}" class="comment-form">@csrf
                            <div class="grow">
                                <textarea name="body" class="textarea" rows="2" maxlength="500" required placeholder="{{ __('Votre question aux candidats…') }}" aria-label="{{ __('Votre question') }}"></textarea>
                                <select name="target_user_id" class="select input-sm mt-sm" aria-label="{{ __('Destinataire') }}"><option value="">{{ __('À tous les candidats') }}</option>
                                    @foreach($debate->participants as $p)<option value="{{ $p->user_id }}">{{ $p->user->name }}</option>@endforeach</select>
                            </div>
                            <button class="btn btn-primary btn-sm">{{ __('Envoyer') }}</button>
                        </form>
                    @endauth
                @endif
                @forelse($questions as $q)
                    <div class="item">
                        <button type="button" class="btn btn-outline btn-sm {{ in_array($q->id, $votedIds) ? 'btn-soft' : '' }}" style="flex-direction:column;padding:.3rem .5rem" data-action="{{ route('debates.questions.vote', $q) }}" data-support data-auth aria-label="{{ __('Voter pour cette question') }}"><x-icon name="chevron-up"/><span data-count>{{ $q->votes_count }}</span></button>
                        <div class="item-body">
                            <div>{{ $q->body }}</div>
                            <div class="small faint">{{ $q->user->name }}@if($q->target) → {{ $q->target->name }}@endif
                                @if($q->status === 'selected')<span class="badge badge-primary">{{ __('Sélectionnée') }}</span>@elseif($q->status === 'answered')<span class="badge badge-success">{{ __('Répondue') }}</span>@endif</div>
                        </div>
                        @if($debate->canManage($viewer))
                            <div class="dropdown"><button class="btn btn-ghost btn-icon btn-sm" data-dropdown aria-label="{{ __('Modération') }}"><x-icon name="more"/></button>
                                <div class="menu">@foreach(['selected' => __('Sélectionner'), 'answered' => __('Marquer répondue'), 'pending' => __('En attente'), 'rejected' => __('Rejeter')] as $st => $lbl)
                                    <form method="POST" action="{{ route('debates.questions.status', $q) }}">@csrf<input type="hidden" name="status" value="{{ $st }}"><button>{{ $lbl }}</button></form>@endforeach</div></div>
                        @endif
                    </div>
                @empty
                    <p class="p muted small">{{ __('Aucune question pour le moment.') }}</p>
                @endforelse
            </div>
            @include('partials.comments', ['target' => $debate, 'type' => 'debate', 'comments' => $debate->rootComments()->where('is_hidden', false)->with(['user', 'replies.user'])->latest()->paginate(20)])
        </div>

        <div>
            <div class="card"><div class="card-head"><h3>{{ __('Candidats') }}</h3></div>
                @foreach($debate->participants as $p)
                    <div class="user-card">
                        <img src="{{ $p->user->candidateProfile?->photoUrl() ?? $p->user->avatarUrl() }}" alt="" class="avatar">
                        <div class="info"><a href="{{ route('candidates.show', $p->user->username) }}" class="name">{{ $p->user->candidateProfile?->full_name ?? $p->user->name }}</a>@include('partials.verified', ['u' => $p->user])
                            <div class="small faint">{{ position_name($p->user->candidateProfile?->position_sought) }}</div></div>
                        <span class="badge {{ $p->status === 'accepted' ? 'badge-success' : ($p->status === 'declined' ? 'badge-danger' : 'badge-warning') }}">{{ ['accepted' => __('Confirmé'), 'declined' => __('Décliné'), 'invited' => __('Invité')][$p->status] }}</span>
                        @if($debate->canManage($viewer))
                            <form method="POST" action="{{ route('debates.participants.remove', [$debate, $p]) }}" data-confirm="{{ __('Retirer ce participant ?') }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-icon btn-sm" aria-label="{{ __('Retirer') }}"><x-icon name="x"/></button></form>
                        @endif
                    </div>
                @endforeach
                @if($debate->canManage($viewer))
                    <form method="POST" action="{{ route('debates.invite', $debate) }}" class="card-foot row">@csrf
                        <select name="user_id" class="select input-sm grow" aria-label="{{ __('Inviter un candidat') }}">
                            @foreach(\App\Models\User::whereIn('account_type', ['candidate', 'official'])->whereNotIn('id', $debate->participants->pluck('user_id'))->orderBy('name')->get() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select><button class="btn btn-soft btn-sm">{{ __('Inviter') }}</button></form>
                @endif
            </div>
            <a href="{{ route('compare.index', ['c' => $debate->participants->pluck('user.username')->all()]) }}" class="btn btn-outline btn-block"><x-icon name="compare"/>{{ __('Comparer leurs programmes') }}</a>
        </div>
    </div>
@endsection
