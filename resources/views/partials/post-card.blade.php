@php
    /** @var \App\Models\Post $post */
    $viewer = auth()->user();
    $full = $full ?? false;
    $author = $post->user;
    $images = $post->media->where('type', 'image')->values();
    $video = $post->media->firstWhere('type', 'video');
@endphp
<article class="item" id="post-{{ $post->id }}" data-item="post:{{ $post->id }}" @unless($full) data-href="{{ $post->url() }}" @endunless aria-labelledby="post-{{ $post->id }}-author">
    <a href="{{ $author->profileUrl() }}" class="shrink-0" tabindex="-1" aria-hidden="true"><img src="{{ $author->avatarUrl() }}" alt="" class="avatar" loading="lazy"></a>
    <div class="item-body">
        @isset($reposter)
            <div class="item-context" style="margin:0 0 .25rem"><x-icon name="repost"/>
                <a href="{{ $reposter->profileUrl() }}" class="faint">{{ $reposter->id === $viewer?->id ? __('Vous avez reposté') : __(':name a reposté', ['name' => $reposter->name]) }}</a></div>
        @endisset
        <div class="item-meta">
            <a href="{{ $author->profileUrl() }}" class="name" id="post-{{ $post->id }}-author">{{ $author->name }}</a>@include('partials.verified', ['u' => $author])
            <span class="handle">{{ '@'.$author->username }}</span>
            <span class="sep"></span>
            <a href="{{ $post->url() }}" class="handle" title="{{ $post->created_at->translatedFormat('d F Y H:i') }}"><time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->diffForHumans(null, true, true) }}</time></a>
            @if($post->visibility === 'followers')<span class="sep"></span><span class="faint" title="{{ __('Abonnés uniquement') }}"><x-icon name="users" style="width:14px;height:14px"/></span>@endif
            @if($post->community)<span class="sep"></span><a href="{{ $post->community->url() }}" class="badge">{{ $post->community->name }}</a>@endif
        </div>
        @include('partials.item-menu', ['model' => $post, 'type' => 'post'])

        @if($post->body)
            <div class="post-text {{ $full ? 'big' : '' }}" data-text>{!! render_text($post->body) !!}</div>
            @if($post->lang && $post->lang !== app()->getLocale())
                <button type="button" class="btn btn-ghost btn-sm" style="padding:.1rem .3rem;margin:-.3rem 0 .3rem -.3rem" data-translate="{{ route('interact.translate', ['post', $post->id]) }}">
                    <x-icon name="translate"/>{{ __('Traduire') }}</button>
            @endif
        @endif

        @if($images->isNotEmpty())
            <div class="media-grid n{{ min(4, $images->count()) }}">
                @foreach($images as $img)
                    <img src="{{ $img->url() }}" alt="{{ $img->alt ?: __('Image de :name', ['name' => $author->name]) }}" loading="lazy" decoding="async" data-lightbox
                         @if($img->width) width="{{ $img->width }}" height="{{ $img->height }}" @endif>
                @endforeach
            </div>
        @endif
        @if($video)
            <div class="media-grid n1">
                <video src="{{ $video->url() }}" controls playsinline preload="{{ $viewer?->data_saver ? 'none' : 'metadata' }}" @if($video->thumbnail) poster="{{ $video->thumbnailUrl() }}" @endif></video>
            </div>
        @endif

        @if($post->poll)
            <div data-poll-wrap>@include('partials.poll', ['poll' => $post->poll])</div>
        @endif

        @if($post->link_url && $images->isEmpty() && ! $video)
            <a href="{{ $post->link_url }}" class="link-card" target="_blank" rel="noopener nofollow ugc">
                @if($post->link_image && ! $viewer?->data_saver)<img src="{{ $post->link_image }}" alt="" loading="lazy">@endif
                <div class="lc-body">
                    <div class="lc-domain">{{ parse_url($post->link_url, PHP_URL_HOST) }}</div>
                    <div class="clamp-2" style="font-weight:650">{{ $post->link_title ?: $post->link_url }}</div>
                    @if($post->link_description)<div class="small muted clamp-2">{{ $post->link_description }}</div>@endif
                </div>
            </a>
        @endif

        @if($post->quoteOf)
            <a href="{{ $post->quoteOf->url() }}" class="quote">
                <div class="item-meta"><img src="{{ $post->quoteOf->user->avatarUrl() }}" alt="" class="avatar avatar-xs">
                    <span class="name">{{ $post->quoteOf->user->name }}</span><span class="handle">{{ '@'.$post->quoteOf->user->username }}</span></div>
                <div class="clamp-3 mt-sm">{{ $post->quoteOf->body }}</div>
            </a>
        @endif

        @if($post->edited_at)<div class="edited">{{ __('Modifié') }} · {{ $post->edited_at->diffForHumans() }}</div>@endif
        @if($full)
            <div class="small faint mt-sm">{{ $post->created_at->translatedFormat('H:i · d F Y') }} · <b>{{ short_number($post->views_count) }}</b> {{ __('vues') }}</div>
        @endif

        @include('partials.actions', ['model' => $post, 'type' => 'post'])
    </div>
</article>
