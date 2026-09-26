@extends('layouts.admin')
@section('title', __('Statistiques'))
@section('actions')
    <div class="pills">@foreach([7, 30, 90, 365] as $d)<a href="?days={{ $d }}" class="pill {{ $days === $d ? 'active' : '' }}">{{ $d }} j</a>@endforeach</div>
    <a href="{{ route('admin.stats.export', ['days' => $days]) }}" class="btn btn-outline btn-sm"><x-icon name="download"/>CSV</a>
@endsection
@php
    $labels = ['users' => __('Utilisateurs inscrits'), 'new_users' => __('Nouveaux utilisateurs'), 'active_users_30' => __('Utilisateurs actifs (30 j)'), 'posts' => __('Publications'),
        'likes' => __('Likes'), 'comments' => __('Commentaires'), 'reposts' => __('Reposts'), 'videos' => __('Vidéos'), 'views' => __('Vues'), 'shorts' => 'Shorts',
        'lives' => __('Lives'), 'live_viewers' => __('Spectateurs'), 'questions' => __('Questions'), 'answers' => __('Réponses'), 'events' => __('Événements'), 'reports' => __('Signalements')];
@endphp
@section('content')
    <div class="grid grid-4 mb">
        @foreach($labels as $k => $l)<div class="stat"><div class="v">{{ number_format($totals[$k] ?? 0, 0, ',', ' ') }}</div><div class="l">{{ $l }}</div>@if(! in_array($k, ['users', 'active_users_30']))<div class="d faint">{{ __(':d derniers jours', ['d' => $days]) }}</div>@endif</div>@endforeach
    </div>
    <div class="grid grid-3">
        @foreach($series as $label => $s)
            <div class="card"><div class="card-head"><h3>{{ $label }}</h3></div><div class="card-body">@include('partials.bar-chart', ['series' => $s, 'label' => $label, 'height' => 110])</div></div>
        @endforeach
    </div>
    <div class="grid grid-3">
        @foreach(['account_types' => __('Types de comptes'), 'countries' => __('Pays'), 'departments' => __('Départements'), 'locales' => __('Langues'), 'report_reasons' => __('Motifs de signalement')] as $k => $title)
            <div class="card"><div class="card-head"><h3>{{ $title }}</h3></div><div class="card-body">
                @php $b = $breakdowns[$k]; $sum = max(1, $b->sum()); @endphp
                @forelse($b as $key => $n)
                    <div class="row between small"><span>{{ $k === 'countries' ? country_name($key) : ($k === 'departments' ? department_name($key) : ($k === 'report_reasons' ? \App\Models\Report::reasonLabel($key) : __($key))) }}</span><b>{{ $n }}</b></div>
                    <div class="progress" style="margin:.25rem 0 .55rem"><span style="width:{{ $n * 100 / $sum }}%"></span></div>
                @empty<span class="muted small">—</span>@endforelse
            </div></div>
        @endforeach
    </div>
    <div class="card"><div class="card-head"><h3>{{ __('Statistiques des candidats') }}</h3></div><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Candidat') }}</th><th>{{ __('Abonnés') }}</th><th>{{ __('Publications') }}</th><th>{{ __('Likes') }}</th><th>{{ __('Vidéos') }}</th><th>{{ __('Lives') }}</th><th>{{ __('Questions') }}</th><th>{{ __('Répondues') }}</th></tr></thead>
        <tbody>@foreach($candidates as $c)<tr><td><a href="{{ route('admin.users.show', $c['user']->id) }}">{{ $c['user']->name }}</a> @if($c['user']->is_verified)✔@endif</td><td>{{ $c['followers'] }}</td><td>{{ $c['posts'] }}</td><td>{{ $c['likes'] }}</td><td>{{ $c['videos'] }}</td><td>{{ $c['lives'] }}</td><td>{{ $c['questions'] }}</td><td>{{ $c['answered'] }}</td></tr>@endforeach</tbody>
    </table></div></div>
@endsection
