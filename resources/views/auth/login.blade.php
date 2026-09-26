@extends('layouts.guest')
@section('title', __('Connexion'))
@section('content')
    <div class="card"><div class="card-body">
        <h1 class="center" style="font-size:1.4rem">{{ __('Bon retour !') }}</h1>
        <p class="center muted">{{ __('Connectez-vous à votre compte Vwajèn') }}</p>

        <div class="social-btns">
            @if(config('services.google.client_id'))
                <a href="{{ route('social.redirect', 'google') }}" class="btn btn-outline btn-block"><x-icon name="google"/>{{ __('Continuer avec Google') }}</a>
            @endif
            @if(config('services.apple.client_id'))
                <a href="{{ route('social.redirect', 'apple') }}" class="btn btn-dark btn-block"><x-icon name="apple"/>{{ __('Continuer avec Apple') }}</a>
            @endif
        </div>
        @if(config('services.google.client_id') || config('services.apple.client_id'))<div class="or">{{ __('ou') }}</div>@endif

        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf
            <div class="field">
                <label for="login" class="label">{{ __('E-mail, téléphone ou nom d\'utilisateur') }}</label>
                <input id="login" name="login" value="{{ old('login') }}" class="input @error('login') is-invalid @enderror" required autofocus autocomplete="username">
                @error('login')<div class="error" role="alert">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <div class="row between"><label for="password" class="label">{{ __('Mot de passe') }}</label>
                    <a href="{{ route('password.request') }}" class="small">{{ __('Mot de passe oublié ?') }}</a></div>
                <input id="password" type="password" name="password" class="input" required autocomplete="current-password">
            </div>
            <label class="check"><input type="checkbox" name="remember" value="1"> {{ __('Rester connecté') }}</label>
            <button class="btn btn-brand btn-lg btn-block">{{ __('Se connecter') }}</button>
        </form>
    </div></div>
    <p class="center">{{ __('Pas encore de compte ?') }} <a href="{{ route('register') }}"><b>{{ __('Créer un compte') }}</b></a></p>
@endsection
