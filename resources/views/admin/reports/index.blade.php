@extends('layouts.admin')
@section('title', __('File de modération'))
@section('content')
    <div class="pills mb">@foreach(['pending' => __('En attente'), 'reviewing' => __('En cours'), 'resolved' => __('Traités'), 'dismissed' => __('Classés'), 'all' => __('Tous')] as $k => $l)<a href="?status={{ $k }}" class="pill {{ $status === $k ? 'active' : '' }}">{{ $l }} @if($k !== 'all')({{ $counts[$k] ?? 0 }})@endif</a>@endforeach</div>
    <form method="GET" class="filters"><input type="hidden" name="status" value="{{ $status }}">
        <select name="reason" class="select" aria-label="{{ __('Motif') }}"><option value="">{{ __('Tous motifs') }}</option>@foreach(\App\Models\Report::REASONS as $r)<option value="{{ $r }}" @selected(request('reason') === $r)>{{ \App\Models\Report::reasonLabel($r) }}</option>@endforeach</select>
        <select name="type" class="select" aria-label="{{ __('Type') }}"><option value="">{{ __('Tous types') }}</option>@foreach(\App\Support\Morph::REPORTABLE as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ \App\Support\Morph::label($t) }}</option>@endforeach</select>
        <button class="btn btn-primary">{{ __('Filtrer') }}</button></form>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Priorité') }}</th><th>{{ __('Motif') }}</th><th>{{ __('Contenu') }}</th><th>{{ __('Utilisateur signalé') }}</th><th>{{ __('Signalé par') }}</th><th>IA</th><th>{{ __('Date') }}</th><th></th></tr></thead>
        <tbody>@forelse($reports as $r)
            <tr><td>@if($r->priority === 'high')<span class="badge badge-danger">{{ __('Haute') }}</span>@else<span class="badge">{{ __('Normale') }}</span>@endif</td>
                <td><strong>{{ \App\Models\Report::reasonLabel($r->reason) }}</strong>@if($r->details)<div class="small faint truncate" style="max-width:220px">{{ $r->details }}</div>@endif</td>
                <td class="small">{{ \App\Support\Morph::label($r->reportable_type) }} #{{ $r->reportable_id }}</td>
                <td class="small">{{ $r->reportedUser ? '@'.$r->reportedUser->username : '—' }}</td>
                <td class="small">{{ $r->reporter?->username ? '@'.$r->reporter->username : '—' }}</td>
                <td>@if($r->ai_score !== null)<span class="badge {{ $r->ai_score >= .7 ? 'badge-danger' : 'badge-info' }}" title="{{ $r->ai_label }}">{{ round($r->ai_score * 100) }}%</span>@else — @endif</td>
                <td class="small">{{ $r->created_at->diffForHumans() }}</td>
                <td class="actions-cell"><a href="{{ route('admin.reports.show', $r) }}" class="btn btn-primary btn-sm">{{ in_array($r->status, ['pending', 'reviewing']) ? __('Traiter') : __('Voir') }}</a></td></tr>
        @empty<tr><td colspan="8" class="muted">{{ __('File vide.') }} ✓</td></tr>@endforelse</tbody>
    </table></div></div>{{ $reports->links() }}
@endsection
