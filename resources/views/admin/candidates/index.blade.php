@extends('layouts.admin')
@section('title', __('Candidats'))
@section('content')
    <form method="GET" class="filters">
        <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Nom, parti') }}" aria-label="{{ __('Rechercher') }}">
        <select name="status" class="select" aria-label="{{ __('Statut') }}"><option value="">{{ __('Tous statuts') }}</option>@foreach(['pending' => __('En attente'), 'verified' => __('Vérifié'), 'rejected' => __('Rejeté')] as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
        <select name="department" class="select" aria-label="{{ __('Département') }}"><option value="">{{ __('Tous départements') }}</option>@foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}" @selected(request('department') === $k)>{{ $d['name'] }}</option>@endforeach</select>
        <button class="btn btn-primary">{{ __('Filtrer') }}</button>
    </form>
    @foreach($profiles as $p)
        <details class="card">
            <summary class="card-body row" style="cursor:pointer">
                <img src="{{ $p->photoUrl() }}" class="avatar" alt="">
                <div class="grow"><strong>{{ $p->full_name }}</strong> <span class="small faint">{{ '@'.$p->user->username }}</span>
                    <div class="small muted">{{ position_name($p->position_sought) }} · {{ $p->constituency }} · {{ department_name($p->department) }} · {{ $p->party }}</div></div>
                <span class="badge {{ ['verified' => 'badge-success', 'rejected' => 'badge-danger'][$p->status] ?? 'badge-warning' }}">{{ ['verified' => __('Vérifié'), 'rejected' => __('Rejeté'), 'pending' => __('En attente')][$p->status] }}</span>
            </summary>
            <form method="POST" action="{{ route('admin.candidates.update', $p) }}" class="card-body" style="padding-top:0">@csrf @method('PUT')
                <div class="grid grid-3">
                    <div class="field"><label class="label">{{ __('Statut') }}</label><select name="status" class="select">@foreach(['pending', 'verified', 'rejected'] as $s)<option value="{{ $s }}" @selected($p->status === $s)>{{ __($s) }}</option>@endforeach</select></div>
                    <div class="field"><label class="label">{{ __('Nom') }}</label><input name="full_name" class="input" value="{{ $p->full_name }}"></div>
                    <div class="field"><label class="label">{{ __('Parti') }}</label><input name="party" class="input" value="{{ $p->party }}"></div>
                    <div class="field"><label class="label">{{ __('Poste') }}</label><select name="position_sought" class="select">@foreach(config('vwajen.positions') as $k => $l)<option value="{{ $k }}" @selected($p->position_sought === $k)>{{ __($l) }}</option>@endforeach</select></div>
                    <div class="field"><label class="label">{{ __('Circonscription') }}</label><input name="constituency" class="input" value="{{ $p->constituency }}"></div>
                    <div class="field"><label class="label">{{ __('Département') }}</label><select name="department" class="select">@foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}" @selected($p->department === $k)>{{ $d['name'] }}</option>@endforeach</select></div>
                </div>
                <input name="note" class="input" placeholder="{{ __('Note publique (historique des modifications)') }}" aria-label="{{ __('Note') }}">
                <div class="row mt-sm"><button class="btn btn-primary btn-sm">{{ __('Enregistrer') }}</button><a href="{{ route('candidates.show', $p->user->username) }}" class="btn btn-ghost btn-sm" target="_blank">{{ __('Voir') }}</a><a href="{{ route('admin.users.show', $p->user_id) }}" class="btn btn-ghost btn-sm">{{ __('Compte') }}</a></div>
            </form>
        </details>
    @endforeach
    @if($profiles->isEmpty())<div class="card empty"><p>{{ __('Aucun candidat.') }}</p></div>@endif
    {{ $profiles->links() }}
@endsection
