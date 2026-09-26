@extends('layouts.app')
@section('title', __('Données civiques publiques'))
@section('content')
    <div class="page-head"><a href="{{ route('observatory.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Données civiques publiques') }}</h1></div>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Département') }}</th><th>{{ __('Chef-lieu') }}</th><th>{{ __('Candidats') }}</th><th>{{ __('Élus référencés') }}</th><th>{{ __('Membres Vwajèn') }}</th><th></th></tr></thead>
        <tbody>@foreach($departments as $d)
            <tr><td><strong>{{ $d['name'] }}</strong></td><td>{{ $d['capital'] }}</td><td>{{ $d['candidates'] }}</td><td>{{ $d['officials'] }}</td><td>{{ $d['members'] }}</td>
                <td class="actions-cell"><a href="{{ route('candidates.index', ['department' => $d['slug']]) }}" class="btn btn-ghost btn-sm">{{ __('Candidats') }}</a><a href="{{ route('officials.index', ['department' => $d['slug']]) }}" class="btn btn-ghost btn-sm">{{ __('Élus') }}</a></td></tr>
        @endforeach</tbody>
    </table></div></div>
    <p class="small muted">{{ __('Données ouvertes également disponibles via l\'API publique.') }} <a href="{{ route('developers') }}">{{ __('Documentation') }}</a></p>
@endsection
