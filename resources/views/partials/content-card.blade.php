@php
    /** Carte générique pour les contenus non-« Vwa » dans les fils : vidéo, short, live, débat, question, réponse, événement, discussion. */
    $type = $model->getMorphClass();
    $author = $model->user;
@endphp
<article class="item" id="{{ $type }}-{{ $model->id }}" data-item="{{ $type }}:{{ $model->id }}" data-href="{{ $model->url() }}">
    <img src="{{ $author?->avatarUrl() }}" alt="" class="avatar" loading="lazy">
    <div class="item-body">
        @isset($reposter)
            @if($reposter)
                <div class="item-context" style="margin:0 0 .25rem"><x-icon name="repost"/>{{ __(':name a reposté', ['name' => $reposter->name]) }}</div>
            @endif
        @endisset
        <div class="item-meta">
            <a href="{{ $author?->profileUrl() }}" class="name">{{ $author?->name }}</a>@if($author)@include('partials.verified', ['u' => $author])@endif
            <span class="sep"></span><span class="badge">{{ \App\Support\Morph::label($type) }}</span>
            <span class="sep"></span><span class="handle">{{ $model->created_at?->diffForHumans(null, true, true) }}</span>
        </div>
        @include('partials.item-menu', ['model' => $model, 'type' => $type])

        @switch($type)
            @case('video')
                <a href="{{ $model->url() }}" class="tile mt-sm" style="max-width:{{ $model->isShort() ? '240px' : '100%' }}">
                    <div class="tile-media {{ $model->isShort() ? 'vertical' : '' }}">
                        @if($model->thumbnailUrl())<img src="{{ $model->thumbnailUrl() }}" alt="" loading="lazy">@else<div class="placeholder"><x-icon name="play"/></div>@endif
                        @if($model->duration)<span class="duration">{{ $model->durationLabel() }}</span>@endif
                        @if($model->isShort())<span class="overlay"><span class="badge badge-primary">Short</span></span>@endif
                    </div>
                    @if($model->title)<div class="tile-body"><div class="tile-title clamp-2">{{ $model->title }}</div></div>@endif
                </a>
                @break
            @case('live')
                <a href="{{ $model->url() }}" class="tile mt-sm">
                    <div class="tile-media">
                        @if($model->coverUrl())<img src="{{ $model->coverUrl() }}" alt="" loading="lazy">@else<div class="placeholder"><x-icon name="{{ $model->isAudio() ? 'mic' : 'live' }}"/></div>@endif
                        <span class="overlay">@if($model->isLive())<span class="badge badge-live">● {{ __('EN DIRECT') }}</span>@elseif($model->status === 'scheduled')<span class="badge">{{ $model->scheduled_at?->translatedFormat('d M, H:i') }}</span>@else<span class="badge">{{ __('Replay') }}</span>@endif</span>
                    </div>
                    <div class="tile-body"><div class="tile-title">{{ $model->title }}</div></div>
                </a>
                @break
            @case('debate')
                <a href="{{ $model->url() }}" class="quote mt-sm">
                    <div class="row"><x-icon name="debate"/><strong class="grow">{{ $model->title }}</strong>
                        @if($model->status === 'live')<span class="badge badge-live">● {{ __('EN DIRECT') }}</span>@endif</div>
                    <div class="small muted mt-sm">{{ $model->scheduled_at->translatedFormat('l d F Y · H:i') }}</div>
                </a>
                @break
            @case('question')
                <a href="{{ $model->url() }}" class="quote mt-sm">
                    <div class="row"><x-icon name="question"/><strong class="grow">{{ $model->title }}</strong><span class="badge">{{ $model->statusLabel() }}</span></div>
                    @if($model->candidate)<div class="small muted mt-sm">{{ __('Adressée à :name', ['name' => $model->candidate->name]) }}</div>@endif
                </a>
                @break
            @case('event')
                <a href="{{ $model->url() }}" class="quote mt-sm row">
                    <div class="date-box"><div class="m">{{ $model->localStart()->translatedFormat('M') }}</div><div class="d">{{ $model->localStart()->format('d') }}</div></div>
                    <div class="grow"><strong>{{ $model->title }}</strong><div class="small muted">{{ $model->is_online ? __('En ligne') : trim($model->location_name.' '.$model->city) }}</div></div>
                </a>
                @break
            @case('answer')
                <div class="post-text" data-text>{!! render_text(\Illuminate\Support\Str::limit($model->body, 400)) !!}</div>
                <a href="{{ $model->url() }}" class="small">{{ __('Voir la question') }}</a>
                @break
            @default
                <a href="{{ $model->url() }}" class="quote mt-sm"><strong>{{ $model->title ?? '' }}</strong>
                    <div class="small muted clamp-2" data-text>{{ $model->body ?? $model->description ?? '' }}</div></a>
        @endswitch

        @include('partials.actions', ['model' => $model, 'type' => $type])
    </div>
</article>
