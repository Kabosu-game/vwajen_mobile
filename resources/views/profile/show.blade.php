@extends('layouts.app')
@section('title', $user->name.' (@'.$user->username.')')
@section('og_description', $user->bio)
@section('og_image', $user->avatarUrl())
@php
    $viewer = auth()->user();
    $isMe = $viewer?->id === $user->id;
    $tabs = ['posts' => __('Publications'), 'media' => __('Médias'), 'videos' => __('Vidéos'), 'shorts' => 'Shorts', 'lives' => __('Lives'), 'reposts' => __('Reposts'), 'events' => __('Événements'), 'questions' => __('Questions')];
    if ($isMe) { $tabs['saved'] = __('Enregistrés'); }
@endphp
@section('content')
    <div class="page-head"><a href="{{ url()->previous() }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a>
        <div><h1>{{ $user->name }}@include('partials.verified', ['u' => $user])</h1><div class="small faint">{{ trans_choice(':n publication|:n publications', $user->posts_count, ['n' => short_number($user->posts_count)]) }}</div></div></div>

    <div class="card" style="overflow:hidden">
        <div class="cover" @if($user->coverUrl()) style="background-image:url('{{ $user->coverUrl() }}')" @endif role="img" aria-label="{{ __('Photo de couverture') }}"></div>
        <div class="profile-head">
            <img src="{{ $user->avatarUrl() }}" alt="{{ __('Photo de profil de :name', ['name' => $user->name]) }}" class="avatar avatar-xl" data-lightbox>
            <div class="profile-actions">
                <div class="dropdown">
                    <button type="button" class="btn btn-outline btn-icon" data-dropdown aria-label="{{ __('Plus d\'options') }}"><x-icon name="more"/></button>
                    <div class="menu">
                        <button type="button" data-share data-share-url="{{ $user->profileUrl() }}" data-share-title="{{ $user->name }}" data-share-type="user" data-share-id="{{ $user->id }}"><x-icon name="share"/>{{ __('Partager le profil') }}</button>
                        <button type="button" data-copy="{{ $user->profileUrl() }}"><x-icon name="link"/>{{ __('Copier le lien') }}</button>
                        @auth @unless($isMe)
                            @if($viewer->isFollowing($user))
                                <button type="button" data-action="{{ route('users.notify', $user) }}"><x-icon name="bell"/>{{ __('Notifications de ce compte') }}</button>
                            @endif
                            <button type="button" data-action="{{ route('users.mute', $user) }}"><x-icon name="volume-off"/>{{ __('Masquer de mon fil') }}</button>
                            @if($viewer->hasBlocked($user))
                                <button type="button" data-action="{{ route('users.unblock', $user) }}" data-method="DELETE" data-reload><x-icon name="unlock"/>{{ __('Débloquer') }}</button>
                            @else
                                <button type="button" class="danger" data-action="{{ route('users.block', $user) }}" data-confirm="{{ __('Bloquer :name ?', ['name' => '@'.$user->username]) }}" data-reload><x-icon name="ban"/>{{ __('Bloquer') }}</button>
                            @endif
                            <a href="{{ route('reports.create', ['user', $user->id]) }}" class="danger"><x-icon name="flag"/>{{ __('Signaler') }}</a>
                            @if($viewer->hasPermission('users.view'))<a href="{{ route('admin.users.show', $user->id) }}"><x-icon name="shield"/>{{ __('Voir dans l\'administration') }}</a>@endif
                        @endunless @endauth
                    </div>
                </div>
                @if($isMe)
                    <a href="{{ route('settings.profile') }}" class="btn btn-outline">{{ __('Modifier le profil') }}</a>
                @elseif(! $blocked)
                    @auth<a href="{{ route('messages.create', ['user' => $user->username]) }}" class="btn btn-outline btn-icon" aria-label="{{ __('Envoyer un message') }}"><x-icon name="mail"/></a>@endauth
                    @include('partials.follow-button', ['u' => $user, 'size' => ''])
                @endif
            </div>

            <div class="profile-name">{{ $user->name }}@include('partials.verified', ['u' => $user])</div>
            <div class="handle">{{ '@'.$user->username }}
                @if($viewer && $user->isFollowing($viewer) && ! $isMe)<span class="badge">{{ __('Vous suit') }}</span>@endif</div>
            <div class="row wrap mt-sm" style="gap:.35rem">
                @if($user->account_type !== 'personal')<span class="badge badge-primary">{{ $user->accountTypeLabel() }}</span>@endif
                @if($user->is_verified)<span class="badge badge-gold"><x-icon name="badge-check" style="width:13px;height:13px"/>{{ $user->badgeLabel() }}</span>@endif
                @if($user->is_diaspora)<span class="badge badge-info"><x-icon name="plane" style="width:13px;height:13px"/>{{ __('Diaspora') }}</span>@endif
                @if($user->is_private)<span class="badge"><x-icon name="lock" style="width:13px;height:13px"/>{{ __('Profil privé') }}</span>@endif
            </div>
            @if($user->bio)<p class="mt-sm" style="white-space:pre-line">{!! render_text($user->bio) !!}</p>@endif

            <div class="profile-meta">
                @if($user->show_location && ($user->location || $user->city))<span><x-icon name="pin"/>{{ $user->location ?: $user->city }}{{ $user->country !== 'HT' ? ', '.country_name($user->country) : '' }}</span>@endif
                @if($user->website)<span><x-icon name="link"/><a href="{{ $user->website }}" target="_blank" rel="noopener nofollow">{{ preg_replace('~^https?://(www\.)?~', '', $user->website) }}</a></span>@endif
                @if($user->show_email)<span><x-icon name="mail"/>{{ $user->email }}</span>@endif
                <span><x-icon name="calendar"/>{{ __('Membre depuis :date', ['date' => $user->created_at->translatedFormat('F Y')]) }}</span>
            </div>
            <div class="profile-stats">
                <a href="{{ route('profile.following', $user) }}"><b>{{ short_number($user->following_count) }}</b> {{ __('abonnements') }}</a>
                <a href="{{ route('profile.followers', $user) }}"><b data-followers-count>{{ short_number($user->followers_count) }}</b> {{ __('abonnés') }}</a>
            </div>
            @if($mutualFollowers->isNotEmpty())
                <div class="row small muted mt-sm"><div class="avatar-stack">@foreach($mutualFollowers as $m)<img src="{{ $m->avatarUrl() }}" alt="" class="avatar avatar-xs">@endforeach</div>
                    {{ __('Suivi par :names', ['names' => $mutualFollowers->pluck('name')->implode(', ')]) }}</div>
            @endif

            @if($user->candidateProfile)
                <a href="{{ route('candidates.show', $user->username) }}" class="quote mt row"><x-icon name="vote"/>
                    <div class="grow"><strong>{{ __('Profil candidat') }}</strong><div class="small muted">{{ position_name($user->candidateProfile->position_sought) }} · {{ __('Programme, propositions, sources, questions') }}</div></div><x-icon name="chevron-right"/></a>
            @endif
            @if($user->officialProfile)
                <a href="{{ route('officials.show', $user->username) }}" class="quote mt row"><x-icon name="landmark"/>
                    <div class="grow"><strong>{{ $user->officialProfile->office }}</strong><div class="small muted">{{ __('Suivi public : activités, déclarations, engagements') }}</div></div><x-icon name="chevron-right"/></a>
            @endif
            @if($user->organizationProfile)
                <div class="quote mt row"><x-icon name="briefcase"/><div class="grow"><strong>{{ $user->organizationProfile->legal_name }}</strong>
                    <div class="small muted">{{ __('Organisation') }} · {{ __($user->organizationProfile->org_type) }}@if($user->organizationProfile->is_professional) · <span class="badge badge-primary">{{ __('Compte professionnel') }}</span>@endif</div></div></div>
            @endif
        </div>

        <nav class="tabs" aria-label="{{ __('Contenus du profil') }}">
            @foreach($tabs as $key => $label)
                <a href="{{ route('profile.show', [$user, 'tab' => $key]) }}" class="tab {{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    @if($blocked)
        <div class="card"><div class="empty"><x-icon name="ban"/><h3>{{ __('Contenu indisponible') }}</h3><p>{{ __('Vous ne pouvez pas voir les publications de ce compte.') }}</p></div></div>
    @elseif(! $canView)
        <div class="card"><div class="empty"><x-icon name="lock"/><h3>{{ __('Ce compte est privé') }}</h3><p>{{ __('Abonnez-vous pour voir ses publications.') }}</p></div></div>
    @else
        @if(in_array($tab, ['videos', 'shorts', 'lives']))
            <div class="grid {{ $tab === 'shorts' ? 'grid-3' : 'grid-2' }}">
                @forelse($items as $it)
                    @if($tab === 'lives') @include('partials.live-tile', ['l' => $it]) @else @include('partials.video-tile', ['v' => $it, 'hideAuthor' => true]) @endif
                @empty
                    <div class="card empty" style="grid-column:1/-1"><x-icon name="film"/><p>{{ __('Aucun contenu pour le moment.') }}</p></div>
                @endforelse
            </div>
            {{ $items->links() }}
        @elseif($tab === 'events')
            <div class="card">@forelse($items as $e) @include('partials.event-row', ['e' => $e]) @empty <div class="empty"><x-icon name="calendar"/><p>{{ __('Aucun événement.') }}</p></div> @endforelse</div>
            {{ $items->links() }}
        @elseif($tab === 'questions')
            <div class="card">@forelse($items as $q) @include('partials.question-row', ['q' => $q]) @empty <div class="empty"><x-icon name="question"/><p>{{ __('Aucune question.') }}</p></div> @endforelse</div>
            {{ $items->links() }}
        @elseif(in_array($tab, ['reposts', 'saved']))
            <div class="card feed">@include('partials.feed-items', ['items' => $items])</div>
        @else
            <div class="card feed">
                @forelse($items as $post) @include('partials.post-card', ['post' => $post]) @empty <div class="empty"><x-icon name="message"/><p>{{ __('Aucune publication pour le moment.') }}</p></div> @endforelse
            </div>
            {{ $items->links() }}
        @endif
    @endif
@endsection
