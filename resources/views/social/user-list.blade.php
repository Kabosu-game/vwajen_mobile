@extends('layouts.app')
@section('title', $title)
@section('content')
    <div class="page-head"><a href="{{ isset($owner) ? $owner->profileUrl() : url()->previous() }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ $title }}</h1></div>
    @isset($owner)
        <nav class="tabs card" style="margin-bottom:.75rem">
            <a href="{{ route('profile.followers', $owner) }}" class="tab {{ request()->routeIs('profile.followers') ? 'active' : '' }}">{{ __('Abonnés') }}</a>
            <a href="{{ route('profile.following', $owner) }}" class="tab {{ request()->routeIs('profile.following') ? 'active' : '' }}">{{ __('Abonnements') }}</a>
        </nav>
    @endisset
    <div class="card">
        @forelse($users as $u) @include('partials.user-card', ['u' => $u]) @empty <div class="empty"><x-icon name="users"/><p>{{ __('Personne pour le moment.') }}</p></div> @endforelse
    </div>
    {{ $users->links() }}
@endsection
