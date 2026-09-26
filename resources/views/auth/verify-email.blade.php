@extends('layouts.app')
@section('title', __('Vérification de l\'e-mail'))
@section('content')
    <div class="card"><div class="card-body center">
        <x-icon name="mail" style="width:48px;height:48px;color:var(--primary);margin:0 auto .6rem"/>
        <h1 style="font-size:1.35rem">{{ __('Vérifiez votre adresse e-mail') }}</h1>
        <p class="muted">{{ __('Nous avons envoyé un lien de vérification à :email. Cliquez dessus pour activer toutes les fonctionnalités.', ['email' => auth()->user()->email]) }}</p>
        <form method="POST" action="{{ route('verification.send') }}" class="mt">@csrf
            <button class="btn btn-primary">{{ __('Renvoyer le lien') }}</button>
        </form>
        <p class="small faint mt">{{ __('Mauvaise adresse ?') }} <a href="{{ route('settings.account') }}">{{ __('Modifier mon e-mail') }}</a></p>
    </div></div>
@endsection
