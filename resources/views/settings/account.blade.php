@extends('settings.layout')
@section('title', __('Compte et sécurité'))
@section('settings')
    <form method="POST" action="{{ route('settings.account.update') }}" class="card"><div class="card-head"><h3>{{ __('Informations du compte') }}</h3></div><div class="card-body">
        @csrf @method('PUT')
        <div class="field"><label class="label" for="username">{{ __('Nom d\'utilisateur') }}</label><input id="username" name="username" class="input" required value="{{ old('username', $user->username) }}" pattern="[A-Za-z0-9_]+">
            <div class="hint">{{ route('profile.show', $user->username) }}</div></div>
        <div class="field"><label class="label" for="email">{{ __('Adresse e-mail') }}</label><input id="email" type="email" name="email" class="input" required value="{{ old('email', $user->email) }}">
            <div class="hint">@if($user->hasVerifiedEmail())<span style="color:var(--success)">✓ {{ __('Vérifiée') }}</span>@else<span style="color:var(--warning)">{{ __('Non vérifiée') }}</span> — <a href="{{ route('verification.notice') }}">{{ __('Vérifier') }}</a>@endif</div></div>
        @if($user->password)<div class="field"><label class="label" for="cp">{{ __('Mot de passe actuel (pour confirmer)') }}</label><input id="cp" type="password" name="current_password" class="input" required autocomplete="current-password"></div>@endif
        <button class="btn btn-primary">{{ __('Enregistrer') }}</button>
    </div></form>

    <div class="card"><div class="card-head"><h3>{{ __('Téléphone') }}</h3></div><div class="card-body row between wrap">
        <div>{{ $user->phone ?: __('Aucun numéro') }} @if($user->phone_verified_at)<span class="badge badge-success">{{ __('Vérifié') }}</span>@elseif($user->phone)<span class="badge badge-warning">{{ __('Non vérifié') }}</span>@endif</div>
        <a href="{{ route('phone.verify') }}" class="btn btn-outline btn-sm">{{ $user->phone ? __('Vérifier / modifier') : __('Ajouter un numéro') }}</a>
    </div></div>

    <form method="POST" action="{{ route('settings.password.update') }}" class="card"><div class="card-head"><h3>{{ $user->password ? __('Changer le mot de passe') : __('Définir un mot de passe') }}</h3></div><div class="card-body">
        @csrf @method('PUT')
        @if($user->password)<div class="field"><label class="label" for="current_password">{{ __('Mot de passe actuel') }}</label><input id="current_password" type="password" name="current_password" class="input" required autocomplete="current-password"></div>@endif
        <div class="grid grid-2">
            <div class="field"><label class="label" for="password">{{ __('Nouveau mot de passe') }}</label><input id="password" type="password" name="password" class="input" required autocomplete="new-password"></div>
            <div class="field"><label class="label" for="password_confirmation">{{ __('Confirmer') }}</label><input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password"></div>
        </div>
        <button class="btn btn-primary">{{ __('Mettre à jour') }}</button>
    </div></form>

    <div class="card"><div class="card-head"><h3>{{ __('Connexions externes') }}</h3></div><div class="card-body">
        <div class="row mb"><x-icon name="google"/><span class="grow">Google</span>{!! $user->google_id ? '<span class="badge badge-success">'.e(__('Connecté')).'</span>' : (config('services.google.client_id') ? '<a href="'.route('social.redirect', 'google').'" class="btn btn-outline btn-sm">'.e(__('Connecter')).'</a>' : '<span class="faint small">'.e(__('Non configuré')).'</span>') !!}</div>
        <div class="row"><x-icon name="apple"/><span class="grow">Apple</span>{!! $user->apple_id ? '<span class="badge badge-success">'.e(__('Connecté')).'</span>' : (config('services.apple.client_id') ? '<a href="'.route('social.redirect', 'apple').'" class="btn btn-outline btn-sm">'.e(__('Connecter')).'</a>' : '<span class="faint small">'.e(__('Non configuré')).'</span>') !!}</div>
    </div></div>
@endsection
