@extends('layouts.app')
@section('layout', 'wide')
@php
    $links = [
        'settings.profile' => ['user', __('Profil')], 'settings.account' => ['key', __('Compte et sécurité')], 'settings.privacy' => ['lock', __('Confidentialité')],
        'settings.notifications' => ['bell', __('Notifications')], 'settings.accessibility' => ['accessibility', __('Affichage et accessibilité')],
        'settings.language' => ['translate', __('Langue')], 'settings.interests' => ['sparkles', __('Personnalisation')], 'settings.sessions' => ['monitor', __('Sessions')],
        'settings.devices' => ['phone', __('Appareils')], 'settings.blocked' => ['ban', __('Comptes bloqués')], 'settings.sanctions' => ['gavel', __('Sanctions et appels')],
        'settings.data' => ['database', __('Données personnelles')], 'settings.cookies' => ['cookie', __('Cookies et consentement')], 'settings.api-keys' => ['code', __('Clés API')],
    ];
@endphp
@section('content')
    <div class="page-head"><h1>{{ __('Paramètres') }}</h1></div>
    <div class="side-layout">
        <nav class="card settings-nav" style="padding:.4rem" aria-label="{{ __('Sections des paramètres') }}">
            @foreach($links as $route => [$icon, $label])
                <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'active' : '' }}" @if(request()->routeIs($route)) aria-current="page" @endif><x-icon name="{{ $icon }}"/>{{ $label }}</a>
            @endforeach
            <a href="{{ route('verification.index') }}"><x-icon name="badge-check"/>{{ __('Vérification') }}</a>
        </nav>
        <div>@yield('settings')</div>
    </div>
@endsection
