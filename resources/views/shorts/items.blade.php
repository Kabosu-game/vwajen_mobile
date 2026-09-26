@foreach($shorts as $s)
    @php $viewer = auth()->user(); @endphp
    <section class="short" data-short="{{ $s->id }}" data-url="{{ $s->url() }}" data-view="{{ route('videos.view', $s) }}" aria-label="{{ $s->title ?: __('Short de :name', ['name' => $s->user->name]) }}">
        <div class="s-top">
            <a href="{{ route('home') }}" class="btn btn-icon" style="background:rgba(0,0,0,.35);color:#fff" aria-label="{{ __('Accueil') }}"><x-icon name="arrow-left"/></a>
            <div class="row">
                @auth<a href="{{ route('videos.create', ['kind' => 'short']) }}" class="btn btn-icon" style="background:rgba(0,0,0,.35);color:#fff" aria-label="{{ __('Publier un Short') }}"><x-icon name="plus"/></a>@endauth
                <button type="button" class="btn btn-icon" style="background:rgba(0,0,0,.35);color:#fff" data-mute aria-label="{{ __('Son') }}"><x-icon name="volume-off"/></button>
            </div>
        </div>
        <video src="{{ $s->videoUrl(($viewer?->data_saver && isset($s->qualities[240])) ? 240 : null) }}" loop playsinline muted preload="{{ $loop->first ? 'auto' : 'none' }}"
               @if($s->thumbnailUrl()) poster="{{ $s->thumbnailUrl() }}" @endif>
            @foreach($s->subtitles as $sub)<track kind="subtitles" src="{{ $sub->url() }}" srclang="{{ $sub->lang }}" label="{{ $sub->label }}" @if($sub->lang === app()->getLocale()) default @endif>@endforeach
        </video>
        <div class="paused-ind"><x-icon name="play"/></div>
        <div class="s-side">
            <a href="{{ $s->user->profileUrl() }}" aria-label="{{ $s->user->name }}"><img src="{{ $s->user->avatarUrl() }}" alt="" class="avatar" style="border:2px solid #fff"></a>
            <button type="button" class="s-btn {{ $s->isLikedBy($viewer) ? 'active' : '' }}" data-s-like="{{ route('interact.like', ['video', $s->id]) }}" aria-label="{{ __('J\'aime') }}">
                <span class="circle"><x-icon name="heart"/></span><span data-count>{{ short_number($s->likes_count) }}</span></button>
            <a href="{{ route('videos.show', [$s, 'player' => 1]) }}#comments" class="s-btn" aria-label="{{ __('Commentaires') }}"><span class="circle"><x-icon name="comment"/></span>{{ short_number($s->comments_count) }}</a>
            <button type="button" class="s-btn {{ $s->isRepostedBy($viewer) ? 'active' : '' }}" data-s-repost="{{ route('interact.repost', ['video', $s->id]) }}" aria-label="{{ __('Repost') }}"><span class="circle"><x-icon name="repost"/></span>{{ short_number($s->reposts_count) }}</button>
            <button type="button" class="s-btn {{ $s->isBookmarkedBy($viewer) ? 'saved' : '' }}" data-s-save="{{ route('interact.bookmark', ['video', $s->id]) }}" aria-label="{{ __('Enregistrer') }}"><span class="circle"><x-icon name="bookmark"/></span></button>
            <button type="button" class="s-btn" data-share data-share-url="{{ $s->url() }}" data-share-title="{{ $s->title ?: $s->user->name }}" data-share-type="video" data-share-id="{{ $s->id }}" data-share-native-first aria-label="{{ __('Partager') }}"><span class="circle"><x-icon name="share"/></span>{{ short_number($s->shares_count) }}</button>
            <a href="{{ route('reports.create', ['video', $s->id]) }}" class="s-btn" aria-label="{{ __('Signaler') }}"><span class="circle"><x-icon name="flag"/></span></a>
        </div>
        <div class="s-overlay">
            <div class="row"><a href="{{ $s->user->profileUrl() }}" class="name" style="color:#fff">{{ '@'.$s->user->username }}</a>@include('partials.verified', ['u' => $s->user])
                @include('partials.follow-button', ['u' => $s->user])</div>
            @if($s->title)<div style="font-weight:700;margin-top:.3rem">{{ $s->title }}</div>@endif
            @if($s->description)<div class="small clamp-2" style="margin-top:.2rem">{!! render_text($s->description) !!}</div>@endif
            <div class="small" style="opacity:.8;margin-top:.3rem"><x-icon name="eye" style="width:13px;height:13px;vertical-align:-2px"/> {{ short_number($s->views_count) }}</div>
        </div>
    </section>
@endforeach
