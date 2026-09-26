@extends('layouts.admin')
@section('title', __('Signalement #:id', ['id' => $report->id]))
@section('content')
    <div class="split-layout">
        <div>
            <div class="card"><div class="card-body">
                <div class="row wrap" style="gap:.35rem"><span class="badge badge-danger">{{ \App\Models\Report::reasonLabel($report->reason) }}</span><span class="badge">{{ __($report->status) }}</span>
                    @if($report->priority === 'high')<span class="badge badge-danger">{{ __('Priorité haute') }}</span>@endif
                    @if($report->ai_score !== null)<span class="badge badge-info">{{ __('Pré-analyse IA') }} : {{ round($report->ai_score * 100) }}% · {{ $report->ai_label }}</span>@endif</div>
                <p class="mt-sm">{{ $report->details ?: __('Aucune précision.') }}</p>
                <div class="small faint">{{ __('Signalé par') }} {{ $report->reporter?->name ?? __('compte supprimé') }} · {{ $report->created_at->format('d/m/Y H:i') }}</div>
                @if($report->resolution)<div class="alert alert-info mt"><x-icon name="info"/><div>{{ __('Décision') }} : {{ $report->resolution }} — {{ $report->handler?->name }} · {{ $report->handled_at?->format('d/m/Y H:i') }}</div></div>@endif
            </div></div>

            <div class="card"><div class="card-head"><h3>{{ __('Contenu signalé') }} — {{ \App\Support\Morph::label($report->reportable_type) }} #{{ $report->reportable_id }}</h3>
                @if($target && method_exists($target, 'url') && ! (method_exists($target, 'trashed') && $target->trashed()))<a href="{{ $target->url() }}" target="_blank" class="small">{{ __('Ouvrir') }}</a>@endif</div>
                <div class="card-body">
                    @if(! $target)<p class="muted">{{ __('Le contenu n\'existe plus.') }}</p>
                    @else
                        @if(method_exists($target, 'trashed') && $target->trashed())<span class="badge badge-danger">{{ __('Supprimé') }}</span>@endif
                        @if($target->is_hidden ?? false)<span class="badge badge-warning">{{ __('Masqué') }}</span>@endif
                        @if($target instanceof \App\Models\User)
                            <div class="row"><img src="{{ $target->avatarUrl() }}" class="avatar" alt=""><div><strong>{{ $target->name }}</strong> {{ '@'.$target->username }}<div class="small">{{ $target->bio }}</div></div></div>
                        @else
                            @if($target->title ?? null)<h3>{{ $target->title }}</h3>@endif
                            <div class="prose" style="white-space:pre-wrap">{{ $target->body ?? $target->description ?? $target->name ?? '' }}</div>
                            @if($target instanceof \App\Models\Post && $target->media->isNotEmpty())<div class="media-grid n{{ min(4, $target->media->count()) }} mt-sm">@foreach($target->media as $med)@if($med->type === 'image')<img src="{{ $med->url() }}" alt="">@else<video src="{{ $med->url() }}" controls></video>@endif @endforeach</div>@endif
                            @if($target instanceof \App\Models\Video)<video src="{{ $target->videoUrl() }}" controls style="max-height:320px;margin-top:.5rem"></video>@endif
                        @endif
                    @endif
                </div></div>

            @if($related->isNotEmpty())
                <div class="card"><div class="card-head"><h3>{{ __('Autres signalements sur ce contenu') }} ({{ $related->count() }})</h3></div>
                    @foreach($related as $r)<div class="item small"><span class="badge">{{ \App\Models\Report::reasonLabel($r->reason) }}</span><div class="item-body">{{ $r->details }}<div class="faint">{{ $r->reporter?->name }} · {{ $r->created_at->diffForHumans() }} · {{ __($r->status) }}</div></div></div>@endforeach</div>
            @endif
        </div>
        <aside>
            @if(in_array($report->status, ['pending', 'reviewing']))
                <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" class="card"><div class="card-head"><h3>{{ __('Décision') }}</h3></div><div class="card-body">
                    @csrf
                    @foreach(['dismiss' => __('Classer sans suite'), 'hide' => __('Masquer le contenu'), 'delete' => __('Supprimer le contenu'), 'warning' => __('Avertir l\'auteur'), 'suspension' => __('Suspendre l\'auteur'), 'ban' => __('Bannir l\'auteur')] as $k => $l)
                        <label class="check"><input type="radio" name="decision" value="{{ $k }}" required @checked($k === 'dismiss')>{{ $l }}</label>
                    @endforeach
                    <input type="number" name="days" min="1" max="365" value="7" class="input input-sm" aria-label="{{ __('Durée de suspension (jours)') }}">
                    <textarea name="reason" class="textarea mt-sm" rows="3" required placeholder="{{ __('Motif (communiqué à l\'auteur si sanction)') }}" aria-label="{{ __('Motif') }}"></textarea>
                    <label class="check mt-sm"><input type="checkbox" name="apply_all" value="1" checked>{{ __('Appliquer à tous les signalements de ce contenu') }}</label>
                    <button class="btn btn-primary btn-block">{{ __('Valider la décision') }}</button>
                </div></form>
            @endif
            @if($owner)
                <div class="card"><div class="card-head"><h3>{{ __('Auteur') }}</h3><a href="{{ route('admin.users.show', $owner->id) }}" class="small">{{ __('Compte') }}</a></div><div class="card-body">
                    <div class="row"><img src="{{ $owner->avatarUrl() }}" class="avatar avatar-sm" alt=""><div><strong>{{ $owner->name }}</strong><div class="small faint">{{ '@'.$owner->username }} · {{ __($owner->status) }}</div></div></div>
                    <h4 class="mt">{{ __('Historique des sanctions') }}</h4>
                    @forelse($history as $s)<div class="small">{{ $s->created_at->format('d/m/Y') }} — <strong>{{ $s->typeLabel() }}</strong> @if($s->revoked_at)({{ __('levée') }})@endif</div>@empty<div class="small muted">{{ __('Aucune.') }}</div>@endforelse
                </div></div>
            @endif
        </aside>
    </div>
@endsection
