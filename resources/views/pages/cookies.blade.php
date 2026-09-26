@extends('pages._page')
@section('title', __('Politique cookies'))
@section('page')
    <p>{{ __('Vwajèn utilise un nombre minimal de cookies. Aucun cookie publicitaire ni traceur tiers.') }}</p>
    <table class="table"><thead><tr><th>{{ __('Cookie') }}</th><th>{{ __('Finalité') }}</th><th>{{ __('Durée') }}</th></tr></thead><tbody>
        <tr><td>vwajen_session</td><td>{{ __('Session de connexion (nécessaire)') }}</td><td>{{ __('Session') }}</td></tr>
        <tr><td>XSRF-TOKEN</td><td>{{ __('Protection contre les falsifications de requêtes (nécessaire)') }}</td><td>{{ __('Session') }}</td></tr>
        <tr><td>remember_web_*</td><td>{{ __('« Rester connecté » (si choisi)') }}</td><td>{{ __('5 ans') }}</td></tr>
        <tr><td>locale</td><td>{{ __('Langue choisie') }}</td><td>{{ __('1 an') }}</td></tr>
        <tr><td>theme, data_saver</td><td>{{ __('Préférences d\'affichage') }}</td><td>{{ __('1 an') }}</td></tr>
        <tr><td>cookie_consent</td><td>{{ __('Mémoriser votre choix') }}</td><td>{{ __('1 an') }}</td></tr>
    </tbody></table>
    <p class="mt">{{ __('Le service worker conserve localement les fichiers de l\'application pour accélérer le chargement et fonctionner hors ligne.') }}</p>
    @auth<a href="{{ route('settings.cookies') }}" class="btn btn-primary">{{ __('Gérer mes préférences') }}</a>@endauth
@endsection
