@extends('layouts.admin')
@section('title', __('Utilisateurs'))
@section('content')
    <form method="GET" class="filters">
        <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Nom, @username, e-mail, téléphone') }}" aria-label="{{ __('Rechercher') }}">
        <select name="status" class="select" aria-label="{{ __('Statut') }}"><option value="">{{ __('Tous statuts') }}</option>@foreach(['active' => __('Actif'), 'suspended' => __('Suspendu'), 'banned' => __('Banni'), 'deleted' => __('Supprimé')] as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
        <select name="type" class="select" aria-label="{{ __('Type') }}"><option value="">{{ __('Tous types') }}</option>@foreach(['personal' => __('Citoyen'), 'candidate' => __('Candidat'), 'organization' => __('Organisation'), 'official' => __('Élu')] as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select>
        <select name="verified" class="select" aria-label="{{ __('Vérification') }}"><option value="">{{ __('Vérifiés ou non') }}</option><option value="1" @selected(request('verified') === '1')>{{ __('Vérifiés') }}</option><option value="0" @selected(request('verified') === '0')>{{ __('Non vérifiés') }}</option></select>
        <select name="role" class="select" aria-label="{{ __('Rôle') }}"><option value="">{{ __('Tous rôles') }}</option>@foreach($roles as $r)<option value="{{ $r->name }}" @selected(request('role') === $r->name)>{{ $r->label }}</option>@endforeach</select>
        <label class="check mb-0 small"><input type="checkbox" name="unverified_email" value="1" @checked(request('unverified_email'))>{{ __('E-mail non vérifié') }}</label>
        <label class="check mb-0 small"><input type="checkbox" name="deletion" value="1" @checked(request('deletion'))>{{ __('Suppression demandée') }}</label>
        <button class="btn btn-primary">{{ __('Filtrer') }}</button>
    </form>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Utilisateur') }}</th><th>{{ __('Type') }}</th><th>{{ __('Statut') }}</th><th>{{ __('Rôles') }}</th><th>{{ __('Abonnés') }}</th><th>{{ __('Pays') }}</th><th>{{ __('Inscrit') }}</th><th>{{ __('Actif') }}</th><th></th></tr></thead>
        <tbody>
        @forelse($users as $u)
            <tr>
                <td><div class="row"><img src="{{ $u->avatarUrl() }}" class="avatar avatar-sm" alt=""><div><a href="{{ route('admin.users.show', $u->id) }}"><strong>{{ $u->name }}</strong></a>@include('partials.verified', ['u' => $u])<div class="small faint">{{ '@'.$u->username }} · {{ $u->email }} @unless($u->email_verified_at)<span class="badge badge-warning">!</span>@endunless</div></div></div></td>
                <td>{{ $u->accountTypeLabel() }}</td>
                <td>@if($u->trashed())<span class="badge badge-danger">{{ __('Supprimé') }}</span>@elseif($u->status !== 'active')<span class="badge badge-danger">{{ __($u->status) }}</span>@else<span class="badge badge-success">{{ __('Actif') }}</span>@endif
                    @if($u->deletion_requested_at)<span class="badge badge-warning">{{ __('Suppression') }}</span>@endif</td>
                <td class="small">{{ $u->roles->pluck('label')->implode(', ') ?: '—' }}</td>
                <td>{{ $u->followers_count }}</td><td>{{ $u->country }}</td>
                <td class="small">{{ $u->created_at->format('d/m/Y') }}</td><td class="small">{{ $u->last_active_at?->diffForHumans(null, true, true) ?? '—' }}</td>
                <td class="actions-cell"><a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-ghost btn-sm">{{ __('Gérer') }}</a></td>
            </tr>
        @empty<tr><td colspan="9" class="muted">{{ __('Aucun utilisateur.') }}</td></tr>@endforelse
        </tbody>
    </table></div></div>
    {{ $users->links() }}
@endsection
