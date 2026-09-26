@php
    $viewer = auth()->user();
    $state = ! $viewer ? 'guest' : ($viewer->isFollowing($u) ? 'following' : ($viewer->hasPendingFollow($u) ? 'pending' : 'none'));
    $size = $size ?? 'btn-sm';
@endphp
@if(! $viewer || $viewer->id !== $u->id)
    @if($state === 'following')
        <button type="button" class="btn btn-outline {{ $size }} is-following" data-follow="{{ route('users.follow', $u) }}" data-unfollow="{{ route('users.unfollow', $u) }}" data-state="following">
            <span class="when-idle">{{ __('Abonné') }}</span><span class="when-hover">{{ __('Ne plus suivre') }}</span></button>
    @elseif($state === 'pending')
        <button type="button" class="btn btn-outline {{ $size }}" data-follow="{{ route('users.follow', $u) }}" data-unfollow="{{ route('users.unfollow', $u) }}" data-state="pending">{{ __('Demandé') }}</button>
    @else
        <button type="button" class="btn btn-dark {{ $size }}" data-follow="{{ $viewer ? route('users.follow', $u) : '' }}" data-unfollow="{{ $viewer ? route('users.unfollow', $u) : '' }}" data-state="none" data-auth>{{ __('Suivre') }}</button>
    @endif
@endif
