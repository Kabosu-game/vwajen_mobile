@switch($t)
    @case('users')
    @case('candidates')
        <div class="card">@foreach($res as $u) @include('partials.user-card', ['u' => $u]) @endforeach</div>
        @break
    @case('posts')
        <div class="card feed">@foreach($res as $post) @include('partials.post-card', ['post' => $post]) @endforeach</div>
        @break
    @case('videos')
    @case('shorts')
        <div class="grid {{ $t === 'shorts' ? 'grid-4' : 'grid-2' }}">@foreach($res as $v) @include('partials.video-tile', ['v' => $v]) @endforeach</div>
        @break
    @case('lives')
        <div class="grid grid-2">@foreach($res as $l) @include('partials.live-tile', ['l' => $l]) @endforeach</div>
        @break
    @case('events')
        <div class="card">@foreach($res as $e) @include('partials.event-row', ['e' => $e]) @endforeach</div>
        @break
    @case('debates')
        <div class="grid grid-2">@foreach($res as $d) @include('partials.debate-tile', ['d' => $d]) @endforeach</div>
        @break
    @case('questions')
        <div class="card">@foreach($res as $q) @include('partials.question-row', ['q' => $q]) @endforeach</div>
        @break
    @case('hashtags')
        <div class="card">@foreach($res as $h)<a href="{{ $h->url() }}" class="item" style="color:var(--text)"><x-icon name="hash"/><div class="item-body"><strong>#{{ $h->name }}</strong><div class="small faint">{{ trans_choice(':n publication|:n publications', $h->uses_count, ['n' => $h->uses_count]) }}</div></div></a>@endforeach</div>
        @break
    @case('communities')
        <div class="card">@foreach($res as $c)<a href="{{ $c->url() }}" class="item" style="color:var(--text)"><img src="{{ $c->avatarUrl() }}" alt="" class="avatar avatar-sq"><div class="item-body"><strong>{{ $c->name }}</strong><div class="small faint">{{ trans_choice(':n membre|:n membres', $c->members_count, ['n' => $c->members_count]) }}</div></div></a>@endforeach</div>
        @break
@endswitch
