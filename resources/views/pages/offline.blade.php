<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ __('Hors ligne') }} · Vwajèn</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body>
<main class="auth-main" style="min-height:100vh">
    <div class="auth-card center">
        <img src="{{ asset('images/mark.png') }}" alt="Vwajèn" width="72" height="72" style="border-radius:18px;margin:0 auto 1rem">
        <x-icon name="wifi-off" style="width:40px;height:40px;margin:0 auto .5rem;color:var(--text-3)"/>
        <h1 style="font-size:1.4rem">{{ __('Vous êtes hors ligne') }}</h1>
        <p class="muted">{{ __('Vérifiez votre connexion. Les pages déjà consultées restent disponibles.') }}</p>
        <button class="btn btn-primary" onclick="location.reload()">{{ __('Réessayer') }}</button>
    </div>
</main>
</body>
</html>
