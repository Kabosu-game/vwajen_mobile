@extends('layouts.app')
@section('title', $event->title)
@section('og_image', $event->coverUrl() ?? asset('images/logo-400.jpg'))
@section('og_description', $event->localStart()->translatedFormat('l d F Y H:i').' — '.($event->is_online ? __('En ligne') : $event->city))
@php $viewer = auth()->user(); @endphp
@if($event->lat)
    @push('head')<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">@endpush
@endif
@section('content')
    <div class="page-head"><a href="{{ route('events.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1 class="truncate">{{ $event->title }}</h1></div>
    <article class="card" style="overflow:hidden" id="event-{{ $event->id }}" data-item="event:{{ $event->id }}">
        @if($event->coverUrl())<img src="{{ $event->coverUrl() }}" alt="" style="width:100%;max-height:320px;object-fit:cover">@endif
        <div class="card-body">
            <div class="row top">
                <div class="date-box"><div class="m">{{ $event->localStart()->translatedFormat('M') }}</div><div class="d">{{ $event->localStart()->format('d') }}</div></div>
                <div class="grow">
                    <h2 style="margin:0">{{ $event->title }}</h2>
                    <div class="small muted">{{ __('Organisé par') }} <a href="{{ $event->user->profileUrl() }}">{{ $event->user->name }}</a>@include('partials.verified', ['u' => $event->user])
                        @if($event->community) · <a href="{{ $event->community->url() }}">{{ $event->community->name }}</a>@endif</div>
                </div>
                @include('partials.item-menu', ['model' => $event, 'type' => 'event'])
            </div>
            @if($event->isPast())<div class="alert alert-warning mt"><x-icon name="clock"/><div>{{ __('Cet événement est terminé.') }}</div></div>@endif
            <dl class="kv mt">
                <dt><x-icon name="calendar" style="width:15px;height:15px;vertical-align:-2px"/> {{ __('Date') }}</dt><dd>{{ $event->localStart()->translatedFormat('l d F Y') }}</dd>
                <dt><x-icon name="clock" style="width:15px;height:15px;vertical-align:-2px"/> {{ __('Heure') }}</dt><dd>{{ $event->localStart()->format('H:i') }}@if($event->ends_at) – {{ $event->localEnd()->format('H:i') }}@endif
                    <span class="faint small">({{ __('Fuseau horaire') }} : {{ $event->timezone }})</span>
                    @if($viewer && $viewer->country !== 'HT' && $event->timezone === 'America/Port-au-Prince')<div class="small faint" data-local-time="{{ $event->starts_at->toIso8601String() }}"></div>@endif</dd>
                @if($event->is_online)
                    <dt><x-icon name="globe" style="width:15px;height:15px;vertical-align:-2px"/> {{ __('En ligne') }}</dt>
                    <dd>@if($myRsvp === 'going' || $event->canManage($viewer))<a href="{{ $event->online_url }}" target="_blank" rel="noopener">{{ __('Lien de participation') }}</a>@else<span class="faint">{{ __('Le lien est visible après inscription (« Je participe »).') }}</span>@endif</dd>
                @else
                    <dt><x-icon name="pin" style="width:15px;height:15px;vertical-align:-2px"/> {{ __('Lieu') }}</dt><dd>{{ collect([$event->location_name, $event->address, $event->city, department_name($event->department), country_name($event->country)])->filter()->implode(', ') }}
                        @if($event->mapUrl())<a href="{{ $event->mapUrl() }}" target="_blank" rel="noopener" class="small">{{ __('Itinéraire') }}</a>@endif</dd>
                @endif
                @if($event->capacity)<dt>{{ __('Places') }}</dt><dd>{{ $event->going_count }} / {{ $event->capacity }}</dd>@endif
                @if($event->category)<dt>{{ __('Thème') }}</dt><dd><span class="category-chip" style="--c:{{ $event->category->color }}">{{ $event->category->name }}</span></dd>@endif
            </dl>

            @unless($event->isPast())
                <div class="row wrap mt">
                    @foreach(['going' => __('Je participe'), 'interested' => __('Intéressé'), 'not_going' => __('Je ne viens pas')] as $st => $lbl)
                        <form method="POST" action="{{ route('events.rsvp', $event) }}">@csrf<input type="hidden" name="status" value="{{ $st }}">
                            <button class="btn {{ $myRsvp === $st ? 'btn-primary' : 'btn-outline' }} btn-sm" data-auth>{{ $lbl }}</button></form>
                    @endforeach
                    @auth
                        <div class="dropdown"><button type="button" class="btn btn-ghost btn-sm" data-dropdown><x-icon name="bell"/>{{ $reminded ? __('Rappel activé') : __('Rappel') }}</button>
                            <div class="menu">@foreach([10 => __('10 minutes avant'), 60 => __('1 heure avant'), 1440 => __('1 jour avant')] as $m => $l)
                                <button type="button" data-action="{{ route('events.remind', $event) }}?minutes={{ $m }}">{{ $l }}</button>@endforeach</div></div>
                    @endauth
                    <a href="{{ route('events.ics', $event) }}" class="btn btn-ghost btn-sm"><x-icon name="download"/>{{ __('Ajouter au calendrier') }}</a>
                </div>
            @endunless

            @if($event->description)<div class="post-text mt" data-text>{!! render_text($event->description) !!}</div>@endif
            @include('partials.actions', ['model' => $event, 'type' => 'event'])
        </div>
    </article>

    @if($event->lat && $event->lng)
        <div id="event-map" style="height:260px;border-radius:var(--radius);border:1px solid var(--border);margin-bottom:.75rem" data-lat="{{ $event->lat }}" data-lng="{{ $event->lng }}" role="img" aria-label="{{ __('Carte') }}"></div>
    @endif

    <div class="card"><div class="card-head"><h3>{{ __('Participants') }} ({{ $event->going_count }})</h3><span class="small faint">{{ $event->interested_count }} {{ __('intéressés') }}</span></div>
        <div class="card-body avatar-stack" style="flex-wrap:wrap;gap:4px">@forelse($attendees as $a)<a href="{{ $a->profileUrl() }}" title="{{ $a->name }}"><img src="{{ $a->avatarUrl() }}" alt="{{ $a->name }}" class="avatar avatar-sm" style="margin:0"></a>@empty<span class="muted small">{{ __('Soyez le premier à participer.') }}</span>@endforelse</div></div>

    @include('partials.comments', ['target' => $event, 'type' => 'event', 'comments' => $comments])
@endsection
@push('scripts')
    @if($event->lat)
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script>
            (function () { const el = document.getElementById('event-map'); if (!el || !window.L) return;
                const m = L.map(el, { scrollWheelZoom: false }).setView([+el.dataset.lat, +el.dataset.lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 18 }).addTo(m);
                L.marker([+el.dataset.lat, +el.dataset.lng]).addTo(m); })();
        </script>
    @endif
    <script>document.querySelectorAll('[data-local-time]').forEach(el => { const d = new Date(el.dataset.localTime); el.textContent = '≈ ' + d.toLocaleString(document.documentElement.lang, { dateStyle: 'full', timeStyle: 'short' }) + ' (heure locale)'; });</script>
@endpush
