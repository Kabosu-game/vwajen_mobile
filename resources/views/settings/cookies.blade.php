@extends('settings.layout')
@section('title', __('Cookies et consentement'))
@php $p = $user->cookie_prefs ?? []; @endphp
@section('settings')
    <form method="POST" action="{{ route('settings.cookies.update') }}" class="card"><div class="card-body">
        @csrf @method('PUT')
        <h3>{{ __('Gestion des cookies') }}</h3>
        <label class="switch mb"><input type="checkbox" checked disabled><span class="track"></span><span><strong>{{ __('Nécessaires') }}</strong><br><span class="small muted">{{ __('Session, sécurité (CSRF), langue. Indispensables au fonctionnement.') }}</span></span></label><br>
        <label class="switch mb"><input type="checkbox" name="preferences" value="1" @checked($p['preferences'] ?? false)><span class="track"></span><span><strong>{{ __('Préférences') }}</strong><br><span class="small muted">{{ __('Thème, économie de données sur les appareils non connectés.') }}</span></span></label><br>
        <label class="switch mb"><input type="checkbox" name="analytics" value="1" @checked($p['analytics'] ?? false)><span class="track"></span><span><strong>{{ __('Mesure d\'audience anonyme') }}</strong><br><span class="small muted">{{ __('Statistiques agrégées d\'utilisation, sans publicité ni revente.') }}</span></span></label>
        <p class="small muted">{{ __('Vwajèn n\'utilise aucun cookie publicitaire.') }} <a href="{{ route('pages.show', 'cookies') }}">{{ __('Politique cookies') }}</a></p>
        <button class="btn btn-primary">{{ __('Enregistrer mon consentement') }}</button>
    </div></form>
@endsection
