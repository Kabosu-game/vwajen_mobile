@extends('layouts.app')
@section('title', $video->title ?: __('Vidéo de :name', ['name' => $video->user->name]))
@section('layout', 'wide')
@section('og_image', $video->thumbnailUrl() ?? asset('images/logo-400.jpg'))
@section('og_description', \Illuminate\Support\Str::limit($video->description, 180))
@php $viewer = auth()->user(); $sources = $video->sources(); @endphp
@push('head')<meta property="og:video" content="{{ $video->videoUrl() }}">@endpush
@section('content')
    <div class="page-head"><a href="{{ url()->previous() }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1 class="truncate">{{ $video->title ?: __('Vidéo') }}</h1></div>
    <div class="split-layout">
        <div>
            @if($video->processing_status !== 'ready')<div class="alert alert-warning"><x-icon name="clock"/><div>{{ __('Traitement en cours (compression et qualités multiples). La vidéo originale est déjà lisible.') }}</div></div>@endif
            <div class="player" data-player data-view="{{ route('videos.view', $video) }}">
                <video controls playsinline preload="{{ $viewer?->data_saver ? 'none' : 'metadata' }}" @if(! $viewer?->reduce_autoplay && ! $viewer?->data_saver) autoplay @endif
                       src="{{ $viewer?->data_saver ? reset($sources) : end($sources) }}" @if($video->thumbnailUrl()) poster="{{ $video->thumbnailUrl() }}" @endif crossorigin="anonymous">
                    @foreach($video->subtitles as $s)<track kind="subtitles" src="{{ $s->url() }}" srclang="{{ $s->lang }}" label="{{ $s->label }}" @if($s->lang === app()->getLocale()) default @endif>@endforeach
                    {{ __('Votre navigateur ne peut pas lire cette vidéo.') }}
                </video>
            </div>
            <div class="player-bar">
                @if(count($sources) > 1)
                    <label class="small row" style="gap:.3rem"><x-icon name="settings" style="width:16px;height:16px"/><span class="sr-only">{{ __('Qualité') }}</span>
                        <select class="select input-sm" data-quality aria-label="{{ __('Qualité vidéo') }}">
                            <option value="auto">{{ __('Auto') }}</option>
                            @foreach($sources as $q => $url)<option value="{{ $url }}">{{ $q === 'original' ? __('Originale') : $q }}</option>@endforeach
                        </select></label>
                @endif
                @if($video->subtitles->isNotEmpty())<span class="badge"><x-icon name="captions" style="width:13px;height:13px"/>{{ $video->subtitles->pluck('lang')->map(fn ($l) => strtoupper($l))->implode(' · ') }}</span>@endif
                <button type="button" class="btn btn-ghost btn-sm" data-fullscreen><x-icon name="maximize"/>{{ __('Plein écran') }}</button>
            </div>

            <div class="card mt-sm" id="video-{{ $video->id }}" data-item="video:{{ $video->id }}"><div class="card-body">
                <h2 style="font-size:1.25rem">{{ $video->title }}</h2>
                <div class="row wrap">
                    <img src="{{ $video->user->avatarUrl() }}" class="avatar" alt="">
                    <div class="grow"><a href="{{ $video->user->profileUrl() }}" class="name">{{ $video->user->name }}</a>@include('partials.verified', ['u' => $video->user])
                        <div class="small faint">{{ short_number($video->user->followers_count) }} {{ __('abonnés') }}</div></div>
                    @include('partials.follow-button', ['u' => $video->user])
                    @include('partials.item-menu', ['model' => $video, 'type' => 'video'])
                </div>
                <div class="small muted mt-sm">{{ short_number($video->views_count) }} {{ __('vues') }} · {{ $video->created_at->translatedFormat('d M Y') }}
                    @if($video->kind === 'replay' && $video->source)· <a href="{{ $video->source->url() }}">{{ __('Replay de : :title', ['title' => $video->source->title]) }}</a>@endif</div>
                @if($video->description)<div class="post-text mt-sm" data-text>{!! render_text($video->description) !!}</div>@endif
                @include('partials.actions', ['model' => $video, 'type' => 'video'])
                @if($video->transcript)
                    <details class="mt-sm"><summary class="small" style="cursor:pointer">{{ __('Transcription') }}</summary><div class="small muted prose mt-sm">{{ $video->transcript }}</div></details>
                @endif
            </div></div>
            @include('partials.comments', ['target' => $video, 'type' => 'video', 'comments' => $comments])
        </div>
        <aside>
            <h3>{{ __('À voir aussi') }}</h3>
            <div class="stack">@foreach($related as $v) @include('partials.video-tile', ['v' => $v]) @endforeach</div>
        </aside>
    </div>
@endsection
@push('scripts')
<script>
(function () {
    const box = document.querySelector('[data-player]'); if (!box) return;
    const v = box.querySelector('video'), q = document.querySelector('[data-quality]'), V = window.Vwajen || {};
    // Qualité adaptative : choix automatique selon le réseau (Network Information API) et l'économie de données.
    if (q) {
        const opts = [...q.options].slice(1).map(o => o.value);
        const conn = navigator.connection || {};
        const slow = V.dataSaver || conn.saveData || /2g|3g/.test(conn.effectiveType || '');
        if (slow && opts.length) { v.src = opts[0]; }
        q.addEventListener('change', () => {
            const t = v.currentTime, playing = !v.paused;
            v.src = q.value === 'auto' ? (slow ? opts[0] : opts[opts.length - 1]) : q.value;
            v.currentTime = t; if (playing) v.play();
        });
    }
    document.querySelector('[data-fullscreen]')?.addEventListener('click', () => (v.requestFullscreen || v.webkitEnterFullscreen || function () {}).call(v));
    // Comptage des vues et du temps de visionnage
    let sent = false, last = 0;
    v.addEventListener('timeupdate', () => {
        if (!sent && v.currentTime > 3) { sent = true; V.api(box.dataset.view, { body: { seconds: Math.round(v.currentTime) } }).catch(() => {}); }
        if (sent && v.currentTime - last > 30) { last = v.currentTime; V.api(box.dataset.view, { body: { seconds: Math.round(v.currentTime) } }).catch(() => {}); }
    });
})();
</script>
@endpush
