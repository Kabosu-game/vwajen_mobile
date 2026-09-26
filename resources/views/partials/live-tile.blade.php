<a href="{{ $l->url() }}" class="tile" data-item="live:{{ $l->id }}">
    <div class="tile-media">
        @if($l->coverUrl())<img src="{{ $l->coverUrl() }}" alt="" loading="lazy">@else<div class="placeholder"><x-icon name="{{ $l->isAudio() ? 'mic' : 'live' }}"/></div>@endif
        <span class="overlay">
            @if($l->isLive())<span class="badge badge-live">● {{ __('EN DIRECT') }}</span>
            @elseif($l->status === 'scheduled')<span class="badge badge-info"><x-icon name="calendar" style="width:13px;height:13px"/>{{ $l->scheduled_at?->translatedFormat('d M · H:i') ?? __('Bientôt') }}</span>
            @elseif($l->status === 'cancelled')<span class="badge badge-danger">{{ __('Annulé') }}</span>
            @else<span class="badge">{{ __('Replay') }}</span>@endif
            @if($l->isAudio())<span class="badge">Audio</span>@endif
        </span>
    </div>
    <div class="tile-body">
        <div class="tile-title clamp-2">{{ $l->title }}</div>
        <div class="row small faint"><img src="{{ $l->user->avatarUrl() }}" alt="" class="avatar avatar-xs">{{ $l->user->name }}@include('partials.verified', ['u' => $l->user])</div>
    </div>
</a>
