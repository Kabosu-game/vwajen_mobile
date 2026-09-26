@extends('layouts.app')
@section('title', $kind === 'short' ? __('Publier un Short') : __('Publier une vidéo'))
@section('content')
    <div class="page-head"><a href="{{ $kind === 'short' ? route('shorts.index') : route('videos.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a>
        <h1>{{ $kind === 'short' ? __('Publier un Short') : __('Publier une vidéo') }}</h1></div>
    <form method="POST" action="{{ route('videos.store') }}" enctype="multipart/form-data" class="card" id="video-form"><div class="card-body">
        @csrf
        <div class="pills mb">
            <label><input type="radio" name="kind" value="long" class="pill-check" @checked($kind === 'long')><span class="pill">🎬 {{ __('Vidéo') }}</span></label>
            <label><input type="radio" name="kind" value="short" class="pill-check" @checked($kind === 'short')><span class="pill">📱 Short ({{ __('vertical, max :s s', ['s' => config('vwajen.limits.short_seconds')]) }})</span></label>
        </div>

        <div data-upload-wrap>
            <label class="dropzone" for="video-file">
                <x-icon name="upload"/>
                <strong>{{ __('Choisir une vidéo') }}</strong>
                <div class="small">{{ __('MP4, WebM, MOV… jusqu\'à :mb Mo. Le téléversement reprend automatiquement après une coupure.', ['mb' => config('vwajen.limits.video_mb')]) }}</div>
            </label>
            <input id="video-file" type="file" accept="video/*" hidden data-resumable="#video-upload">
            <input type="hidden" name="upload" id="video-upload">
            <video data-file-preview hidden controls playsinline style="max-height:320px;margin:.6rem auto;border-radius:12px"></video>
            <div class="upload-progress"><span></span></div>
            <div class="small faint" data-upload-status></div>
        </div>

        <div class="row mt-sm">
            <button type="button" class="btn btn-outline btn-sm" data-record-start><x-icon name="camera"/>{{ __('Enregistrer avec la caméra') }}</button>
            <button type="button" class="btn btn-live btn-sm" data-record-stop hidden><x-icon name="stop"/>{{ __('Arrêter') }}</button>
            <span class="small faint" data-record-time></span>
        </div>
        <video data-record-preview hidden muted playsinline style="max-height:320px;margin-top:.6rem;border-radius:12px"></video>

        <div class="field mt"><label for="title" class="label">{{ __('Titre') }}</label><input id="title" name="title" class="input" maxlength="200" value="{{ old('title') }}"></div>
        <div class="field"><label for="description" class="label">{{ __('Description') }}</label><textarea id="description" name="description" class="textarea" maxlength="5000" data-mention placeholder="#hashtags @mentions">{{ old('description') }}</textarea></div>
        <div class="grid grid-2">
            <div class="field"><label for="thumbnail" class="label">{{ __('Miniature') }}</label><input id="thumbnail" type="file" name="thumbnail" accept="image/*" class="input">
                <div class="hint">{{ __('Générée automatiquement si vide (ffmpeg).') }}</div></div>
            <div class="field"><label for="visibility" class="label">{{ __('Visibilité') }}</label>
                <select id="visibility" name="visibility" class="select"><option value="public">{{ __('Public') }}</option><option value="unlisted">{{ __('Non répertoriée (lien uniquement)') }}</option><option value="private">{{ __('Privée') }}</option></select></div>
            <div class="field"><label for="lang" class="label">{{ __('Langue') }}</label>
                <select id="lang" name="lang" class="select">@foreach(config('vwajen.locales') as $c => $l)<option value="{{ $c }}" @selected(auth()->user()->locale === $c)>{{ $l['name'] }}</option>@endforeach</select></div>
            <div class="field"><label for="subtitle" class="label">{{ __('Sous-titres (.vtt ou .srt)') }}</label><input id="subtitle" type="file" name="subtitle" accept=".vtt,.srt,text/vtt" class="input">
                <select name="subtitle_lang" class="select input-sm mt-sm" aria-label="{{ __('Langue des sous-titres') }}">@foreach(config('vwajen.locales') as $c => $l)<option value="{{ $c }}">{{ $l['name'] }}</option>@endforeach</select></div>
        </div>
        <label class="switch"><input type="checkbox" name="allow_comments" value="1" checked><span class="track"></span>{{ __('Autoriser les commentaires') }}</label>
        <div class="row mt" style="justify-content:flex-end"><button type="submit" class="btn btn-brand" disabled data-submit-video>{{ __('Publier') }}</button></div>
    </div></form>
@endsection
@push('scripts')
<script>
(function () {
    const form = document.getElementById('video-form'), V = window.Vwajen;
    const submit = form.querySelector('[data-submit-video]'), hidden = form.querySelector('#video-upload');
    new MutationObserver(() => {}).observe(hidden, { attributes: true });
    setInterval(() => { submit.disabled = !hidden.value; }, 500);

    // Enregistrement vidéo depuis la caméra (MediaRecorder) puis téléversement reprenable
    let rec, chunks = [], stream, t0, timer;
    const start = form.querySelector('[data-record-start]'), stop = form.querySelector('[data-record-stop]'), prev = form.querySelector('[data-record-preview]'), time = form.querySelector('[data-record-time]');
    start.addEventListener('click', async () => {
        try {
            const vertical = form.querySelector('input[name=kind]:checked').value === 'short';
            stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: vertical ? { width: 720, height: 1280, facingMode: 'user' } : { width: 1280, height: 720 } });
            prev.srcObject = stream; prev.hidden = false; prev.play();
            const type = ['video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4'].find(t => window.MediaRecorder && MediaRecorder.isTypeSupported(t));
            rec = new MediaRecorder(stream, type ? { mimeType: type } : undefined); chunks = [];
            rec.ondataavailable = e => e.data.size && chunks.push(e.data);
            rec.onstop = () => {
                stream.getTracks().forEach(t => t.stop()); prev.srcObject = null; prev.hidden = true; clearInterval(timer);
                const file = new File(chunks, 'enregistrement-' + Date.now() + '.webm', { type: chunks[0]?.type || 'video/webm' });
                const input = form.querySelector('#video-file'); const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files;
                input.dispatchEvent(new Event('change'));
            };
            rec.start(1000); t0 = Date.now(); start.hidden = true; stop.hidden = false;
            timer = setInterval(() => { const s = Math.round((Date.now() - t0) / 1000); time.textContent = s + ' s'; if (vertical && s >= {{ config('vwajen.limits.short_seconds') }}) stop.click(); }, 500);
        } catch (e) { V.toast(e.message, 'error'); }
    });
    stop.addEventListener('click', () => { rec && rec.state !== 'inactive' && rec.stop(); stop.hidden = true; start.hidden = false; time.textContent = ''; });
})();
</script>
@endpush
