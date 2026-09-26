@extends('layouts.admin')
@section('title', __('Vérification des sources'))
@section('content')
    <div class="pills mb">@foreach(['pending' => __('À vérifier'), 'unverified' => __('Non vérifiées'), 'provided' => __('Fournies par candidats'), 'verified' => __('Vérifiées'), 'disputed' => __('Contestées'), 'all' => __('Toutes')] as $k => $l)<a href="?status={{ $k }}" class="pill {{ request('status', 'unverified') === $k ? 'active' : '' }}">{{ $l }}</a>@endforeach</div>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Source') }}</th><th>{{ __('Concerne') }}</th><th>{{ __('Statut') }}</th><th>{{ __('Ajoutée par') }}</th><th>{{ __('Décision') }}</th></tr></thead>
        <tbody>@forelse($sources as $s)
            <tr><td><strong>{{ $s->name }}</strong>@if($s->url)<div class="small"><a href="{{ $s->url }}" target="_blank" rel="noopener nofollow">{{ \Illuminate\Support\Str::limit($s->url, 50) }}</a></div>@endif<div class="small faint">{{ $s->published_on?->format('d/m/Y') }}</div></td>
                <td class="small">{{ \App\Support\Morph::label($s->sourceable_type) }} #{{ $s->sourceable_id }} · <a href="{{ route('sources.show', $s) }}">{{ __('historique') }}</a></td>
                <td><span class="badge badge-{{ $s->statusColor() }}">{{ $s->statusLabel() }}</span></td>
                <td class="small">{{ $s->user?->name ?? '—' }}</td>
                <td><form method="POST" action="{{ route('admin.sources.update', $s) }}" class="row">@csrf @method('PUT')
                    <select name="status" class="select input-sm" aria-label="{{ __('Statut') }}">@foreach(\App\Models\Source::STATUSES as $st)<option value="{{ $st }}" @selected($s->status === $st)>{{ __($st) }}</option>@endforeach</select>
                    <input name="note" class="input input-sm" placeholder="{{ __('Note') }}" aria-label="{{ __('Note') }}"><button class="btn btn-primary btn-sm">OK</button></form></td></tr>
        @empty<tr><td colspan="5" class="muted">{{ __('Aucune source.') }}</td></tr>@endforelse</tbody>
    </table></div></div>{{ $sources->links() }}
@endsection
