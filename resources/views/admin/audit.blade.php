@extends('layouts.admin')
@section('title', __('Journal d\'audit'))
@section('content')
    <form method="GET" class="filters">
        <select name="action" class="select" aria-label="{{ __('Action') }}"><option value="">{{ __('Toutes actions') }}</option>@foreach($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select>
        <input name="user" value="{{ request('user') }}" class="input" placeholder="@username" aria-label="{{ __('Utilisateur') }}">
        <input type="date" name="from" value="{{ request('from') }}" class="input" aria-label="{{ __('Du') }}"><input type="date" name="to" value="{{ request('to') }}" class="input" aria-label="{{ __('Au') }}">
        <button class="btn btn-primary">{{ __('Filtrer') }}</button></form>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Auteur') }}</th><th>{{ __('Action') }}</th><th>{{ __('Objet') }}</th><th>{{ __('Détails') }}</th><th>IP</th></tr></thead>
        <tbody>@forelse($logs as $l)
            <tr><td class="small">{{ $l->created_at->format('d/m/Y H:i:s') }}</td><td class="small">{{ $l->user?->name ?? __('Système') }}</td><td><code>{{ $l->action }}</code></td>
                <td class="small">{{ $l->subject_type }} {{ $l->subject_id }}</td><td class="small" style="max-width:320px"><code style="white-space:pre-wrap;font-size:.75rem">{{ $l->data ? \Illuminate\Support\Str::limit(json_encode($l->data, JSON_UNESCAPED_UNICODE), 240) : '' }}</code></td><td class="small">{{ $l->ip_address }}</td></tr>
        @empty<tr><td colspan="6" class="muted">—</td></tr>@endforelse</tbody>
    </table></div></div>{{ $logs->links() }}
@endsection
