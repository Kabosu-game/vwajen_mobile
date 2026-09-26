@extends('layouts.guest')
@section('title', __('Réinitialiser le mot de passe'))
@section('content')
    <div class="card"><div class="card-body">
        <h1 class="center" style="font-size:1.35rem">{{ __('Nouveau mot de passe') }}</h1>
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field">
                <label for="email" class="label">{{ __('Adresse e-mail') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="input @error('email') is-invalid @enderror" required>
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password" class="label">{{ __('Nouveau mot de passe') }}</label>
                <input id="password" type="password" name="password" class="input @error('password') is-invalid @enderror" required autocomplete="new-password">
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password_confirmation" class="label">{{ __('Confirmer le mot de passe') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
            </div>
            <button class="btn btn-primary btn-block">{{ __('Réinitialiser') }}</button>
        </form>
    </div></div>
@endsection
