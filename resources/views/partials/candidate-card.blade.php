@php $p = $u->candidateProfile; @endphp
<div class="tile" data-item="user:{{ $u->id }}">
    <div class="tile-body center">
        <a href="{{ route('candidates.show', $u->username) }}"><img src="{{ $p?->photoUrl() ?? $u->avatarUrl() }}" alt="" class="avatar avatar-lg" style="margin:0 auto .5rem;width:84px;height:84px" loading="lazy"></a>
        <a href="{{ route('candidates.show', $u->username) }}" class="name" style="display:block">{{ $p?->full_name ?? $u->name }}@include('partials.verified', ['u' => $u])</a>
        <div class="small muted">{{ position_name($p?->position_sought) }}@if($p?->constituency) · {{ $p->constituency }}@endif</div>
        @if($p?->party && $u->show_political)<div class="small faint mt-sm">{{ $p->party }}</div>@endif
        @if($p?->department)<div class="small faint">{{ department_name($p->department) }}</div>@endif
        <div class="row mt" style="justify-content:center">
            @include('partials.follow-button', ['u' => $u])
            <a href="{{ route('questions.create', ['to' => $u->username]) }}" class="btn btn-soft btn-sm" title="{{ __('Poser une question') }}"><x-icon name="question"/></a>
        </div>
    </div>
</div>
