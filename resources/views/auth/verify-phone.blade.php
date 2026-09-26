@extends('layouts.app')
@section('title', __('Vérification du téléphone'))
@section('content')
    <div class="page-head"><a href="{{ route('settings.account') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Vérification du téléphone') }}</h1></div>
    <div class="card"><div class="card-body">
        @if($user->phone_verified_at)
            <div class="alert alert-success"><x-icon name="check-circle"/><div>{{ __('Votre numéro :phone est vérifié.', ['phone' => $user->phone]) }}</div></div>
        @endif
        <form method="POST" action="{{ route('phone.send') }}" class="mb">@csrf
            <label for="phone" class="label">{{ __('Numéro de téléphone') }}</label>
            <div class="input-group">
                <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="input" placeholder="+509 3X XX XX XX" required>
                <button class="btn btn-primary">{{ __('Recevoir un code') }}</button>
            </div>
            @error('phone')<div class="error">{{ $message }}</div>@enderror
            <div class="hint">{{ __('Un code à 6 chiffres vous sera envoyé par SMS.') }}</div>
        </form>
        @if(session('code_sent') || $user->phone_code)
            <form method="POST" action="{{ route('phone.confirm') }}">@csrf
                <label for="code" class="label">{{ __('Code reçu') }}</label>
                <div class="input-group">
                    <input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="input" required autocomplete="one-time-code" style="letter-spacing:.4em;font-size:1.2rem">
                    <button class="btn btn-success">{{ __('Vérifier') }}</button>
                </div>
                @error('code')<div class="error">{{ $message }}</div>@enderror
            </form>
        @endif
    </div></div>
@endsection
