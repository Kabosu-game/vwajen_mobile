<a href="{{ $d->url() }}" class="tile" data-item="debate:{{ $d->id }}">
    <div class="tile-media" style="aspect-ratio:21/9">
        @if($d->coverUrl())<img src="{{ $d->coverUrl() }}" alt="" loading="lazy">@else<div class="placeholder"><x-icon name="debate"/></div>@endif
        <span class="overlay">
            @if($d->status === 'live')<span class="badge badge-live">● {{ __('EN DIRECT') }}</span>
            @elseif($d->status === 'scheduled')<span class="badge badge-info">{{ $d->scheduled_at->translatedFormat('d M · H:i') }}</span>
            @elseif($d->archived_at)<span class="badge">{{ __('Archivé') }}</span>
            @else<span class="badge">{{ __('Replay') }}</span>@endif
        </span>
    </div>
    <div class="tile-body">
        <div class="tile-title clamp-2">{{ $d->title }}</div>
        <div class="avatar-stack mt-sm">
            @foreach($d->participants->take(6) as $p)
                <img src="{{ $p->user->avatarUrl() }}" alt="{{ $p->user->name }}" title="{{ $p->user->name }}" class="avatar avatar-sm">
            @endforeach
        </div>
        <div class="small faint mt-sm">{{ trans_choice(':n participant|:n participants', $d->participants->count(), ['n' => $d->participants->count()]) }}</div>
    </div>
</a>
