@extends('settings.layout')
@section('title', __('Langue'))
@php $content = $user->content_prefs['languages'] ?? []; @endphp
@section('settings')
    <form method="POST" action="{{ route('settings.language.update') }}" class="card"><div class="card-body">
        @csrf @method('PUT')
        <h3>{{ __('Langue de l\'interface') }}</h3>
        <div class="stack-sm mb">
            @foreach(config('vwajen.locales') as $code => $l)
                <label class="check"><input type="radio" name="locale" value="{{ $code }}" @checked($user->locale === $code)><span lang="{{ $code }}"><strong>{{ $l['name'] }}</strong></span></label>
            @endforeach
        </div>
        <div class="hint mb">{{ __('Les notifications et e-mails vous sont envoyés dans cette langue.') }}</div>
        <h3>{{ __('Langues des contenus du fil') }}</h3>
        <p class="small muted">{{ __('Laissez tout décoché pour voir toutes les langues. Un bouton « Traduire » est proposé sur les contenus dans une autre langue.') }}</p>
        @foreach(config('vwajen.locales') as $code => $l)
            <label class="check"><input type="checkbox" name="content_languages[]" value="{{ $code }}" @checked(in_array($code, $content))>{{ $l['name'] }}</label>
        @endforeach
        <button class="btn btn-primary mt">{{ __('Enregistrer') }}</button>
    </div></form>
@endsection
