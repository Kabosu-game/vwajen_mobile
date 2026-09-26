@extends('layouts.app')
@section('title', $post->user->name.' : '.\Illuminate\Support\Str::limit($post->body ?? __('Vwa'), 60))
@section('og_description', \Illuminate\Support\Str::limit($post->body, 180))
@if($img = $post->media->firstWhere('type', 'image'))@section('og_image', $img->url())@endif
@section('content')
    <div class="page-head"><a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Vwa') }}</h1></div>
    @if($post->is_hidden)
        <div class="alert alert-warning"><x-icon name="eye-off"/><div>{{ __('Ce contenu est masqué par la modération. Seuls vous et l\'équipe pouvez le voir.') }}</div></div>
    @endif
    <div class="card">@include('partials.post-card', ['post' => $post, 'full' => true])</div>
    @if($post->edits->isNotEmpty() && auth()->id() === $post->user_id)
        <details class="card"><summary class="card-body" style="cursor:pointer">{{ __('Historique des modifications') }} ({{ $post->edits->count() }})</summary>
            @foreach($post->edits as $edit)
                <div class="item"><div class="item-body"><div class="small faint">{{ $edit->created_at->translatedFormat('d M Y H:i') }}</div><div>{{ $edit->body }}</div></div></div>
            @endforeach
        </details>
    @endif
    @include('partials.comments', ['target' => $post, 'type' => 'post', 'comments' => $comments])
@endsection
