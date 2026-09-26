@php
    $viewer = auth()->user();
    $trends = app(\App\Http\Controllers\DiscoverController::class)->trendingHashtags(6);
    $suggestionIds = \Illuminate\Support\Facades\Cache::remember('sidebar.suggest.'.($viewer?->id ?? 'guest'), now()->addMinutes(10), function () use ($viewer) {
        $exclude = $viewer ? array_merge($viewer->followingIds(), [$viewer->id], $viewer->blockedIdsBothWays()) : [];
        return \App\Models\User::where('status', 'active')->where('searchable', true)->whereNotIn('id', $exclude ?: [0])
            ->orderByDesc('is_verified')->orderByDesc('followers_count')->limit(4)->pluck('id')->all();
    });
    $suggestions = \App\Models\User::whereIn('id', $suggestionIds)->orderByDesc('is_verified')->orderByDesc('followers_count')->get();
    $nextEvents = \App\Models\Event::where('is_hidden', false)->where('visibility', 'public')->where('starts_at', '>=', now())->orderBy('starts_at')->limit(3)->get();
    $electionDate = setting('election_date');
@endphp

<form action="{{ route('search.index') }}" method="GET" class="search-box mb" role="search" data-suggest>
    <x-icon name="search"/>
    <label for="side-search" class="sr-only">{{ __('Rechercher') }}</label>
    <input id="side-search" type="search" name="q" class="input" placeholder="{{ __('Rechercher sur Vwajèn') }}" autocomplete="off">
    <div class="suggest" hidden></div>
</form>

@if($electionDate)
    @php $d = \Carbon\Carbon::parse($electionDate); @endphp
    @if($d->isFuture())
        <div class="hero" style="padding:1.1rem">
            <div class="small" style="opacity:.85">{{ __('Prochaines élections') }}</div>
            <div style="font-size:1.6rem;font-weight:800">{{ __('J-:n', ['n' => (int) now()->diffInDays($d)]) }}</div>
            <div class="small">{{ $d->translatedFormat('d F Y') }}</div>
            <a href="{{ route('compare.index') }}" class="btn btn-sm mt-sm" style="background:#fff;color:#0b1f5c">{{ __('Comparer les programmes') }}</a>
        </div>
    @endif
@endif

@if($trends->isNotEmpty())
    <div class="panel" style="padding:.9rem 0">
        <h3 style="padding:0 1rem">{{ __('Tendances') }}</h3>
        @foreach($trends as $t)
            <a href="{{ route('hashtags.show', $t->name) }}" class="trend">
                <div class="small faint">{{ __('Tendance') }}</div>
                <div style="font-weight:700">#{{ $t->name }}</div>
                <div class="small faint">{{ trans_choice(':n publication|:n publications', $t->recent_uses, ['n' => short_number($t->recent_uses)]) }}</div>
            </a>
        @endforeach
        <a href="{{ route('trends') }}" class="trend small">{{ __('Voir plus') }}</a>
    </div>
@endif

@if($suggestions->isNotEmpty())
    <div class="panel" style="padding:.9rem 0">
        <h3 style="padding:0 1rem">{{ __('Suggestions') }}</h3>
        @foreach($suggestions as $u)
            @include('partials.user-card', ['u' => $u, 'compact' => true])
        @endforeach
        <a href="{{ route('discover.index', ['section' => 'users']) }}" class="trend small">{{ __('Voir plus') }}</a>
    </div>
@endif

@if($nextEvents->isNotEmpty())
    <div class="panel">
        <h3>{{ __('Événements à venir') }}</h3>
        <div class="stack-sm">
            @foreach($nextEvents as $e)
                <a href="{{ $e->url() }}" class="row" style="color:var(--text)">
                    <div class="date-box"><div class="m">{{ $e->localStart()->translatedFormat('M') }}</div><div class="d">{{ $e->localStart()->format('d') }}</div></div>
                    <div class="grow"><div class="clamp-2" style="font-weight:650">{{ $e->title }}</div>
                        <div class="small faint">{{ $e->is_online ? __('En ligne') : $e->city }}</div></div>
                </a>
            @endforeach
        </div>
    </div>
@endif

<div class="lang-switch" style="padding:0 1rem .6rem" aria-label="{{ __('Langue') }}">
    @foreach(config('vwajen.locales') as $code => $l)
        <a href="{{ route('locale.switch', $code) }}" class="{{ app()->getLocale() === $code ? 'active' : '' }}" lang="{{ $code }}" hreflang="{{ $code }}">{{ $l['name'] }}</a>
    @endforeach
</div>
<nav class="footer-links" aria-label="{{ __('Liens utiles') }}">
    <a href="{{ route('pages.show', 'about') }}">{{ __('À propos') }}</a>
    <a href="{{ route('pages.show', 'rules') }}">{{ __('Règles de la communauté') }}</a>
    <a href="{{ route('pages.show', 'privacy') }}">{{ __('Confidentialité') }}</a>
    <a href="{{ route('pages.show', 'terms') }}">{{ __('Conditions') }}</a>
    <a href="{{ route('pages.show', 'cookies') }}">{{ __('Cookies') }}</a>
    <a href="{{ route('pages.show', 'accessibility') }}">{{ __('Accessibilité') }}</a>
    <a href="{{ route('developers') }}">API</a>
    <span>© {{ date('Y') }} Vwajèn</span>
</nav>
<form method="POST" action="{{ route('newsletter.subscribe') }}" class="panel mt">@csrf
    <label for="nl-email" class="label">{{ __('Newsletter civique') }}</label>
    <div class="input-group"><input id="nl-email" type="email" name="email" required class="input input-sm" placeholder="email@exemple.com"><button class="btn btn-primary btn-sm">OK</button></div>
</form>
