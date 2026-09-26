<a href="{{ $v->url() }}" class="tile" data-item="video:{{ $v->id }}">
    <div class="tile-media {{ $v->isShort() ? 'vertical' : '' }}">
        @if($v->thumbnailUrl())
            <img src="{{ $v->thumbnailUrl() }}" alt="" loading="lazy" decoding="async">
        @elseif(! auth()->user()?->data_saver)
            <video src="{{ $v->videoUrl() }}#t=1" preload="metadata" muted playsinline aria-hidden="true"></video>
        @else
            <div class="placeholder"><x-icon name="play"/></div>
        @endif
        @if($v->duration)<span class="duration">{{ $v->durationLabel() }}</span>@endif
        @if($v->kind === 'replay')<span class="overlay"><span class="badge">{{ __('Replay') }}</span></span>@endif
        @if($v->processing_status !== 'ready')<span class="overlay"><span class="badge badge-warning">{{ __('Traitement…') }}</span></span>@endif
    </div>
    <div class="tile-body">
        @unless($v->isShort())<div class="tile-title clamp-2">{{ $v->title ?: __('Vidéo sans titre') }}</div>@endunless
        <div class="small faint truncate">@if(! ($hideAuthor ?? false)){{ $v->user->name }} · @endif{{ short_number($v->views_count) }} {{ __('vues') }}@unless($v->isShort()) · {{ $v->created_at->diffForHumans(null, true, true) }}@endunless</div>
    </div>
</a>
