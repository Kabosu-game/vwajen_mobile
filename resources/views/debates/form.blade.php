@extends('layouts.app')
@section('title', $debate->exists ? __('Modifier le débat') : __('Créer un débat'))
@php $selected = old('participants', $debate->exists ? $debate->participants->pluck('user_id')->all() : []); @endphp
@section('content')
    <div class="page-head"><a href="{{ $debate->exists ? $debate->url() : route('debates.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ $debate->exists ? __('Modifier le débat') : __('Programmer un débat') }}</h1></div>
    <form method="POST" action="{{ $debate->exists ? route('debates.update', $debate) : route('debates.store') }}" enctype="multipart/form-data" class="card"><div class="card-body">
        @csrf @if($debate->exists) @method('PUT') @endif
        <div class="field"><label for="title" class="label">{{ __('Titre') }}</label><input id="title" name="title" class="input" required maxlength="200" value="{{ old('title', $debate->title) }}"></div>
        <div class="field"><label for="description" class="label">{{ __('Description') }}</label><textarea id="description" name="description" class="textarea">{{ old('description', $debate->description) }}</textarea></div>
        <div class="grid grid-2">
            <div class="field"><label for="scheduled_at" class="label">{{ __('Date et heure') }}</label><input id="scheduled_at" type="datetime-local" name="scheduled_at" class="input" required value="{{ old('scheduled_at', $debate->scheduled_at?->format('Y-m-d\TH:i')) }}"></div>
            <div class="field"><label for="duration" class="label">{{ __('Durée (minutes)') }}</label><input id="duration" type="number" name="duration_minutes" class="input" min="15" max="600" value="{{ old('duration_minutes', $debate->duration_minutes) }}"></div>
            <div class="field"><label for="category_id" class="label">{{ __('Thème principal') }}</label><select id="category_id" name="category_id" class="select"><option value="">—</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $debate->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div class="field"><label for="constituency" class="label">{{ __('Circonscription (facultatif)') }}</label><input id="constituency" name="constituency" class="input" value="{{ old('constituency', $debate->constituency) }}"></div>
        </div>
        <div class="field"><label for="moderator" class="label">{{ __('Modérateur (nom d\'utilisateur, vous par défaut)') }}</label><input id="moderator" name="moderator" class="input" placeholder="@username" value="{{ old('moderator', $debate->moderator?->username) }}"></div>
        @unless($debate->exists)
            <fieldset><legend>{{ __('Candidats participants (2 à 12)') }}</legend>
                <div class="pills" style="max-height:220px;overflow-y:auto">
                    @foreach($candidates as $c)
                        <label><input type="checkbox" name="participants[]" value="{{ $c->id }}" class="pill-check" @checked(in_array($c->id, $selected))><span class="pill">{{ $c->name }}</span></label>
                    @endforeach
                </div>
                <div class="hint">{{ __('Chaque candidat reçoit une invitation qu\'il doit accepter.') }}</div>
            </fieldset>
        @endunless
        <div class="field"><label for="rules" class="label">{{ __('Règles du débat') }}</label><textarea id="rules" name="rules" class="textarea" rows="3">{{ old('rules', $debate->rules) }}</textarea></div>
        <div class="field"><label for="cover" class="label">{{ __('Image de couverture') }}</label><input id="cover" type="file" name="cover" accept="image/*" class="input"></div>
        <label class="switch mb"><input type="checkbox" name="public_questions" value="1" @checked(old('public_questions', $debate->public_questions))><span class="track"></span>{{ __('Questions du public') }}</label><br>
        <label class="switch mb"><input type="checkbox" name="chat_enabled" value="1" @checked(old('chat_enabled', $debate->chat_enabled))><span class="track"></span>{{ __('Chat en direct') }}</label>
        <details class="mt"><summary class="small" style="cursor:pointer">{{ __('Diffusion via serveur média (HLS)') }}</summary>
            <input type="url" name="playback_url" class="input mt-sm" placeholder="https://…/debate.m3u8" value="{{ old('playback_url', $debate->playback_url) }}" aria-label="HLS"></details>
        <div class="row mt" style="justify-content:flex-end"><button class="btn btn-primary">{{ $debate->exists ? __('Enregistrer') : __('Programmer le débat') }}</button></div>
    </div></form>
@endsection
