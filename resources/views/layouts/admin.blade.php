@php
    $me = auth()->user();
    $is = fn (...$p) => request()->routeIs(...$p) ? 'active' : '';
    $counts = [
        'reports' => \App\Models\Report::where('status', 'pending')->count(),
        'verifications' => \App\Models\VerificationRequest::where('status', 'pending')->count(),
        'appeals' => \App\Models\Appeal::where('status', 'pending')->count(),
    ];
    $can = fn ($p) => $me->hasPermission($p);
    $theme = $me->theme;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" @if(in_array($theme, ['light', 'dark'])) data-theme="{{ $theme }}" @endif data-font="{{ $me->font_size }}" @if($me->high_contrast) data-contrast="high" @endif>
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ __('Administration') }} Vwajèn</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<a class="skip-link" href="#main">{{ __('Aller au contenu') }}</a>
<div class="admin-shell">
    <nav class="admin-nav" id="admin-nav" aria-label="{{ __('Navigation de l\'administration') }}">
        <a href="{{ route('admin.dashboard') }}" class="brand" style="padding:.3rem .5rem"><img src="{{ asset('images/mark.png') }}" alt="" width="32" height="32"><span>Vwajèn Admin</span></a>
        <a href="{{ route('admin.dashboard') }}" class="{{ $is('admin.dashboard') }}"><x-icon name="dashboard"/>{{ __('Vue générale') }}</a>
        @if($can('stats.view'))<a href="{{ route('admin.stats') }}" class="{{ $is('admin.stats') }}"><x-icon name="chart"/>{{ __('Statistiques') }}</a>@endif

        <div class="nav-section">{{ __('Comptes') }}</div>
        @if($can('users.view'))<a href="{{ route('admin.users.index') }}" class="{{ $is('admin.users.*') }}"><x-icon name="users"/>{{ __('Utilisateurs') }}</a>@endif
        @if($can('candidates.manage'))
            <a href="{{ route('admin.candidates.index') }}" class="{{ $is('admin.candidates.*') }}"><x-icon name="vote"/>{{ __('Candidats') }}</a>
            <a href="{{ route('admin.organizations.index') }}" class="{{ $is('admin.organizations.*') }}"><x-icon name="briefcase"/>{{ __('Organisations') }}</a>
            <a href="{{ route('admin.officials.index') }}" class="{{ $is('admin.officials.*') }}"><x-icon name="landmark"/>{{ __('Élus') }}</a>
        @endif
        @if($can('verifications.manage'))
            <a href="{{ route('admin.verifications.index') }}" class="{{ $is('admin.verifications.*') }}"><x-icon name="badge-check"/>{{ __('Vérifications') }}@if($counts['verifications'])<span class="count">{{ $counts['verifications'] }}</span>@endif</a>
            <a href="{{ route('admin.sources.index') }}" class="{{ $is('admin.sources.*') }}"><x-icon name="link"/>{{ __('Sources') }}</a>
        @endif

        @if($can('moderation.manage'))
            <div class="nav-section">{{ __('Modération') }}</div>
            <a href="{{ route('admin.reports.index') }}" class="{{ $is('admin.reports.*') }}"><x-icon name="flag"/>{{ __('Signalements') }}@if($counts['reports'])<span class="count">{{ $counts['reports'] }}</span>@endif</a>
            <a href="{{ route('admin.sanctions.index') }}" class="{{ $is('admin.sanctions.*') }}"><x-icon name="gavel"/>{{ __('Sanctions') }}</a>
            <a href="{{ route('admin.appeals.index') }}" class="{{ $is('admin.appeals.*') }}"><x-icon name="scale"/>{{ __('Appels') }}@if($counts['appeals'])<span class="count">{{ $counts['appeals'] }}</span>@endif</a>
            <div class="nav-section">{{ __('Contenus') }}</div>
            @foreach(['posts' => ['message', __('Publications')], 'comments' => ['comment', __('Commentaires')], 'videos' => ['film', __('Vidéos')], 'shorts' => ['shorts', 'Shorts'], 'lives' => ['live', __('Lives')],
                'debates' => ['debate', __('Débats')], 'questions' => ['question', __('Questions')], 'answers' => ['check-circle', __('Réponses')], 'events' => ['calendar', __('Événements')],
                'communities' => ['users', __('Communautés')], 'discussions' => ['debate', __('Discussions')], 'podcasts' => ['podcast', __('Podcasts')]] as $t => [$i, $l])
                <a href="{{ route('admin.content.index', $t) }}" class="{{ request()->is('admin/content/'.$t) ? 'active' : '' }}"><x-icon name="{{ $i }}"/>{{ $l }}</a>
            @endforeach
            <a href="{{ route('admin.hashtags.index') }}" class="{{ $is('admin.hashtags.*') }}"><x-icon name="hash"/>{{ __('Hashtags') }}</a>
        @endif

        <div class="nav-section">{{ __('Système') }}</div>
        @if($can('audit.view'))<a href="{{ route('admin.audit') }}" class="{{ $is('admin.audit') }}"><x-icon name="history"/>{{ __('Audit') }}</a>@endif
        @if($can('roles.manage'))<a href="{{ route('admin.roles.index') }}" class="{{ $is('admin.roles.*') }}"><x-icon name="key"/>{{ __('Rôles et permissions') }}</a>@endif
        @if($can('settings.manage'))
            <a href="{{ route('admin.settings') }}" class="{{ $is('admin.settings*') }}"><x-icon name="settings"/>{{ __('Paramètres') }}</a>
            <a href="{{ route('admin.categories.index') }}" class="{{ $is('admin.categories.*') }}"><x-icon name="layers"/>{{ __('Catégories') }}</a>
            <a href="{{ route('admin.languages.index') }}" class="{{ $is('admin.languages.*') }}"><x-icon name="translate"/>{{ __('Langues') }}</a>
            <a href="{{ route('admin.announcements.index') }}" class="{{ $is('admin.announcements.*') }}"><x-icon name="bell"/>{{ __('Notifications système') }}</a>
            <a href="{{ route('admin.newsletter.index') }}" class="{{ $is('admin.newsletter.*') }}"><x-icon name="newspaper"/>{{ __('Newsletter') }}</a>
            <a href="{{ route('admin.ambassadors.index') }}" class="{{ $is('admin.ambassadors.*') }}"><x-icon name="award"/>{{ __('Ambassadeurs') }}</a>
        @endif
        @if($can('elections.manage'))<a href="{{ route('admin.elections.index') }}" class="{{ $is('admin.elections.*') }}"><x-icon name="archive"/>{{ __('Élections') }}</a>@endif
        @if($can('system.monitor'))<a href="{{ route('admin.monitoring') }}" class="{{ $is('admin.monitoring') }}"><x-icon name="activity"/>{{ __('Monitoring') }}</a>@endif
        <hr style="border-color:rgba(255,255,255,.1)">
        <a href="{{ route('home') }}"><x-icon name="arrow-left"/>{{ __('Retour au site') }}</a>
    </nav>
    <main class="admin-main" id="main" tabindex="-1">
        <div class="admin-top">
            <button type="button" class="btn btn-outline btn-icon admin-burger" onclick="document.getElementById('admin-nav').classList.toggle('open')" aria-label="{{ __('Menu') }}"><x-icon name="menu"/></button>
            <h1>@yield('title')</h1>
            <div class="actions">@yield('actions')
                <nav class="lang-switch" aria-label="{{ __('Langue') }}">@foreach(config('vwajen.locales') as $code => $l)<a href="{{ route('locale.switch', $code) }}" class="{{ app()->getLocale() === $code ? 'active' : '' }}" lang="{{ $code }}" hreflang="{{ $code }}" title="{{ $l['name'] }}">{{ $l['short'] }}</a>@endforeach</nav>
                <span class="row small muted"><img src="{{ $me->avatarUrl() }}" class="avatar avatar-xs" alt="">{{ $me->name }} · {{ $me->roles->pluck('label')->implode(', ') }}</span></div>
        </div>
        @include('partials.flash')
        @yield('content')
    </main>
</div>
<div class="toasts" id="toasts" role="status" aria-live="polite"></div>
<script>window.Vwajen = {!! json_encode(['csrf' => csrf_token(), 'locale' => app()->getLocale(), 'user' => ['id' => $me->id], 'i18n' => ['copied' => __('Lien copié !'), 'error' => __('Une erreur est survenue. Réessayez.')], 'routes' => ['login' => route('login'), 'suggest' => route('search.suggest'), 'cookies' => route('cookies.consent')]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};
</script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
