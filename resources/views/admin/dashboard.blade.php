@extends('layouts.admin')
@section('title', __('Vue générale'))
@section('content')
    <div class="grid grid-4 mb">
        <div class="stat"><div class="v">{{ number_format($totals['users'], 0, ',', ' ') }}</div><div class="l">{{ __('Utilisateurs inscrits') }}</div><div class="d" style="color:var(--success)">+{{ $totals['new_users'] }}</div></div>
        <div class="stat"><div class="v">{{ $totals['active_users_1'] }} / {{ $totals['active_users_7'] }} / {{ $totals['active_users_30'] }}</div><div class="l">{{ __('Actifs 1 j / 7 j / 30 j') }}</div></div>
        <div class="stat"><div class="v">{{ $totals['verified_candidates'] }}/{{ $totals['candidates'] }}</div><div class="l">{{ __('Candidats vérifiés') }}</div></div>
        <div class="stat" style="{{ $totals['reports_pending'] ? 'border-color:var(--danger)' : '' }}"><div class="v">{{ $totals['reports_pending'] }}</div><div class="l">{{ __('Signalements en attente') }}</div></div>
        <div class="stat"><div class="v">{{ short_number($totals['posts']) }}</div><div class="l">{{ __('Publications') }}</div></div>
        <div class="stat"><div class="v">{{ short_number($totals['videos'] + $totals['shorts']) }}</div><div class="l">{{ __('Vidéos + Shorts') }}</div></div>
        <div class="stat"><div class="v">{{ short_number($totals['questions']) }}</div><div class="l">{{ __('Questions') }} · {{ $totals['answers'] }} {{ __('réponses') }}</div></div>
        <div class="stat"><div class="v">{{ $pendingAppeals }}</div><div class="l">{{ __('Appels en attente') }}</div></div>
    </div>

    <div class="grid grid-3 mb">
        <div class="card"><div class="card-head"><h3>{{ __('Inscriptions (30 j)') }}</h3></div><div class="card-body">@include('partials.bar-chart', ['series' => $signups, 'label' => __('Inscriptions')])</div></div>
        <div class="card"><div class="card-head"><h3>{{ __('Utilisateurs actifs (30 j)') }}</h3></div><div class="card-body">@include('partials.bar-chart', ['series' => $active, 'label' => __('Actifs'), 'color' => 'var(--success)'])</div></div>
        <div class="card"><div class="card-head"><h3>{{ __('Publications (30 j)') }}</h3></div><div class="card-body">@include('partials.bar-chart', ['series' => $posts, 'label' => __('Publications'), 'color' => '#7c3aed'])</div></div>
    </div>

    <div class="grid grid-2">
        <div class="card"><div class="card-head"><h3>{{ __('File de modération') }}</h3><a href="{{ route('admin.reports.index') }}" class="small">{{ __('Tout voir') }}</a></div>
            @forelse($pendingReports as $r)
                <a href="{{ route('admin.reports.show', $r) }}" class="item" style="color:var(--text)"><x-icon name="flag"/>
                    <div class="item-body"><strong>{{ \App\Models\Report::reasonLabel($r->reason) }}</strong> · {{ \App\Support\Morph::label($r->reportable_type) }} #{{ $r->reportable_id }}
                        <div class="small faint">{{ $r->reporter?->name }} · {{ $r->created_at->diffForHumans() }}</div></div>
                    @if($r->priority === 'high')<span class="badge badge-danger">{{ __('Priorité') }}</span>@endif @if($r->ai_score !== null)<span class="badge badge-info">IA {{ round($r->ai_score * 100) }}%</span>@endif</a>
            @empty<div class="p muted small">{{ __('Aucun signalement en attente.') }} ✓</div>@endforelse
        </div>
        <div class="card"><div class="card-head"><h3>{{ __('Vérifications en attente') }}</h3><a href="{{ route('admin.verifications.index') }}" class="small">{{ __('Tout voir') }}</a></div>
            @forelse($pendingVerifications as $v)
                <a href="{{ route('admin.verifications.show', $v) }}" class="item" style="color:var(--text)"><img src="{{ $v->user->avatarUrl() }}" class="avatar avatar-sm" alt="">
                    <div class="item-body"><strong>{{ $v->user->name }}</strong> <span class="badge">{{ \App\Models\VerificationRequest::typeLabel($v->type) }}</span><div class="small faint">{{ $v->created_at->diffForHumans() }}</div></div></a>
            @empty<div class="p muted small">{{ __('Aucune demande en attente.') }} ✓</div>@endforelse
        </div>
        <div class="card"><div class="card-head"><h3>{{ __('En direct') }}</h3></div>
            @forelse($liveNow as $l)<a href="{{ $l->url() }}" class="item" style="color:var(--text)"><span class="badge badge-live">LIVE</span><div class="item-body"><strong>{{ $l->title }}</strong><div class="small faint">{{ $l->user->name }} · {{ $l->currentViewersCount() }} {{ __('spectateurs') }}</div></div></a>
            @empty<div class="p muted small">{{ __('Aucun live en cours.') }}</div>@endforelse
        </div>
        <div class="card"><div class="card-head"><h3>{{ __('Nouveaux utilisateurs') }}</h3><a href="{{ route('admin.users.index') }}" class="small">{{ __('Tout voir') }}</a></div>
            @foreach($recentUsers as $u)<a href="{{ route('admin.users.show', $u->id) }}" class="user-card" style="color:var(--text)"><img src="{{ $u->avatarUrl() }}" class="avatar avatar-sm" alt=""><div class="info"><strong>{{ $u->name }}</strong><div class="small faint">{{ '@'.$u->username }} · {{ $u->created_at->diffForHumans() }} · {{ country_name($u->country) }}</div></div>@unless($u->email_verified_at)<span class="badge badge-warning">{{ __('E-mail non vérifié') }}</span>@endunless</a>@endforeach
        </div>
    </div>
    @if($recentAudit->isNotEmpty())
        <div class="card"><div class="card-head"><h3>{{ __('Dernières actions administratives') }}</h3><a href="{{ route('admin.audit') }}" class="small">{{ __('Audit complet') }}</a></div>
            <div class="table-wrap"><table class="table"><tbody>@foreach($recentAudit as $a)<tr><td class="small faint">{{ $a->created_at->format('d/m H:i') }}</td><td>{{ $a->user?->name ?? __('Système') }}</td><td><code>{{ $a->action }}</code></td><td class="small">{{ $a->subject_type }} {{ $a->subject_id }}</td></tr>@endforeach</tbody></table></div></div>
    @endif
@endsection
