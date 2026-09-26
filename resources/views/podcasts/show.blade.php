@extends('layouts.app')
@section('title', $podcast->title)
@section('og_image', $podcast->coverUrl())
@section('content')
    <div class="page-head"><a href="{{ route('podcasts.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1 class="truncate">{{ $podcast->title }}</h1></div>
    <div class="card"><div class="card-body row top">
        <img src="{{ $podcast->coverUrl() }}" alt="" style="width:140px;height:140px;border-radius:14px;object-fit:cover">
        <div class="grow"><h2>{{ $podcast->title }}</h2><div class="small muted">{{ __('par') }} <a href="{{ $podcast->user->profileUrl() }}">{{ $podcast->user->name }}</a> · {{ config("vwajen.locales.{$podcast->lang}.name") }}@if($podcast->category) · {{ $podcast->category->name }}@endif</div>
            @if($podcast->description)<p class="mt-sm">{{ $podcast->description }}</p>@endif
            <div class="row mt-sm">
                <button type="button" class="btn btn-outline btn-sm" data-share data-share-url="{{ $podcast->url() }}" data-share-title="{{ $podcast->title }}"><x-icon name="share"/>{{ __('Partager') }}</button>
                @auth @if(auth()->id() !== $podcast->user_id)<a href="{{ route('reports.create', ['podcast', $podcast->id]) }}" class="btn btn-ghost btn-sm"><x-icon name="flag"/></a>@endif @endauth
                @if(auth()->id() === $podcast->user_id)<form method="POST" action="{{ route('podcasts.destroy', $podcast) }}" data-confirm="{{ __('Supprimer ce podcast ?') }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm"><x-icon name="trash"/></button></form>@endif
            </div></div>
    </div></div>
    @if(auth()->id() === $podcast->user_id)
        <details class="card"><summary class="card-body" style="cursor:pointer"><strong>+ {{ __('Publier un épisode') }}</strong></summary>
            <form method="POST" action="{{ route('podcasts.episodes.store', $podcast) }}" enctype="multipart/form-data" class="card-body" style="padding-top:0">@csrf
                <input name="title" class="input" placeholder="{{ __('Titre de l\'épisode') }}" required aria-label="{{ __('Titre') }}">
                <textarea name="description" class="textarea mt-sm" placeholder="{{ __('Description') }}" aria-label="{{ __('Description') }}"></textarea>
                <div data-upload-wrap class="mt-sm"><label class="label">{{ __('Fichier audio (téléversement reprenable)') }}</label>
                    <input type="file" accept="audio/*" class="input" data-resumable="#ep-upload"><input type="hidden" name="upload" id="ep-upload">
                    <div class="upload-progress"><span></span></div><div class="small faint" data-upload-status></div></div>
                <label class="check mt-sm"><input type="checkbox" name="transcribe" value="1">{{ __('Transcription automatique') }}</label>
                <button class="btn btn-primary mt-sm">{{ __('Publier') }}</button></form></details>
    @endif
    <div class="card"><div class="card-head"><h3>{{ __('Épisodes') }}</h3></div>
        @forelse($podcast->episodes as $ep)
            <div class="item" id="episode-{{ $ep->id }}"><div class="item-body">
                <strong>{{ $ep->title }}</strong><div class="small faint">{{ $ep->published_at?->translatedFormat('d M Y') }} · {{ trans_choice(':n écoute|:n écoutes', $ep->plays_count, ['n' => $ep->plays_count]) }}</div>
                @if($ep->description)<p class="small mt-sm">{{ $ep->description }}</p>@endif
                <audio controls preload="none" class="audio-player mt-sm" src="{{ $ep->audioUrl() }}" data-play="{{ route('podcasts.play', $ep) }}"></audio>
                @if($ep->transcript)<details class="mt-sm"><summary class="small" style="cursor:pointer">{{ __('Transcription') }}</summary><p class="small muted">{{ $ep->transcript }}</p></details>@endif
            </div></div>
        @empty <div class="empty"><p>{{ __('Aucun épisode.') }}</p></div> @endforelse
    </div>
@endsection
@push('scripts')<script>document.querySelectorAll('audio[data-play]').forEach(a => a.addEventListener('play', () => { if (!a.dataset.sent) { a.dataset.sent = 1; Vwajen.api(a.dataset.play).catch(() => {}); } }, { once: true }));</script>@endpush
