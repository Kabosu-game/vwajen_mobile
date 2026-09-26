@extends('layouts.guest')
@section('title', __('Mot de passe oublié'))
@section('content')
    <div class="card"><div class="card-body">
        <h1 class="center" style="font-size:1.35rem">{{ __('Mot de passe oublié ?') }}</h1>
        <p class="center muted">{{ __('Indiquez votre adresse e-mail : nous vous enverrons un lien de réinitialisation.') }}</p>
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="field">
                <label for="email" class="label">{{ __('Adresse e-mail') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="input @error('email') is-invalid @enderror" required autofocus>
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary btn-block">{{ __('Envoyer le lien') }}</button>
        </form>
    </div></div>
    <p class="center"><a href="{{ route('login') }}">← {{ __('Retour à la connexion') }}</a></p>
@endsection
