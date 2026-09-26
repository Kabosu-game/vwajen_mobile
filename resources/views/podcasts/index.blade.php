@extends('layouts.app')
@section('title', __('Podcasts'))
@section('content')
    <div class="page-head"><h1>{{ __('Podcasts') }}</h1><div class="actions">
        <a href="{{ route('spaces.index') }}" class="btn btn-outline btn-sm"><x-icon name="mic"/>Audio Spaces</a>
        @auth<a href="{{ route('podcasts.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus"/>{{ __('Créer un podcast') }}</a>@endauth</div></div>
    @if($latest->isNotEmpty())
        <div class="card"><div class="card-head"><h3>{{ __('Derniers épisodes') }}</h3></div>
            @foreach($latest as $ep)
                <div class="item"><img src="{{ $ep->podcast->coverUrl() }}" alt="" class="avatar avatar-lg avatar-sq">
                    <div class="item-body"><a href="{{ $ep->url() }}" style="font-weight:700;color:var(--text)">{{ $ep->title }}</a><div class="small faint">{{ $ep->podcast->title }} · {{ $ep->published_at?->diffForHumans() }}</div>
                        <audio controls preload="none" class="audio-player mt-sm" src="{{ $ep->audioUrl() }}" data-play="{{ route('podcasts.play', $ep) }}"></audio></div></div>
            @endforeach
        </div>
    @endif
    <div class="chip-row mb"><a href="?" class="pill {{ ! request('category') ? 'active' : '' }}">{{ __('Tous') }}</a>@foreach($categories as $c)<a href="?category={{ $c->slug }}" class="pill {{ request('category') === $c->slug ? 'active' : '' }}">{{ $c->name }}</a>@endforeach</div>
    <div class="grid grid-3">
        @forelse($podcasts as $p)
            <a href="{{ $p->url() }}" class="tile"><div class="tile-media" style="aspect-ratio:1"><img src="{{ $p->coverUrl() }}" alt="" loading="lazy"></div>
                <div class="tile-body"><div class="tile-title clamp-2">{{ $p->title }}</div><div class="small faint">{{ $p->user->name }} · {{ trans_choice(':n épisode|:n épisodes', $p->episodes_count, ['n' => $p->episodes_count]) }}</div></div></a>
        @empty <div class="card empty" style="grid-column:1/-1"><x-icon name="podcast"/><p>{{ __('Aucun podcast.') }}</p></div> @endforelse
    </div>
    {{ $podcasts->links() }}
@endsection
@push('scripts')<script>document.querySelectorAll('audio[data-play]').forEach(a => a.addEventListener('play', () => { if (!a.dataset.sent) { a.dataset.sent = 1; Vwajen.api(a.dataset.play).catch(() => {}); } }, { once: true }));</script>@endpush
