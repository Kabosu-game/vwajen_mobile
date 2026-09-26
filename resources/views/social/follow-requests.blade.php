@extends('layouts.app')
@section('title', __('Demandes d\'abonnement'))
@section('content')
    <div class="page-head"><a href="{{ route('notifications.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Demandes d\'abonnement') }}</h1></div>
    <div class="card">
        @forelse($requests as $r)
            <div class="user-card">
                <img src="{{ $r->follower->avatarUrl() }}" alt="" class="avatar">
                <div class="info"><a href="{{ $r->follower->profileUrl() }}" class="name">{{ $r->follower->name }}</a><div class="handle small">{{ '@'.$r->follower->username }} · {{ $r->created_at->diffForHumans() }}</div></div>
                <form method="POST" action="{{ route('follow.accept', $r) }}">@csrf<button class="btn btn-primary btn-sm">{{ __('Accepter') }}</button></form>
                <form method="POST" action="{{ route('follow.decline', $r) }}">@csrf @method('DELETE')<button class="btn btn-outline btn-sm">{{ __('Refuser') }}</button></form>
            </div>
        @empty
            <div class="empty"><x-icon name="user-check"/><p>{{ __('Aucune demande en attente.') }}</p></div>
        @endforelse
    </div>
    {{ $requests->links() }}
@endsection
