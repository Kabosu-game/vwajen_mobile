@extends('layouts.app')
@section('title', __('Vidéos'))
@section('layout', 'wide')
@section('content')
    <div class="page-head"><h1>{{ __('Vidéos') }}</h1>
        <div class="actions">
            <a href="{{ route('shorts.index') }}" class="btn btn-outline btn-sm"><x-icon name="shorts"/>Shorts</a>
            @auth<a href="{{ route('videos.create') }}" class="btn btn-primary btn-sm"><x-icon name="upload"/>{{ __('Publier une vidéo') }}</a>@endauth
        </div></div>
    <div class="pills mb">
        <a href="?sort=recent" class="pill {{ $sort === 'recent' && ! $kind ? 'active' : '' }}">{{ __('Récentes') }}</a>
        <a href="?sort=popular" class="pill {{ $sort === 'popular' ? 'active' : '' }}">{{ __('Populaires') }}</a>
        <a href="?kind=replay" class="pill {{ $kind === 'replay' ? 'active' : '' }}">{{ __('Replays de lives et débats') }}</a>
    </div>
    <div class="grid grid-3">
        @forelse($videos as $v) @include('partials.video-tile', ['v' => $v]) @empty
            <div class="card empty" style="grid-column:1/-1"><x-icon name="film"/><p>{{ __('Aucune vidéo pour le moment.') }}</p></div>
        @endforelse
    </div>
    {{ $videos->links() }}
@endsection
