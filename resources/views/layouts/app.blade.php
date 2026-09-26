@php
    $me = auth()->user();
    $theme = $me?->theme ?? request()->cookie('theme', 'system');
    $dataSaver = $me?->data_saver ?? request()->cookie('data_saver') === '1';
    $layout = trim($__env->yieldContent('layout'));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
      @if(in_array($theme, ['light', 'dark'])) data-theme="{{ $theme }}" @endif
      data-font="{{ $me?->font_size ?? 'md' }}"
      @if($me?->high_contrast) data-contrast="high" @endif
      @if($me?->reduce_motion) data-motion="reduce" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim($__env->yieldContent('title')) ? trim($__env->yieldContent('title')).' · ' : '' }}Vwajèn</title>
    <meta name="description" content="@yield('description', __('Vwajèn, la plateforme civique et sociale des Haïtiens : publications, candidats, programmes, débats, lives et questions aux candidats.'))">
    <meta name="theme-color" content="#0b1f5c">
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icon-192.png') }}">
    <link rel="manifest" href="{{ route('manifest') }}">
    <meta property="og:site_name" content="Vwajèn">
    <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title')) ?: 'Vwajèn')">
    <meta property="og:description" content="@yield('og_description', __('La voix des citoyens haïtiens'))">
    <meta property="og:image" content="@yield('og_image', asset('images/logo-400.jpg'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="apple-itunes-app" content="app-argument={{ url()->current() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('head')
</head>
<body class="{{ $dataSaver ? 'data-saver' : '' }}">
<a class="skip-link" href="#main">{{ __('Aller au contenu') }}</a>

{{-- Barre supérieure mobile --}}
<header class="mobile-top">
    <a href="{{ route('home') }}" class="row" aria-label="Vwajèn — {{ __('Accueil') }}">
        <img src="{{ asset('images/icon-64.png') }}" alt="" width="32" height="32" style="border-radius:8px">
        <strong>Vwajèn</strong>
    </a>
    <div class="row">
        <a href="{{ route('search.index') }}" class="btn btn-ghost btn-icon" aria-label="{{ __('Rechercher') }}"><x-icon name="search"/></a>
        @auth
            <a href="{{ route('messages.index') }}" class="btn btn-ghost btn-icon" aria-label="{{ __('Messages') }}" style="position:relative">
                <x-icon name="mail"/>@if($unreadMessages)<span class="nav-count" data-count="messages" style="left:20px;top:0">{{ $unreadMessages }}</span>@endif
            </a>
            <a href="{{ $me->profileUrl() }}" aria-label="{{ __('Mon profil') }}"><img src="{{ $me->avatarUrl() }}" alt="" class="avatar avatar-sm"></a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm">{{ __('Connexion') }}</a>
        @endauth
    </div>
</header>

<div class="app {{ $layout }}">
    <aside class="sidebar-left" aria-label="{{ __('Navigation principale') }}">
        @include('partials.nav')
    </aside>

    <main id="main" class="main" tabindex="-1">
        @include('partials.flash')
        @yield('content')
    </main>

    @if(! in_array($layout, ['wide', 'full']))
        <aside class="sidebar-right" aria-label="{{ __('Informations complémentaires') }}">
            @section('right')
                @include('partials.right')
            @show
        </aside>
    @endif
</div>

{{-- Navigation mobile --}}
<nav class="mobile-nav" aria-label="{{ __('Navigation mobile') }}">
    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}"><x-icon name="home"/>{{ __('Accueil') }}</a>
    <a href="{{ route('discover.index') }}" class="{{ request()->routeIs('discover.*') ? 'active' : '' }}"><x-icon name="compass"/>{{ __('Découvrir') }}</a>
    <a href="{{ route('shorts.index') }}" class="{{ request()->routeIs('shorts.*') ? 'active' : '' }}"><x-icon name="shorts"/>Shorts</a>
    <a href="{{ route('candidates.index') }}" class="{{ request()->routeIs('candidates.*') ? 'active' : '' }}"><x-icon name="vote"/>{{ __('Candidats') }}</a>
    @auth
        <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}"><x-icon name="bell"/>{{ __('Alertes') }}
            <span class="nav-count" data-count="notifications" @if(! $unreadNotifications) hidden @endif>{{ $unreadNotifications }}</span></a>
    @else
        <a href="{{ route('login') }}"><x-icon name="login"/>{{ __('Connexion') }}</a>
    @endauth
</nav>
@auth
    <button type="button" class="fab" data-modal-open="compose-modal" aria-label="{{ __('Publier une Vwa') }}"><x-icon name="plus"/></button>
@endauth

@include('partials.modals')

<div class="toasts" id="toasts" role="status" aria-live="polite"></div>
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="{{ __('Image') }}">
    <button type="button" class="btn btn-dark btn-icon" data-lightbox-close aria-label="{{ __('Fermer') }}"><x-icon name="x"/></button>
    <img src="" alt="">
</div>

@if(! request()->cookie('cookie_consent') && ! $me?->cookie_consent_at)
    <div class="cookie-banner" id="cookie-banner" role="dialog" aria-label="{{ __('Cookies') }}">
        <div class="row top"><x-icon name="cookie"/>
            <div class="grow">
                <strong>{{ __('Vwajèn respecte votre vie privée') }}</strong>
                <p class="small muted mb-0">{{ __('Nous utilisons des cookies nécessaires au fonctionnement du site (session, sécurité, langue). Avec votre accord, nous mémorisons aussi vos préférences d\'affichage.') }}
                    <a href="{{ route('pages.show', 'cookies') }}">{{ __('En savoir plus') }}</a></p>
            </div>
        </div>
        <div class="row mt-sm" style="justify-content:flex-end">
            <button class="btn btn-outline btn-sm" data-cookie="necessary">{{ __('Nécessaires uniquement') }}</button>
            <button class="btn btn-primary btn-sm" data-cookie="all">{{ __('Tout accepter') }}</button>
        </div>
    </div>
@endif

<script>
    window.Vwajen = {!! json_encode([
        'csrf' => csrf_token(),
        'locale' => app()->getLocale(),
        'user' => $me ? ['id' => $me->id, 'username' => $me->username, 'name' => $me->name, 'avatar' => $me->avatarUrl()] : null,
        'dataSaver' => (bool) $dataSaver,
        'reduceAutoplay' => (bool) ($me?->reduce_autoplay || $dataSaver),
        'vapid' => config('vwajen.vapid.public'),
        'routes' => [
            'login' => route('login'),
            'notificationsCount' => $me ? route('notifications.count') : null,
            'pushSubscribe' => route('push.subscribe'),
            'suggest' => route('search.suggest'),
            'cookies' => route('cookies.consent'),
            'uploads' => route('uploads.init'),
            'linkPreview' => route('posts.preview'),
        ],
        'i18n' => [
            'copied' => __('Lien copié !'),
            'error' => __('Une erreur est survenue. Réessayez.'),
            'loginRequired' => __('Connectez-vous pour continuer.'),
            'confirm' => __('Êtes-vous sûr ?'),
            'offline' => __('Vous êtes hors ligne. Certaines fonctions sont indisponibles.'),
            'online' => __('Connexion rétablie.'),
            'uploading' => __('Téléversement…'),
            'uploadResumed' => __('Reprise du téléversement…'),
            'uploadDone' => __('Téléversement terminé.'),
            'uploadFailed' => __('Échec du téléversement. Il reprendra automatiquement.'),
            'follow' => __('Suivre'),
            'following' => __('Abonné'),
            'unfollow' => __('Ne plus suivre'),
            'requested' => __('Demandé'),
            'newNotification' => __('Nouvelle notification'),
            'translating' => __('Traduction…'),
            'showOriginal' => __('Voir l\'original'),
            'loading' => __('Chargement…'),
            'hidden' => __('Contenu masqué.'),
            'pushEnabled' => __('Notifications push activées.'),
            'pushDenied' => __('Notifications refusées par le navigateur.'),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};
</script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
