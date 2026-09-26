@extends('layouts.admin')
@section('title', __('Responsables élus'))
@section('content')
    <details class="card"><summary class="card-body" style="cursor:pointer"><strong>+ {{ __('Ajouter / rattacher un profil d\'élu') }}</strong></summary>
        <form method="POST" action="{{ route('admin.officials.store') }}" class="card-body" style="padding-top:0">@csrf
            <div class="grid grid-3">
                <input name="username" class="input" placeholder="{{ __('Compte (username)') }}" required aria-label="username">
                <input name="full_name" class="input" placeholder="{{ __('Nom complet') }}" required aria-label="{{ __('Nom') }}">
                <input name="office" class="input" placeholder="{{ __('Fonction') }}" required aria-label="{{ __('Fonction') }}">
                <input name="institution" class="input" placeholder="{{ __('Institution') }}" aria-label="{{ __('Institution') }}">
                <input name="constituency" class="input" placeholder="{{ __('Circonscription') }}" aria-label="{{ __('Circonscription') }}">
                <select name="department" class="select" aria-label="{{ __('Département') }}"><option value="">—</option>@foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}">{{ $d['name'] }}</option>@endforeach</select>
                <input name="party" class="input" placeholder="{{ __('Parti') }}" aria-label="{{ __('Parti') }}">
                <input type="date" name="mandate_start" class="input" aria-label="{{ __('Début du mandat') }}"><input type="date" name="mandate_end" class="input" aria-label="{{ __('Fin du mandat') }}">
            </div>
            <button class="btn btn-primary mt-sm">{{ __('Enregistrer') }}</button></form></details>
    <form method="GET" class="filters"><input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Nom') }}" aria-label="{{ __('Rechercher') }}"><button class="btn btn-primary">{{ __('Filtrer') }}</button></form>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Élu') }}</th><th>{{ __('Fonction') }}</th><th>{{ __('Circonscription') }}</th><th>{{ __('Mandat') }}</th><th></th></tr></thead>
        <tbody>@forelse($officials as $o)<tr><td><strong>{{ $o->full_name }}</strong> <span class="faint small">{{ '@'.$o->user->username }}</span></td><td>{{ $o->office }}<div class="small faint">{{ $o->institution }}</div></td><td>{{ $o->constituency }}</td>
            <td class="small">{{ $o->mandate_start?->format('Y') }}–{{ $o->mandate_end?->format('Y') }}</td><td class="actions-cell"><a href="{{ route('officials.show', $o->user->username) }}" class="btn btn-ghost btn-sm" target="_blank">{{ __('Suivi') }}</a></td></tr>
        @empty<tr><td colspan="5" class="muted">{{ __('Aucun élu.') }}</td></tr>@endforelse</tbody>
    </table></div></div>{{ $officials->links() }}
@endsection
