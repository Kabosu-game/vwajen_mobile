@extends('layouts.app')
@section('title', $kind === 'audio' ? 'Audio Spaces' : 'Vwajèn Live')
@section('layout', 'wide')
@section('content')
    <div class="page-head"><h1>{{ $kind === 'audio' ? 'Audio Spaces' : 'Vwajèn Live' }}</h1>
        <div class="actions">
            <a href="{{ $kind === 'audio' ? route('lives.index') : route('spaces.index') }}" class="btn btn-outline btn-sm"><x-icon name="{{ $kind === 'audio' ? 'live' : 'mic' }}"/>{{ $kind === 'audio' ? 'Lives vidéo' : 'Audio Spaces' }}</a>
            @auth
                @if(auth()->user()->canGoLive())
                    <a href="{{ route('lives.create', ['kind' => $kind]) }}" class="btn btn-live btn-sm"><x-icon name="{{ $kind === 'audio' ? 'mic' : 'live' }}"/>{{ $kind === 'audio' ? __('Ouvrir un Space') : __('Démarrer un live') }}</a>
                @endif
            @endauth
        </div>
    </div>
    @auth @unless(auth()->user()->canGoLive())
        <div class="alert alert-info"><x-icon name="badge-check"/><div class="small">{{ __('Seuls les comptes certifiés peuvent faire des lives.') }} <a href="{{ route('verification.index') }}">{{ __('Demander la vérification') }}</a></div></div>
    @endunless @endauth

    <div class="section-title"><h2><span class="badge badge-live">● LIVE</span> {{ __('En direct') }}</h2></div>
    <div class="grid grid-3">
        @forelse($live as $l) @include('partials.live-tile', ['l' => $l]) @empty <div class="card empty" style="grid-column:1/-1"><x-icon name="live"/><p>{{ __('Aucun live en cours.') }}</p></div> @endforelse
    </div>

    <div class="section-title"><h2>{{ __('Programmés') }}</h2></div>
    <div class="grid grid-3">
        @forelse($upcoming as $l) @include('partials.live-tile', ['l' => $l]) @empty <div class="card empty" style="grid-column:1/-1"><p>{{ __('Aucun live programmé.') }}</p></div> @endforelse
    </div>

    <div class="section-title"><h2>{{ __('Replays') }}</h2></div>
    <div class="grid grid-3">
        @forelse($replays as $l) @include('partials.live-tile', ['l' => $l]) @empty <div class="card empty" style="grid-column:1/-1"><p>{{ __('Aucun replay disponible.') }}</p></div> @endforelse
    </div>
    {{ $replays->links() }}
@endsection
