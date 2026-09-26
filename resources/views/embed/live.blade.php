@extends('embed.layout')
@section('title', $live->title)
@section('content')
    <div style="position:relative;height:100vh;display:grid;place-items:center;color:#fff;text-align:center">
        @if($live->replay)
            <video controls playsinline preload="metadata" src="{{ $live->replay->videoUrl() }}" style="max-height:100vh;width:100%"></video>
        @elseif($live->playback_url && $live->isLive())
            <video id="v" controls playsinline autoplay muted style="max-height:100vh;width:100%"></video>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/hls.js/1.5.13/hls.min.js"></script>
            <script>const v=document.getElementById('v'),u=@js($live->playback_url);if(v.canPlayType('application/vnd.apple.mpegurl'))v.src=u;else if(window.Hls&&Hls.isSupported()){const h=new Hls();h.loadSource(u);h.attachMedia(v);}</script>
        @else
            <div>
                @if($live->coverUrl())<img src="{{ $live->coverUrl() }}" alt="" style="max-height:60vh;margin:0 auto 1rem;border-radius:12px">@endif
                <h1 style="font-size:1.3rem;color:#fff">{{ $live->title }}</h1>
                <p>@if($live->isLive())<span class="badge badge-live">● {{ __('EN DIRECT') }}</span>@elseif($live->scheduled_at){{ $live->scheduled_at->translatedFormat('d F Y · H:i') }}@endif</p>
                <a href="{{ $live->url() }}" target="_blank" rel="noopener" class="btn btn-live">{{ __('Voir sur Vwajèn') }}</a>
            </div>
        @endif
        <div class="embed-bar"><img src="{{ asset('images/icon-32.png') }}" alt="" width="20" height="20"><a href="{{ $live->url() }}" target="_blank" rel="noopener"><strong>{{ $live->title }}</strong></a><span style="margin-left:auto">{{ $live->user->name }} · Vwajèn Live</span></div>
    </div>
@endsection
