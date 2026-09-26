@php
    /** Scène du live (WebRTC / HLS / replay) + chat. Utilisée par les lives et les débats. */
    $viewer = auth()->user();
    $isOwner = $viewer && $viewer->id === $live->user_id;
    $isDebate = isset($debate);
    $canStart = $isDebate ? $debate->canManage($viewer) : ($isOwner && $viewer->canGoLive());
    $chatType = $isDebate ? 'debate' : 'live';
    $chatId = $isDebate ? $debate->id : $live->id;
    $replay = $live->replay ?? ($isDebate ? $debate->replay : null);
@endphp
<div class="live-layout" id="live-app"
     data-live="{{ $live->id }}" data-status="{{ $live->status }}" data-kind="{{ $live->kind }}"
     data-host="{{ $isHost ? 1 : 0 }}" data-owner="{{ $isOwner ? 1 : 0 }}" data-moderator="{{ $canModerate ? 1 : 0 }}"
     data-playback="{{ $live->playback_url }}" data-ice='@json($rtc)'
     data-heartbeat="{{ route('lives.heartbeat', $live) }}" data-signals="{{ route('lives.signals', $live) }}" data-signal="{{ route('lives.signal', $live) }}"
     data-chat="{{ route('chat.index', [$chatType, $chatId]) }}" data-chat-post="{{ $viewer ? route('chat.store', [$chatType, $chatId]) : '' }}"
     data-react="{{ route('lives.react', $live) }}" data-start="{{ $isDebate ? route('debates.start', $debate) : route('lives.start', $live) }}"
     data-end="{{ $isDebate ? route('debates.end', $debate) : route('lives.end', $live) }}" data-replay="{{ route('lives.replay', $live) }}"
     data-kick="{{ url('/lives/'.$live->id.'/viewers') }}" data-chat-mod="{{ url('/chat/messages') }}" data-me="{{ $viewer?->name }}">
    <div>
        @if($live->status === 'ended' && $replay)
            <div class="player">
                <video controls playsinline preload="metadata" src="{{ $replay->videoUrl() }}" @if($replay->thumbnailUrl()) poster="{{ $replay->thumbnailUrl() }}" @endif data-track-view="{{ route('videos.view', $replay) }}">
                    @foreach($replay->subtitles as $s)<track kind="subtitles" src="{{ $s->url() }}" srclang="{{ $s->lang }}" label="{{ $s->label }}">@endforeach
                </video>
            </div>
            <div class="row mt-sm"><span class="badge">{{ __('Replay') }}</span><a href="{{ $replay->url() }}?player=1" class="small">{{ __('Ouvrir la vidéo du replay') }}</a></div>
        @else
            <div class="stage" id="stage">
                <div class="stage-info">
                    <span class="badge badge-live" data-live-badge @if(! $live->isLive()) hidden @endif>● {{ __('EN DIRECT') }}</span>
                    <span class="badge" style="background:rgba(0,0,0,.6);color:#fff"><x-icon name="eye" style="width:13px;height:13px"/> <span data-viewers>{{ $live->currentViewersCount() }}</span></span>
                </div>
                <div class="stage-grid n1" data-grid></div>
                <div class="stage-empty" data-stage-empty>
                    <div>
                        <x-icon name="{{ $live->isAudio() ? 'mic' : 'live' }}" style="width:52px;height:52px;margin:0 auto .6rem"/>
                        @if($live->status === 'scheduled')
                            <strong>{{ __('Commence :when', ['when' => $live->scheduled_at?->diffForHumans() ?? __('bientôt')]) }}</strong>
                            <div class="small" style="opacity:.8">{{ $live->scheduled_at?->translatedFormat('l d F · H:i') }}</div>
                        @elseif($live->status === 'live')
                            <strong data-connecting>{{ __('Connexion au direct…') }}</strong>
                        @elseif($live->status === 'cancelled')
                            <strong>{{ __('Ce live a été annulé.') }}</strong>
                        @else
                            <strong>{{ __('Le live est terminé.') }}</strong><div class="small" style="opacity:.8">{{ __('Le replay sera disponible prochainement.') }}</div>
                        @endif
                    </div>
                </div>
                <video data-hls hidden controls playsinline style="width:100%;height:100%;background:#000"></video>
                <div class="reactions-layer" data-reactions-layer aria-hidden="true"></div>
            </div>

            @if($isHost || $canStart)
                <div class="card mt-sm" data-studio><div class="card-body">
                    <div class="row wrap">
                        <strong class="grow"><x-icon name="settings" style="vertical-align:-4px"/> {{ __('Studio') }}</strong>
                        <button type="button" class="btn btn-outline btn-sm" data-cam-start><x-icon name="camera"/>{{ $live->isAudio() ? __('Activer le micro') : __('Activer caméra et micro') }}</button>
                        <button type="button" class="btn btn-ghost btn-icon btn-sm" data-toggle-mic hidden aria-label="{{ __('Micro') }}"><x-icon name="mic"/></button>
                        @unless($live->isAudio())
                            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-toggle-cam hidden aria-label="{{ __('Caméra') }}"><x-icon name="camera"/></button>
                            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-screen hidden aria-label="{{ __('Partager l\'écran') }}"><x-icon name="screen"/></button>
                        @endunless
                        @if($canStart)
                            <button type="button" class="btn btn-live btn-sm" data-go-live @if($live->isLive()) hidden @endif><x-icon name="record"/>{{ __('Démarrer le live') }}</button>
                            <button type="button" class="btn btn-dark btn-sm" data-end-live @unless($live->isLive()) hidden @endunless data-confirm="{{ __('Terminer le live ?') }}"><x-icon name="stop"/>{{ __('Terminer') }}</button>
                        @elseif($isHost)
                            <button type="button" class="btn btn-primary btn-sm" data-join-stage @unless($live->isLive()) disabled @endunless><x-icon name="arrow-right"/>{{ __('Rejoindre la scène') }}</button>
                        @endif
                    </div>
                    @if($isOwner)
                        <label class="check small mt-sm mb-0"><input type="checkbox" data-record checked> {{ __('Enregistrer le live pour le replay') }}</label>
                        <div class="upload-progress" data-replay-progress hidden><span></span></div><div class="small faint" data-replay-status></div>
                    @endif
                    <div class="small faint mt-sm">{{ __('La diffusion se fait directement depuis votre navigateur (WebRTC). Pour une grande audience, utilisez un serveur média (champ HLS).') }}</div>
                </div></div>
            @endif
        @endif
    </div>

    <aside class="chat" aria-label="{{ __('Chat en direct') }}">
        <div class="chat-head"><strong>{{ __('Chat en direct') }}</strong>
            @if($canModerate)
                <div class="dropdown">
                    <button type="button" class="btn btn-ghost btn-icon btn-sm" data-dropdown aria-label="{{ __('Modération du chat') }}"><x-icon name="shield"/></button>
                    <div class="menu" style="min-width:260px;padding:.7rem">
                        <form method="POST" action="{{ route('lives.chat-settings', $live) }}" data-ajax>@csrf
                            <label class="check"><input type="checkbox" name="chat_enabled" value="1" @checked($live->chat_enabled)>{{ __('Chat activé') }}</label>
                            <label class="check"><input type="checkbox" name="chat_followers_only" value="1" @checked($live->chat_followers_only)>{{ __('Abonnés uniquement') }}</label>
                            <label class="small">{{ __('Mode lent (s)') }} <input type="number" name="slow_mode_seconds" min="0" max="300" value="{{ $live->slow_mode_seconds }}" class="input input-sm"></label>
                            <button class="btn btn-primary btn-sm mt-sm">{{ __('Appliquer') }}</button>
                        </form>
                        <hr><div class="small" style="font-weight:700">{{ __('Spectateurs') }}</div>
                        <div data-audience class="small" style="max-height:220px;overflow-y:auto"></div>
                    </div>
                </div>
            @endif
        </div>
        <div class="chat-pinned" data-pinned hidden></div>
        <div class="chat-body" data-chat-body aria-live="polite"></div>
        <div class="reaction-bar" role="group" aria-label="{{ __('Réactions') }}">
            @foreach($reactions as $r)<button type="button" data-react="{{ $r }}" data-auth aria-label="{{ $r }}">{{ $r }}</button>@endforeach
        </div>
        @auth
            <form class="chat-form" data-chat-form>
                <label for="chat-input" class="sr-only">{{ __('Message') }}</label>
                <input id="chat-input" class="input input-sm" maxlength="500" placeholder="{{ __('Écrire un message…') }}" autocomplete="off">
                <button class="btn btn-primary btn-sm" aria-label="{{ __('Envoyer') }}"><x-icon name="send"/></button>
            </form>
        @else
            <div class="chat-form small"><a href="{{ route('login') }}">{{ __('Connectez-vous pour participer au chat') }}</a></div>
        @endauth
    </aside>
</div>
@push('scripts')
    @if($live->playback_url)<script src="https://cdnjs.cloudflare.com/ajax/libs/hls.js/1.5.13/hls.min.js" defer></script>@endif
    <script src="{{ asset('js/live.js') }}?v={{ filemtime(public_path('js/live.js')) }}" defer></script>
@endpush
