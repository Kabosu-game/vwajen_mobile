@extends('layouts.app')
@section('title', __('Gérer la vidéo'))
@section('content')
    <div class="page-head"><a href="{{ $video->url() }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Gérer la vidéo') }}</h1></div>
    <form method="POST" action="{{ route('videos.update', $video) }}" enctype="multipart/form-data" class="card"><div class="card-body">
        @csrf @method('PUT')
        <div class="row top mb">
            @if($video->thumbnailUrl())<img src="{{ $video->thumbnailUrl() }}" alt="" style="width:160px;border-radius:10px">@endif
            <div class="small muted">{{ __('Durée') }} : {{ $video->durationLabel() ?: '—' }}<br>{{ __('Qualités') }} : {{ collect($video->sources())->keys()->implode(', ') }}<br>{{ __('Statut') }} : {{ __($video->processing_status) }}</div>
        </div>
        <div class="field"><label for="title" class="label">{{ __('Titre') }}</label><input id="title" name="title" class="input" maxlength="200" value="{{ old('title', $video->title) }}"></div>
        <div class="field"><label for="description" class="label">{{ __('Description') }}</label><textarea id="description" name="description" class="textarea">{{ old('description', $video->description) }}</textarea></div>
        <div class="grid grid-2">
            <div class="field"><label for="thumbnail" class="label">{{ __('Nouvelle miniature') }}</label><input id="thumbnail" type="file" name="thumbnail" accept="image/*" class="input"></div>
            <div class="field"><label for="visibility" class="label">{{ __('Visibilité') }}</label>
                <select id="visibility" name="visibility" class="select">@foreach(['public' => __('Public'), 'unlisted' => __('Non répertoriée'), 'private' => __('Privée')] as $k => $l)<option value="{{ $k }}" @selected($video->visibility === $k)>{{ $l }}</option>@endforeach</select></div>
        </div>
        <label class="switch"><input type="checkbox" name="allow_comments" value="1" @checked($video->allow_comments)><span class="track"></span>{{ __('Autoriser les commentaires') }}</label>
        <div class="row mt" style="justify-content:flex-end"><button class="btn btn-primary">{{ __('Enregistrer') }}</button></div>
    </div></form>

    <div class="card"><div class="card-head"><h3>{{ __('Sous-titres') }}</h3>
        <form method="POST" action="{{ route('videos.subtitles.auto', $video) }}">@csrf<button class="btn btn-soft btn-sm"><x-icon name="sparkles"/>{{ __('Générer automatiquement') }}</button></form></div>
        <div class="card-body">
            @forelse($video->subtitles as $s)
                <div class="row mb" style="margin-bottom:.4rem"><x-icon name="captions"/><span class="grow">{{ $s->label }} @if($s->is_auto)<span class="badge">auto</span>@endif</span>
                    <a href="{{ $s->url() }}" class="btn btn-ghost btn-sm" download>{{ __('Télécharger') }}</a>
                    <form method="POST" action="{{ route('videos.subtitles.destroy', $s) }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-icon btn-sm" aria-label="{{ __('Supprimer') }}"><x-icon name="trash"/></button></form></div>
            @empty<p class="muted small">{{ __('Aucun sous-titre.') }}</p>@endforelse
            <form method="POST" action="{{ route('videos.subtitles.store', $video) }}" enctype="multipart/form-data" class="row mt">@csrf
                <input type="file" name="file" accept=".vtt,.srt" class="input input-sm grow" required aria-label="{{ __('Fichier de sous-titres') }}">
                <select name="lang" class="select input-sm" style="width:auto" aria-label="{{ __('Langue') }}">@foreach(config('vwajen.locales') as $c => $l)<option value="{{ $c }}">{{ $l['name'] }}</option>@endforeach</select>
                <button class="btn btn-primary btn-sm">{{ __('Ajouter') }}</button></form>
        </div></div>

    <form method="POST" action="{{ route('videos.destroy', $video) }}" data-confirm="{{ __('Supprimer définitivement cette vidéo ?') }}">@csrf @method('DELETE')
        <button class="btn btn-danger-outline"><x-icon name="trash"/>{{ __('Supprimer la vidéo') }}</button></form>
@endsection
