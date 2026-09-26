@php $theme = request()->cookie('theme', 'system'); @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" @if(in_array($theme, ['light', 'dark'])) data-theme="{{ $theme }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Vwajèn</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <link rel="manifest" href="{{ route('manifest') }}">
    <meta name="theme-color" content="#0b1f5c">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<a class="skip-link" href="#main">{{ __('Aller au contenu') }}</a>
<div class="auth-wrap">
    <aside class="auth-side" aria-hidden="true">
        <img src="{{ asset('images/mark.png') }}" alt="" width="84" height="84" style="border-radius:20px;margin-bottom:1.2rem;box-shadow:0 10px 40px rgba(0,0,0,.35)">
        <h1>Vwajèn</h1>
        <p>{{ __('La voix des citoyens haïtiens, en Haïti et dans la diaspora.') }}</p>
        <ul class="features">
            <li><x-icon name="message"/>{{ __('Publiez, débattez et suivez l\'actualité civique') }}</li>
            <li><x-icon name="vote"/>{{ __('Comparez les programmes des candidats, sources à l\'appui') }}</li>
            <li><x-icon name="question"/>{{ __('Posez vos questions directement aux candidats') }}</li>
            <li><x-icon name="live"/>{{ __('Suivez les débats et lives en direct') }}</li>
            <li><x-icon name="globe"/>{{ __('Kreyòl, français, anglais — même avec une faible connexion') }}</li>
        </ul>
    </aside>
    <main class="auth-main" id="main">
        <div class="auth-card">
            <a href="{{ route('home') }}" class="brand"><img src="{{ asset('images/mark.png') }}" alt="" width="40" height="40"><span>Vwajèn</span></a>
            @include('partials.flash', ['hideErrors' => true])
            @yield('content')
            <div class="lang-switch mt" style="justify-content:center">
                @foreach(config('vwajen.locales') as $code => $l)
                    <a href="{{ route('locale.switch', $code) }}" class="{{ app()->getLocale() === $code ? 'active' : '' }}" lang="{{ $code }}">{{ $l['name'] }}</a>
                @endforeach
            </div>
            <p class="center small faint mt"><a href="{{ route('pages.show', 'privacy') }}" class="faint">{{ __('Confidentialité') }}</a> · <a href="{{ route('pages.show', 'terms') }}" class="faint">{{ __('Conditions') }}</a> · <a href="{{ route('home') }}" class="faint">{{ __('Explorer sans compte') }}</a></p>
        </div>
    </main>
</div>
<script>window.Vwajen = {!! json_encode(['csrf' => csrf_token(), 'locale' => app()->getLocale(), 'user' => null, 'i18n' => [], 'routes' => ['login' => route('login'), 'suggest' => route('search.suggest'), 'cookies' => route('cookies.consent')]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};
</script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
