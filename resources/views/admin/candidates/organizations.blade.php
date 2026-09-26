@extends('layouts.admin')
@section('title', __('Organisations'))
@section('content')
    <form method="GET" class="filters"><input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Raison sociale') }}" aria-label="{{ __('Rechercher') }}">
        <select name="type" class="select" aria-label="{{ __('Type') }}"><option value="">{{ __('Tous types') }}</option>@foreach(['party', 'ngo', 'media', 'government', 'association', 'company', 'other'] as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ __($t) }}</option>@endforeach</select>
        <button class="btn btn-primary">{{ __('Filtrer') }}</button></form>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Organisation') }}</th><th>{{ __('Type') }}</th><th>{{ __('N° enregistrement') }}</th><th>{{ __('Compte') }}</th><th>{{ __('Vérification') }}</th><th></th></tr></thead>
        <tbody>@forelse($orgs as $o)<tr><td><strong>{{ $o->legal_name }}</strong></td><td>{{ __($o->org_type) }}</td><td>{{ $o->registration_number ?: '—' }}</td><td>{{ '@'.$o->user->username }}</td>
            <td>@if($o->user->is_verified)<span class="badge badge-success">{{ __('Vérifiée') }}</span>@else<span class="badge badge-warning">{{ __($o->status) }}</span>@endif</td>
            <td class="actions-cell">
                <form method="POST" action="{{ route('admin.organizations.professional', $o) }}" style="display:inline">@csrf<button class="btn btn-sm {{ $o->is_professional ? 'btn-soft' : 'btn-outline' }}">{{ $o->is_professional ? __('Compte pro ✓') : __('Passer en compte pro') }}</button></form>
                <a href="{{ route('admin.users.show', $o->user_id) }}" class="btn btn-ghost btn-sm">{{ __('Gérer') }}</a></td></tr>
        @empty<tr><td colspan="6" class="muted">{{ __('Aucune organisation.') }}</td></tr>@endforelse</tbody>
    </table></div></div>{{ $orgs->links() }}
@endsection
