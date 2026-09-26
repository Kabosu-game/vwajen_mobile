<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · Vwajèn</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="auth-main" style="min-height:100vh" id="main">
    <div class="auth-card center">
        <a href="{{ url('/') }}"><img src="{{ asset('images/mark.png') }}" alt="Vwajèn" width="72" height="72" style="border-radius:18px;margin:0 auto 1rem"></a>
        <div class="gradient-text" style="font-size:3.5rem;font-weight:900;line-height:1">@yield('code')</div>
        <h1 style="font-size:1.35rem;margin-top:.5rem">@yield('heading')</h1>
        <p class="muted">@yield('message')</p>
        <div class="row" style="justify-content:center">
            <a href="{{ url('/') }}" class="btn btn-primary">{{ __('Retour à l\'accueil') }}</a>
            <button type="button" class="btn btn-outline" onclick="history.back()">{{ __('Page précédente') }}</button>
        </div>
    </div>
</main>
</body>
</html>
