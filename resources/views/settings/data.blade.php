@extends('settings.layout')
@section('title', __('Données personnelles'))
@section('settings')
    <div class="card"><div class="card-head"><h3>{{ __('Export des données') }}</h3></div><div class="card-body">
        <p class="small muted">{{ __('Téléchargez une archive ZIP contenant toutes vos données : profil, publications, commentaires, likes, abonnements, messages envoyés, notifications, appareils, et vos photos/vidéos.') }}</p>
        <form method="POST" action="{{ route('settings.export') }}">@csrf<button class="btn btn-primary"><x-icon name="download"/>{{ __('Exporter mes données') }}</button></form>
    </div></div>
    <div class="card"><div class="card-head"><h3>{{ __('Gestion des données personnelles') }}</h3></div><div class="card-body small">
        <dl class="kv">
            <dt>{{ __('Compte créé le') }}</dt><dd>{{ $user->created_at->translatedFormat('d F Y') }}</dd>
            <dt>{{ __('Conditions acceptées') }}</dt><dd>{{ $user->terms_accepted_at?->translatedFormat('d F Y') ?? '—' }}</dd>
            <dt>{{ __('Consentement cookies') }}</dt><dd>{{ $user->cookie_consent_at?->translatedFormat('d F Y') ?? __('Non donné') }} — <a href="{{ route('settings.cookies') }}">{{ __('Modifier') }}</a></dd>
            <dt>{{ __('Données visibles') }}</dt><dd><a href="{{ route('settings.privacy') }}">{{ __('Paramètres de confidentialité') }}</a></dd>
        </dl>
        <p class="mt"><a href="{{ route('pages.show', 'privacy') }}">{{ __('Politique de confidentialité') }}</a> · <a href="{{ route('pages.show', 'sensitive-data') }}">{{ __('Données sensibles') }}</a></p>
    </div></div>
    <form method="POST" action="{{ route('settings.account.destroy') }}" class="card" style="border-color:var(--danger)" data-confirm="{{ __('Êtes-vous sûr de vouloir supprimer votre compte ?') }}"><div class="card-head"><h3 style="color:var(--danger)">{{ __('Suppression du compte') }}</h3></div><div class="card-body">
        @csrf @method('DELETE')
        <p class="small">{{ __('Votre compte sera immédiatement désactivé et masqué, puis définitivement supprimé (avec vos médias) après :days jours. Vous pouvez annuler en vous reconnectant pendant ce délai.', ['days' => config('vwajen.account_purge_days')]) }}</p>
        @if($user->password)<div class="field"><label class="label" for="dpw">{{ __('Mot de passe') }}</label><input id="dpw" type="password" name="password" class="input" required autocomplete="current-password"></div>@endif
        <div class="field"><label class="label" for="conf">{{ __('Tapez SUPPRIMER pour confirmer') }}</label><input id="conf" name="confirmation" class="input" required pattern="SUPPRIMER|DELETE|EFASE" autocomplete="off"></div>
        <button class="btn btn-danger"><x-icon name="trash"/>{{ __('Supprimer mon compte') }}</button>
    </div></form>
@endsection
