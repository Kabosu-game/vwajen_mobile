@php
    $me = auth()->user();
    $is = fn (...$p) => request()->routeIs(...$p) ? 'active' : '';
@endphp
<a href="{{ route('home') }}" class="brand" aria-label="Vwajèn — {{ __('Accueil') }}">
    <img src="{{ asset('images/mark.png') }}" alt="" width="38" height="38">
    <span class="brand-name">Vwajèn</span>
</a>

<nav class="nav">
    <a href="{{ route('home') }}" class="nav-item {{ $is('home') }}" @if($is('home')) aria-current="page" @endif><x-icon name="home"/><span class="nav-label">{{ __('Accueil') }}</span></a>
    <a href="{{ route('discover.index') }}" class="nav-item {{ $is('discover.*', 'trends', 'hashtags.*') }}"><x-icon name="compass"/><span class="nav-label">{{ __('Découvrir') }}</span></a>
    <a href="{{ route('search.index') }}" class="nav-item {{ $is('search.*') }}"><x-icon name="search"/><span class="nav-label">{{ __('Rechercher') }}</span></a>
    @auth
        <a href="{{ route('notifications.index') }}" class="nav-item {{ $is('notifications.*') }}"><x-icon name="bell"/><span class="nav-label">{{ __('Notifications') }}</span>
            <span class="nav-count" data-count="notifications" @if(! $unreadNotifications) hidden @endif>{{ $unreadNotifications }}</span></a>
        <a href="{{ route('messages.index') }}" class="nav-item {{ $is('messages.*') }}"><x-icon name="mail"/><span class="nav-label">{{ __('Messages') }}</span>
            <span class="nav-count" data-count="messages" @if(! $unreadMessages) hidden @endif>{{ $unreadMessages }}</span></a>
    @endauth
    <a href="{{ route('shorts.index') }}" class="nav-item {{ $is('shorts.*') }}"><x-icon name="shorts"/><span class="nav-label">Vwajèn Shorts</span></a>
    <a href="{{ route('videos.index') }}" class="nav-item {{ $is('videos.*') }}"><x-icon name="film"/><span class="nav-label">{{ __('Vidéos') }}</span></a>
    <a href="{{ route('lives.index') }}" class="nav-item {{ $is('lives.*', 'spaces.*') }}"><x-icon name="live"/><span class="nav-label">Vwajèn Live</span></a>

    <div class="nav-section nav-label">{{ __('Civique') }}</div>
    <a href="{{ route('candidates.index') }}" class="nav-item {{ $is('candidates.*', 'programs.*') }}"><x-icon name="vote"/><span class="nav-label">{{ __('Candidats') }}</span></a>
    <a href="{{ route('questions.index') }}" class="nav-item {{ $is('questions.*') }}"><x-icon name="question"/><span class="nav-label">Kesyon pou kandida yo</span></a>
    <a href="{{ route('debates.index') }}" class="nav-item {{ $is('debates.*') }}"><x-icon name="debate"/><span class="nav-label">{{ __('Débats') }}</span></a>
    <a href="{{ route('compare.index') }}" class="nav-item {{ $is('compare.*') }}"><x-icon name="compare"/><span class="nav-label">{{ __('Comparer') }}</span></a>
    <a href="{{ route('officials.index') }}" class="nav-item {{ $is('officials.*') }}"><x-icon name="landmark"/><span class="nav-label">{{ __('Élus') }}</span></a>

    <div class="nav-section nav-label">{{ __('Communauté') }}</div>
    <a href="{{ route('events.index') }}" class="nav-item {{ $is('events.*') }}"><x-icon name="calendar"/><span class="nav-label">{{ __('Événements') }}</span></a>
    <a href="{{ route('communities.index') }}" class="nav-item {{ $is('communities.*', 'discussions.*') }}"><x-icon name="users"/><span class="nav-label">{{ __('Communautés') }}</span></a>
    <a href="{{ route('map.index') }}" class="nav-item {{ $is('map.*') }}"><x-icon name="map"/><span class="nav-label">Vwajèn Map</span></a>
    <a href="{{ route('mond.index') }}" class="nav-item {{ $is('mond.*') }}"><x-icon name="globe"/><span class="nav-label">Vwajèn Mond</span></a>

    @auth
        <div class="nav-section nav-label">{{ __('Mon espace') }}</div>
        <a href="{{ $me->profileUrl() }}" class="nav-item {{ request()->is('@'.$me->username) ? 'active' : '' }}"><x-icon name="user"/><span class="nav-label">{{ __('Profil') }}</span></a>
        <a href="{{ route('profile.show', [$me->username, 'tab' => 'saved']) }}" class="nav-item"><x-icon name="bookmark"/><span class="nav-label">{{ __('Enregistrés') }}</span></a>
        @if(in_array($me->account_type, ['candidate', 'official', 'organization']) || $me->candidateProfile)
            <a href="{{ route('candidate.dashboard') }}" class="nav-item {{ $is('candidate.*') }}"><x-icon name="dashboard"/><span class="nav-label">{{ __('Tableau de bord') }}</span></a>
        @endif
        @if($me->isStaff())
            <a href="{{ route('admin.dashboard') }}" class="nav-item"><x-icon name="shield"/><span class="nav-label">{{ __('Administration') }}</span></a>
        @endif
        <a href="{{ route('settings.profile') }}" class="nav-item {{ $is('settings.*') }}"><x-icon name="settings"/><span class="nav-label">{{ __('Paramètres') }}</span></a>
    @endauth

    <details class="nav-more">
        <summary class="nav-item" style="cursor:pointer;list-style:none"><x-icon name="more"/><span class="nav-label">{{ __('Plus') }}</span></summary>
        <a href="{{ route('podcasts.index') }}" class="nav-item"><x-icon name="podcast"/><span class="nav-label">{{ __('Podcasts') }}</span></a>
        <a href="{{ route('spaces.index') }}" class="nav-item"><x-icon name="mic"/><span class="nav-label">Audio Spaces</span></a>
        <a href="{{ route('observatory.index') }}" class="nav-item"><x-icon name="chart"/><span class="nav-label">{{ __('Observatoire citoyen') }}</span></a>
        <a href="{{ route('elections.index') }}" class="nav-item"><x-icon name="archive"/><span class="nav-label">{{ __('Archives électorales') }}</span></a>
        <a href="{{ route('civic.index') }}" class="nav-item"><x-icon name="database"/><span class="nav-label">{{ __('Données civiques') }}</span></a>
        <a href="{{ route('ambassadors.index') }}" class="nav-item"><x-icon name="award"/><span class="nav-label">{{ __('Ambassadeurs') }}</span></a>
        <a href="{{ route('developers') }}" class="nav-item"><x-icon name="code"/><span class="nav-label">API</span></a>
    </details>
</nav>

@auth
    <button type="button" class="btn btn-brand btn-lg btn-block nav-compose" data-modal-open="compose-modal">
        <x-icon name="edit"/><span class="nav-compose-label">{{ __('Publier une Vwa') }}</span>
    </button>
    <div class="dropdown" style="margin-top:auto">
        <button type="button" class="nav-user" data-dropdown style="width:100%;border:0;background:none;font:inherit;cursor:pointer" aria-haspopup="menu" aria-label="{{ __('Menu du compte') }}">
            <img src="{{ $me->avatarUrl() }}" alt="" class="avatar avatar-sm">
            <span class="grow nav-label" style="text-align:left;min-width:0">
                <span class="name truncate" style="display:block">{{ $me->name }}</span>
                <span class="handle small truncate" style="display:block">{{ '@'.$me->username }}</span>
            </span>
            <x-icon name="more" class="nav-label"/>
        </button>
        <div class="menu up left" role="menu">
            <a href="{{ $me->profileUrl() }}" role="menuitem"><x-icon name="user"/>{{ __('Mon profil') }}</a>
            <a href="{{ route('questions.mine') }}" role="menuitem"><x-icon name="question"/>{{ __('Mes questions') }}</a>
            <a href="{{ route('verification.index') }}" role="menuitem"><x-icon name="badge-check"/>{{ __('Vérification') }}</a>
            <button type="button" data-theme-toggle role="menuitem"><x-icon name="moon"/>{{ __('Mode sombre / clair') }}</button>
            <hr>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button type="submit" role="menuitem"><x-icon name="logout"/>{{ __('Se déconnecter') }}</button>
            </form>
        </div>
    </div>
@else
    <div class="panel mt">
        <p class="small">{{ __('Rejoignez la conversation citoyenne.') }}</p>
        <a href="{{ route('register') }}" class="btn btn-brand btn-block">{{ __('Créer un compte') }}</a>
        <a href="{{ route('login') }}" class="btn btn-outline btn-block mt-sm">{{ __('Connexion') }}</a>
    </div>
@endauth
