@extends('layouts.app')
@section('title', $live->exists ? __('Modifier le live') : __('Nouveau live'))
@section('content')
    <div class="page-head"><a href="{{ $live->exists ? $live->url() : route('lives.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a>
        <h1>{{ $live->exists ? __('Modifier le live') : ($live->kind === 'audio' ? __('Ouvrir un Audio Space') : __('Nouveau live')) }}</h1></div>
    <form method="POST" action="{{ $live->exists ? route('lives.update', $live) : route('lives.store') }}" enctype="multipart/form-data" class="card"><div class="card-body">
        @csrf @if($live->exists) @method('PUT') @endif
        <div class="field"><label class="label">{{ __('Type') }}</label>
            <div class="pills">
                <label><input type="radio" name="kind" value="video" class="pill-check" @checked($live->kind !== 'audio')><span class="pill">🎥 {{ __('Live vidéo') }}</span></label>
                <label><input type="radio" name="kind" value="audio" class="pill-check" @checked($live->kind === 'audio')><span class="pill">🎙️ Audio Space</span></label>
            </div></div>
        <div class="field"><label for="title" class="label">{{ __('Titre du live') }}</label><input id="title" name="title" class="input" required maxlength="150" value="{{ old('title', $live->title) }}"></div>
        <div class="field"><label for="description" class="label">{{ __('Description') }}</label><textarea id="description" name="description" class="textarea" maxlength="3000">{{ old('description', $live->description) }}</textarea></div>
        <div class="field"><label for="cover" class="label">{{ __('Image de couverture') }}</label>
            @if($live->coverUrl())<img src="{{ $live->coverUrl() }}" alt="" style="max-height:120px;border-radius:10px;margin-bottom:.4rem">@endif
            <input id="cover" type="file" name="cover" accept="image/*" class="input"></div>
        <div class="field"><label for="scheduled_at" class="label">{{ __('Programmer (facultatif)') }}</label>
            <input id="scheduled_at" type="datetime-local" name="scheduled_at" class="input" value="{{ old('scheduled_at', $live->scheduled_at?->format('Y-m-d\TH:i')) }}">
            <div class="hint">{{ __('Laissez vide pour démarrer maintenant. Vos abonnés seront notifiés et pourront programmer un rappel.') }}</div></div>
        <fieldset><legend>{{ __('Chat en direct') }}</legend>
            <label class="switch mb"><input type="checkbox" name="chat_enabled" value="1" @checked(old('chat_enabled', $live->chat_enabled ?? true))><span class="track"></span>{{ __('Activer le chat') }}</label><br>
            <label class="switch mb"><input type="checkbox" name="chat_followers_only" value="1" @checked(old('chat_followers_only', $live->chat_followers_only))><span class="track"></span>{{ __('Réservé aux abonnés') }}</label>
            <div class="field mt-sm"><label for="slow" class="label">{{ __('Mode lent (secondes entre deux messages)') }}</label><input id="slow" type="number" name="slow_mode_seconds" min="0" max="300" class="input" style="max-width:140px" value="{{ old('slow_mode_seconds', $live->slow_mode_seconds ?? 0) }}"></div>
        </fieldset>
        <fieldset><legend>{{ __('Lieu (pour la carte, facultatif)') }}</legend>
            <div class="grid grid-3">
                <input name="city" class="input" placeholder="{{ __('Ville') }}" value="{{ old('city', $live->city) }}" aria-label="{{ __('Ville') }}">
                <input name="lat" class="input" placeholder="Latitude" value="{{ old('lat', $live->lat) }}" aria-label="Latitude">
                <input name="lng" class="input" placeholder="Longitude" value="{{ old('lng', $live->lng) }}" aria-label="Longitude">
            </div>
        </fieldset>
        <details><summary class="small" style="cursor:pointer">{{ __('Diffusion avancée (OBS / serveur média)') }}</summary>
            <div class="field mt-sm"><label for="playback_url" class="label">{{ __('URL de lecture HLS (.m3u8)') }}</label>
                <input id="playback_url" type="url" name="playback_url" class="input" value="{{ old('playback_url', $live->playback_url) }}" placeholder="https://…/live.m3u8">
                <div class="hint">{{ __('Optionnel : si vous diffusez via un serveur RTMP/HLS, collez l\'URL de lecture. Sinon, la diffusion se fait directement depuis votre navigateur.') }}</div></div>
        </details>
        <div class="row mt" style="justify-content:flex-end"><button class="btn btn-live">{{ $live->exists ? __('Enregistrer') : __('Créer le live') }}</button></div>
    </div></form>
@endsection
