<div class="user-card" data-item="user:{{ $u->id }}">
    <a href="{{ $u->profileUrl() }}" tabindex="-1" aria-hidden="true"><img src="{{ $u->avatarUrl() }}" alt="" class="avatar" loading="lazy"></a>
    <div class="info">
        <div class="truncate"><a href="{{ $u->profileUrl() }}" class="name">{{ $u->name }}</a>@include('partials.verified', ['u' => $u])</div>
        <div class="handle small truncate">{{ '@'.$u->username }}
            @if($u->account_type !== 'personal') · <span class="faint">{{ $u->accountTypeLabel() }}</span>@endif</div>
        @if(! ($compact ?? false) && $u->bio)<div class="small muted clamp-2">{{ $u->bio }}</div>@endif
    </div>
    @include('partials.follow-button', ['u' => $u])
</div>
