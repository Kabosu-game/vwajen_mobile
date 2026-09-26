@extends('layouts.guest')
@section('title', __('Créer un compte'))
@section('content')
    <div class="card"><div class="card-body">
        <h1 class="center" style="font-size:1.4rem">{{ __('Rejoignez Vwajèn') }}</h1>
        <p class="center muted">{{ __('Votre voix compte. Inscription gratuite.') }}</p>

        <div class="social-btns">
            @if(config('services.google.client_id'))<a href="{{ route('social.redirect', 'google') }}" class="btn btn-outline btn-block"><x-icon name="google"/>{{ __('S\'inscrire avec Google') }}</a>@endif
            @if(config('services.apple.client_id'))<a href="{{ route('social.redirect', 'apple') }}" class="btn btn-dark btn-block"><x-icon name="apple"/>{{ __('S\'inscrire avec Apple') }}</a>@endif
        </div>
        @if(config('services.google.client_id') || config('services.apple.client_id'))<div class="or">{{ __('ou') }}</div>@endif

        <form method="POST" action="{{ route('register') }}" novalidate>
            @csrf
            <input type="hidden" name="_form_ts" value="{{ time() }}">
            <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></div>

            <fieldset>
                <legend>{{ __('Type de compte') }}</legend>
                <div class="pills">
                    @foreach(['personal' => __('Citoyen'), 'candidate' => __('Candidat'), 'organization' => __('Organisation')] as $val => $label)
                        <label><input type="radio" name="account_type" value="{{ $val }}" class="pill-check" @checked(old('account_type', 'personal') === $val)><span class="pill">{{ $label }}</span></label>
                    @endforeach
                </div>
                <div class="hint">{{ __('Les candidats et organisations pourront demander la vérification de leur compte.') }}</div>
            </fieldset>

            <div class="field">
                <label for="name" class="label">{{ __('Nom complet') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" class="input @error('name') is-invalid @enderror" required maxlength="80" autocomplete="name">
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="username" class="label">{{ __('Nom d\'utilisateur') }}</label>
                <div class="input-group"><span class="btn btn-outline" style="border-radius:9px 0 0 9px;pointer-events:none">@</span>
                    <input id="username" name="username" value="{{ old('username') }}" class="input @error('username') is-invalid @enderror" required maxlength="30" pattern="[A-Za-z0-9_]+" autocomplete="username" style="border-radius:0 9px 9px 0"></div>
                <div class="hint">{{ __('Lettres, chiffres et _ uniquement.') }}</div>
                @error('username')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="email" class="label">{{ __('Adresse e-mail') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="input @error('email') is-invalid @enderror" required autocomplete="email">
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="phone" class="label">{{ __('Téléphone') }} <span class="faint">({{ __('facultatif') }})</span></label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="input @error('phone') is-invalid @enderror" placeholder="+509 3X XX XX XX" autocomplete="tel">
                @error('phone')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="grid grid-2">
                <div class="field">
                    <label for="country" class="label">{{ __('Pays de résidence') }}</label>
                    <select id="country" name="country" class="select">
                        @foreach(all_countries() as $code => $name)<option value="{{ $code }}" @selected(old('country', 'HT') === $code)>{{ $name }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="locale" class="label">{{ __('Langue') }}</label>
                    <select id="locale" name="locale" class="select">
                        @foreach(config('vwajen.locales') as $code => $l)<option value="{{ $code }}" @selected(old('locale', app()->getLocale()) === $code)>{{ $l['name'] }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="password" class="label">{{ __('Mot de passe') }}</label>
                <input id="password" type="password" name="password" class="input @error('password') is-invalid @enderror" required autocomplete="new-password" minlength="8">
                <div class="hint">{{ __('8 caractères minimum, avec majuscule, minuscule et chiffre.') }}</div>
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password_confirmation" class="label">{{ __('Confirmer le mot de passe') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
            </div>
            <label class="check"><input type="checkbox" name="terms" value="1" required @checked(old('terms'))>
                <span class="small">{!! __('J\'accepte les :terms et la :privacy.', ['terms' => '<a href="'.route('pages.show', 'terms').'" target="_blank">'.e(__('conditions d\'utilisation')).'</a>', 'privacy' => '<a href="'.route('pages.show', 'privacy').'" target="_blank">'.e(__('politique de confidentialité')).'</a>']) !!}</span></label>
            @error('terms')<div class="error">{{ $message }}</div>@enderror
            <button class="btn btn-brand btn-lg btn-block mt">{{ __('Créer mon compte') }}</button>
        </form>
    </div></div>
    <p class="center">{{ __('Déjà inscrit ?') }} <a href="{{ route('login') }}"><b>{{ __('Connexion') }}</b></a></p>
@endsection
