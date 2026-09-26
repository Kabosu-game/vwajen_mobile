@extends('settings.layout')
@section('title', __('Comptes bloqués'))
@section('settings')
    <div class="card"><div class="card-head"><h3>{{ __('Comptes bloqués') }}</h3></div>
        @forelse($blocks as $b)
            <div class="user-card"><img src="{{ $b->blocked->avatarUrl() }}" alt="" class="avatar"><div class="info"><strong>{{ $b->blocked->name }}</strong><div class="handle small">{{ '@'.$b->blocked->username }}</div></div>
                <button class="btn btn-outline btn-sm" data-action="{{ route('users.unblock', $b->blocked) }}" data-method="DELETE" data-remove-closest=".user-card">{{ __('Débloquer') }}</button></div>
        @empty <div class="p muted small">{{ __('Aucun compte bloqué.') }}</div> @endforelse
    </div>
    {{ $blocks->links() }}
    <div class="card"><div class="card-head"><h3>{{ __('Comptes masqués') }}</h3></div>
        @forelse($muted as $m)
            <div class="user-card"><img src="{{ $m->muted->avatarUrl() }}" alt="" class="avatar"><div class="info"><strong>{{ $m->muted->name }}</strong><div class="handle small">{{ '@'.$m->muted->username }}</div></div>
                <button class="btn btn-outline btn-sm" data-action="{{ route('users.mute', $m->muted) }}" data-remove-closest=".user-card">{{ __('Réafficher') }}</button></div>
        @empty <div class="p muted small">{{ __('Aucun compte masqué.') }}</div> @endforelse
    </div>
@endsection
