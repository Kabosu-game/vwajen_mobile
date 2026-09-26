@extends('embed.layout')
@section('title', $video->title ?: $video->user->name)
@section('content')
    <div style="position:relative;height:100vh;display:grid;place-items:center">
        <video controls playsinline preload="metadata" src="{{ $video->videoUrl() }}" @if($video->thumbnailUrl()) poster="{{ $video->thumbnailUrl() }}" @endif style="max-height:100vh;width:100%">
            @foreach($video->subtitles as $s)<track kind="subtitles" src="{{ $s->url() }}" srclang="{{ $s->lang }}" label="{{ $s->label }}">@endforeach
        </video>
        <div class="embed-bar"><img src="{{ asset('images/icon-32.png') }}" alt="" width="20" height="20">
            <a href="{{ $video->url() }}" target="_blank" rel="noopener"><strong>{{ $video->title ?: $video->user->name }}</strong></a>
            <span style="margin-left:auto">{{ '@'.$video->user->username }} · Vwajèn</span></div>
    </div>
@endsection
