@extends('layouts.app')
@section('title', __('Débats'))
@section('content')
    <div class="page-head"><h1>{{ __('Débats') }}</h1>
        <div class="actions">@auth<a href="{{ route('debates.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus"/>{{ __('Créer un débat') }}</a>@endauth</div></div>
    <nav class="tabs card" style="margin-bottom:.75rem">
        <a href="?tab=upcoming" class="tab {{ $tab === 'upcoming' ? 'active' : '' }}">{{ __('À venir') }}</a>
        <a href="?tab=live" class="tab {{ $tab === 'live' ? 'active' : '' }}">{{ __('En direct') }} @if($liveCount)<span class="badge badge-live">{{ $liveCount }}</span>@endif</a>
        <a href="?tab=replays" class="tab {{ $tab === 'replays' ? 'active' : '' }}">{{ __('Replays') }}</a>
        <a href="?tab=archived" class="tab {{ $tab === 'archived' ? 'active' : '' }}">{{ __('Archives') }}</a>
    </nav>
    <div class="grid grid-2">
        @forelse($debates as $d) @include('partials.debate-tile', ['d' => $d]) @empty
            <div class="card empty" style="grid-column:1/-1"><x-icon name="debate"/><p>{{ __('Aucun débat dans cette catégorie.') }}</p></div>
        @endforelse
    </div>
    {{ $debates->links() }}
@endsection
