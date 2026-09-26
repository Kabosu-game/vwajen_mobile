@extends('layouts.admin')
@section('title', __('Rôles et permissions'))
@section('content')
    @foreach($roles as $role)
        <details class="card" @if($loop->first) open @endif>
            <summary class="card-body row" style="cursor:pointer"><x-icon name="key"/><strong class="grow">{{ $role->label }} <code class="small">{{ $role->name }}</code></strong>
                <span class="small faint">{{ trans_choice(':n utilisateur|:n utilisateurs', $role->users_count, ['n' => $role->users_count]) }} · {{ $role->name === 'superadmin' ? __('toutes') : $role->permissions->count() }} {{ __('permissions') }}</span>
                @if($role->is_system)<span class="badge">{{ __('Système') }}</span>@endif</summary>
            <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="card-body" style="padding-top:0">@csrf @method('PUT')
                <div class="grid grid-2"><input name="label" value="{{ $role->label }}" class="input" aria-label="{{ __('Libellé') }}" @disabled($role->name === 'superadmin')><input name="description" value="{{ $role->description }}" class="input" placeholder="{{ __('Description') }}" aria-label="{{ __('Description') }}" @disabled($role->name === 'superadmin')></div>
                <div class="grid grid-3 mt">
                    @foreach($permissions as $group => $perms)
                        <fieldset><legend>{{ __(ucfirst($group)) }}</legend>
                            @foreach($perms as $p)<label class="check small"><input type="checkbox" name="permissions[]" value="{{ $p->id }}" @checked($role->name === 'superadmin' || $role->permissions->contains($p)) @disabled($role->name === 'superadmin')><span>{{ __($p->label) }}<br><code style="font-size:.7rem">{{ $p->name }}</code></span></label>@endforeach
                        </fieldset>
                    @endforeach
                </div>
                @if($role->name !== 'superadmin')
                    <div class="row"><button class="btn btn-primary btn-sm">{{ __('Enregistrer') }}</button>
                        @unless($role->is_system)<button type="submit" form="del-role-{{ $role->id }}" class="btn btn-danger-outline btn-sm">{{ __('Supprimer') }}</button>@endunless</div>
                @endif
            </form>
            @unless($role->is_system)<form id="del-role-{{ $role->id }}" method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-confirm="{{ __('Supprimer ce rôle ?') }}">@csrf @method('DELETE')</form>@endunless
        </details>
    @endforeach
    <div class="grid grid-2">
        <form method="POST" action="{{ route('admin.roles.store') }}" class="card"><div class="card-head"><h3>{{ __('Nouveau rôle') }}</h3></div><div class="card-body">
            @csrf<input name="label" class="input" placeholder="{{ __('Libellé') }}" required aria-label="{{ __('Libellé') }}"><input name="description" class="input mt-sm" placeholder="{{ __('Description') }}" aria-label="{{ __('Description') }}">
            <button class="btn btn-primary btn-sm mt-sm">{{ __('Créer') }}</button><div class="hint">{{ __('Les permissions se règlent après création.') }}</div></div></form>
        <form method="POST" action="{{ route('admin.permissions.store') }}" class="card"><div class="card-head"><h3>{{ __('Nouvelle permission') }}</h3></div><div class="card-body">
            @csrf<input name="name" class="input" placeholder="module.action" required pattern="[a-z_]+\.[a-z_]+" aria-label="{{ __('Nom') }}"><input name="label" class="input mt-sm" placeholder="{{ __('Libellé') }}" required aria-label="{{ __('Libellé') }}">
            <input name="group" class="input mt-sm" placeholder="{{ __('Groupe') }}" value="general" aria-label="{{ __('Groupe') }}"><button class="btn btn-primary btn-sm mt-sm">{{ __('Créer') }}</button></div></form>
    </div>
@endsection
