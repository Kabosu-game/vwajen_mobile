@extends('layouts.app')
@section('title', __('Événements'))
@section('content')
    <div class="page-head"><h1>{{ __('Événements') }}</h1>
        <div class="actions"><a href="{{ route('map.index') }}" class="btn btn-outline btn-sm"><x-icon name="map"/>{{ __('Carte') }}</a>@auth<a href="{{ route('events.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus"/>{{ __('Créer') }}</a>@endauth</div></div>
    <nav class="tabs card" style="margin-bottom:.75rem">
        <a href="{{ route('events.index', request()->except('when', 'page')) }}" class="tab {{ $when === 'upcoming' ? 'active' : '' }}">{{ __('À venir') }}</a>
        <a href="{{ route('events.index', ['when' => 'past'] + request()->except('when', 'page')) }}" class="tab {{ $when === 'past' ? 'active' : '' }}">{{ __('Passés') }}</a>
    </nav>
    <form method="GET" class="filters">
        @if($when === 'past')<input type="hidden" name="when" value="past">@endif
        <select name="department" class="select" aria-label="{{ __('Département') }}"><option value="">{{ __('Tous les départements') }}</option>@foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}" @selected(request('department') === $k)>{{ $d['name'] }}</option>@endforeach</select>
        <input name="city" value="{{ request('city') }}" class="input" placeholder="{{ __('Ville') }}" aria-label="{{ __('Ville') }}">
        <select name="category" class="select" aria-label="{{ __('Thème') }}"><option value="">{{ __('Tous les thèmes') }}</option>@foreach($categories as $c)<option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>@endforeach</select>
        <select name="online" class="select" aria-label="{{ __('Format') }}"><option value="">{{ __('Présentiel et en ligne') }}</option><option value="1" @selected(request('online') === '1')>{{ __('En ligne') }}</option><option value="0" @selected(request('online') === '0')>{{ __('Présentiel') }}</option></select>
        <button class="btn btn-outline">{{ __('Filtrer') }}</button>
    </form>
    <div class="card">@forelse($events as $e) @include('partials.event-row', ['e' => $e]) @empty <div class="empty"><x-icon name="calendar"/><p>{{ __('Aucun événement.') }}</p></div> @endforelse</div>
    {{ $events->links() }}
@endsection
