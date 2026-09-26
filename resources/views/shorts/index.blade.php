@extends('layouts.app')
@section('title', 'Vwajèn Shorts')
@section('layout', 'wide')
@if($start)@section('og_image', $start->thumbnailUrl() ?? asset('images/logo-400.jpg'))@endif
@section('content')
    <div class="shorts-page" id="shorts" data-feed="{{ route('shorts.feed') }}" aria-label="Vwajèn Shorts" tabindex="0">
        @include('shorts.items', ['shorts' => $shorts])
        @if($shorts->isEmpty())
            <div class="short"><div class="empty" style="color:#fff"><x-icon name="shorts"/><h3 style="color:#fff">{{ __('Aucun Short pour le moment') }}</h3>
                @auth<a href="{{ route('videos.create', ['kind' => 'short']) }}" class="btn btn-brand">{{ __('Publier le premier Short') }}</a>@endauth</div></div>
        @endif
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('js/shorts.js') }}?v={{ filemtime(public_path('js/shorts.js')) }}" defer></script>
@endpush
