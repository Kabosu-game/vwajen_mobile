@extends('layouts.admin')
@section('title', __('Hashtags'))
@section('content')
    <form method="GET" class="filters"><input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="#" aria-label="{{ __('Rechercher') }}">
        <select name="sort" class="select" aria-label="{{ __('Tri') }}"><option value="">{{ __('Plus utilisés') }}</option><option value="recent" @selected(request('sort') === 'recent')>{{ __('Récents') }}</option></select><button class="btn btn-primary">{{ __('Filtrer') }}</button></form>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>Hashtag</th><th>{{ __('Utilisations') }}</th><th>{{ __('Dernière utilisation') }}</th><th>{{ __('État') }}</th><th></th></tr></thead>
        <tbody>@forelse($hashtags as $h)<tr><td><a href="{{ $h->url() }}" target="_blank">#{{ $h->name }}</a></td><td>{{ $h->uses_count }}</td><td class="small">{{ $h->last_used_at?->diffForHumans() }}</td>
            <td>{!! $h->is_blocked ? '<span class="badge badge-danger">'.e(__('Bloqué')).'</span>' : '<span class="badge badge-success">'.e(__('Actif')).'</span>' !!}</td>
            <td class="actions-cell"><form method="POST" action="{{ route('admin.hashtags.toggle', $h) }}">@csrf<button class="btn btn-sm {{ $h->is_blocked ? 'btn-success' : 'btn-danger-outline' }}">{{ $h->is_blocked ? __('Débloquer') : __('Bloquer') }}</button></form></td></tr>
        @empty<tr><td colspan="5" class="muted">—</td></tr>@endforelse</tbody>
    </table></div></div>{{ $hashtags->links() }}
@endsection
