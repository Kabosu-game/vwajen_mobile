@php
    $me = auth()->user();
    $uid = 'c'.\Illuminate\Support\Str::random(6);
    $limit = config('vwajen.limits.post_length');
@endphp
<form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data" class="{{ ($inModal ?? false) ? '' : 'composer' }}" data-composer id="{{ $uid }}">
    @csrf
    @isset($community)<input type="hidden" name="community_id" value="{{ $community->id }}">@endisset
    <input type="hidden" name="video_upload" data-upload-target>
    <div class="row top">
        <img src="{{ $me->avatarUrl() }}" alt="" class="avatar">
        <div class="grow">
            <label for="{{ $uid }}-body" class="sr-only">{{ __('Votre Vwa') }}</label>
            <textarea id="{{ $uid }}-body" name="body" rows="2" maxlength="{{ $limit + 50 }}" data-autosize data-mention
                      placeholder="{{ $placeholder ?? __('Quoi de neuf ? Faites entendre votre voix…') }}"></textarea>

            <div class="previews" data-previews></div>
            <div data-video-preview hidden class="mt-sm">
                <video controls playsinline style="max-height:260px;border-radius:12px"></video>
                <div class="upload-progress"><span></span></div>
                <div class="small faint" data-upload-status></div>
            </div>
            <div class="link-card" data-link-preview hidden></div>

            <div class="poll-builder" data-poll hidden>
                <div class="row between mb"><strong>{{ __('Sondage') }}</strong>
                    <button type="button" class="btn btn-ghost btn-sm" data-poll-remove>{{ __('Retirer') }}</button></div>
                @for($i = 1; $i <= 4; $i++)
                    <input type="text" name="poll_options[]" class="input input-sm mb-0" style="margin-bottom:.4rem" maxlength="100"
                           placeholder="{{ __('Choix :n', ['n' => $i]) }}{{ $i > 2 ? ' ('.__('facultatif').')' : '' }}" aria-label="{{ __('Choix :n', ['n' => $i]) }}">
                @endfor
                <div class="row wrap mt-sm">
                    <label class="small">{{ __('Durée') }}
                        <select name="poll_hours" class="select input-sm" style="width:auto;display:inline-block">
                            <option value="6">6 h</option><option value="24" selected>1 {{ __('jour') }}</option><option value="72">3 {{ __('jours') }}</option><option value="168">7 {{ __('jours') }}</option>
                        </select></label>
                    <label class="check small mb-0"><input type="checkbox" name="poll_multiple" value="1">{{ __('Choix multiples') }}</label>
                </div>
            </div>
        </div>
    </div>

    <div class="composer-tools">
        <label class="tool" title="{{ __('Photos') }}"><x-icon name="image"/><span class="sr-only">{{ __('Ajouter des photos') }}</span>
            <input type="file" name="images[]" accept="image/*" multiple hidden data-images></label>
        <label class="tool" title="{{ __('Vidéo') }}"><x-icon name="video"/><span class="sr-only">{{ __('Ajouter une vidéo') }}</span>
            <input type="file" accept="video/*" hidden data-video-file></label>
        <button type="button" class="tool" data-poll-toggle title="{{ __('Sondage') }}" aria-label="{{ __('Ajouter un sondage') }}"><x-icon name="poll"/></button>
        <div class="dropdown">
            <button type="button" class="tool" data-dropdown aria-label="{{ __('Émojis') }}"><x-icon name="smile"/></button>
            <div class="menu left emoji-menu" data-emoji-menu>
                @foreach(['😀','😂','😍','🙏','👏','🔥','💪','🇭🇹','❤️','🎉','🤔','😢','✊','📢','✅','🗳️','🌴','☀️'] as $e)
                    <button type="button" data-emoji="{{ $e }}" style="font-size:1.25rem;justify-content:center">{{ $e }}</button>
                @endforeach
            </div>
        </div>
        @unless(isset($community))
            <label class="sr-only" for="{{ $uid }}-vis">{{ __('Visibilité') }}</label>
            <select id="{{ $uid }}-vis" name="visibility" class="select input-sm" style="width:auto;border-radius:999px;margin-left:.3rem">
                <option value="public">🌐 {{ __('Public') }}</option>
                <option value="followers">👥 {{ __('Abonnés') }}</option>
            </select>
        @endunless
        <label class="sr-only" for="{{ $uid }}-lang">{{ __('Langue de la publication') }}</label>
        <select id="{{ $uid }}-lang" name="lang" class="select input-sm" style="width:auto;border-radius:999px;margin-left:.3rem">
            @foreach(config('vwajen.locales') as $code => $l)
                <option value="{{ $code }}" @selected($me->locale === $code)>{{ $l['short'] }}</option>
            @endforeach
        </select>
        <span class="char-count" data-count-for="{{ $uid }}-body" data-max="{{ $limit }}">{{ $limit }}</span>
        <button type="submit" class="btn btn-primary" data-submit>{{ __('Publier') }}</button>
    </div>
</form>
