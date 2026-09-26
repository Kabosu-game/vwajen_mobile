@extends('embed.layout')
@section('title', $post->user->name)
@section('content')
    <div style="background:var(--surface);min-height:100vh;padding:1rem">
        <div class="row"><img src="{{ $post->user->avatarUrl() }}" alt="" class="avatar"><div><strong>{{ $post->user->name }}</strong>@include('partials.verified', ['u' => $post->user])<div class="small faint">{{ '@'.$post->user->username }} · {{ $post->created_at->translatedFormat('d M Y') }}</div></div>
            <a href="{{ $post->url() }}" target="_blank" rel="noopener" class="right"><img src="{{ asset('images/icon-32.png') }}" alt="Vwajèn" width="24" height="24"></a></div>
        <div class="post-text big mt-sm">{!! render_text($post->body) !!}</div>
        @foreach($post->media->where('type', 'image')->take(1) as $m)<img src="{{ $m->url() }}" alt="{{ $m->alt }}" style="border-radius:12px;margin-top:.5rem">@endforeach
        <a href="{{ $post->url() }}" target="_blank" rel="noopener" class="small">{{ __('Voir sur Vwajèn') }}</a>
    </div>
@endsection
