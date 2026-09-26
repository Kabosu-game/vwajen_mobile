@extends('layouts.app')
@section('title', '#'.$tag->name)
@section('content')
    <div class="page-head"><a href="{{ url()->previous() }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a>
        <div><h1>#{{ $tag->name }}</h1><div class="small faint">{{ trans_choice(':n publication|:n publications', $tag->uses_count, ['n' => short_number($tag->uses_count)]) }} · {{ trans_choice(':n abonné|:n abonnés', $tag->followers()->count(), ['n' => $tag->followers()->count()]) }}</div></div>
        <div class="actions">
            <button type="button" class="btn {{ $following ? 'btn-outline' : 'btn-dark' }} btn-sm" data-action="{{ route('hashtags.follow', $tag->name) }}" data-auth data-reload>
                {{ $following ? __('Ne plus suivre') : __('Suivre le hashtag') }}</button>
            <button type="button" class="btn btn-ghost btn-icon" data-share data-share-url="{{ $tag->url() }}" data-share-title="#{{ $tag->name }}" aria-label="{{ __('Partager') }}"><x-icon name="share"/></button>
        </div>
    </div>
    <nav class="tabs card" style="margin-bottom:.75rem">
        <a href="?tab=top" class="tab {{ $tab === 'top' ? 'active' : '' }}">{{ __('Populaires') }}</a>
        <a href="?tab=recent" class="tab {{ $tab === 'recent' ? 'active' : '' }}">{{ __('Récentes') }}</a>
        <a href="?tab=videos" class="tab {{ $tab === 'videos' ? 'active' : '' }}">{{ __('Vidéos') }}</a>
    </nav>
    @if($tab === 'videos')
        <div class="grid grid-3">@forelse($items as $v) @include('partials.video-tile', ['v' => $v]) @empty <div class="card empty" style="grid-column:1/-1"><p>{{ __('Aucune vidéo.') }}</p></div> @endforelse</div>
    @else
        <div class="card feed">@forelse($items as $post) @include('partials.post-card', ['post' => $post]) @empty <div class="empty"><x-icon name="hash"/><p>{{ __('Aucune publication.') }}</p></div> @endforelse</div>
    @endif
    {{ $items->links() }}
@endsection
