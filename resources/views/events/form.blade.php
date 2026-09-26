@extends('layouts.app')
@section('title', $event->exists ? __('Modifier l\'événement') : __('Créer un événement'))
@push('head')<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">@endpush
@php
    $start = $event->exists ? $event->localStart() : null;
    $end = $event->exists ? $event->localEnd() : null;
    $zones = ['America/Port-au-Prince', 'America/New_York', 'America/Montreal', 'America/Toronto', 'America/Chicago', 'America/Los_Angeles', 'America/Santo_Domingo',
        'America/Santiago', 'America/Sao_Paulo', 'America/Mexico_City', 'America/Guadeloupe', 'America/Martinique', 'America/Cayenne', 'America/Nassau', 'Europe/Paris', 'Europe/Brussels', 'Europe/London', 'Africa/Dakar', 'UTC'];
@endphp
@section('content')
    <div class="page-head"><a href="{{ $event->exists ? $event->url() : route('events.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ $event->exists ? __('Modifier l\'événement') : __('Créer un événement') }}</h1></div>
    <form method="POST" action="{{ $event->exists ? route('events.update', $event) : route('events.store') }}" enctype="multipart/form-data" class="card"><div class="card-body">
        @csrf @if($event->exists) @method('PUT') @endif
        <div class="field"><label for="title" class="label">{{ __('Titre') }}</label><input id="title" name="title" class="input" required maxlength="200" value="{{ old('title', $event->title) }}"></div>
        <div class="field"><label for="description" class="label">{{ __('Description') }}</label><textarea id="description" name="description" class="textarea">{{ old('description', $event->description) }}</textarea></div>
        <div class="field"><label for="cover" class="label">{{ __('Image') }}</label><input id="cover" type="file" name="cover" accept="image/*" class="input"></div>
        <fieldset><legend>{{ __('Date et heure') }}</legend>
            <div class="grid grid-2">
                <div class="field"><label for="start_date" class="label">{{ __('Date de début') }}</label><input id="start_date" type="date" name="start_date" class="input" required value="{{ old('start_date', $start?->format('Y-m-d')) }}"></div>
                <div class="field"><label for="start_time" class="label">{{ __('Heure de début') }}</label><input id="start_time" type="time" name="start_time" class="input" required value="{{ old('start_time', $start?->format('H:i') ?? '15:00') }}"></div>
                <div class="field"><label for="end_date" class="label">{{ __('Date de fin') }}</label><input id="end_date" type="date" name="end_date" class="input" value="{{ old('end_date', $end?->format('Y-m-d')) }}"></div>
                <div class="field"><label for="end_time" class="label">{{ __('Heure de fin') }}</label><input id="end_time" type="time" name="end_time" class="input" value="{{ old('end_time', $end?->format('H:i')) }}"></div>
            </div>
            <div class="field"><label for="timezone" class="label">{{ __('Fuseau horaire') }}</label>
                <select id="timezone" name="timezone" class="select">@foreach($zones as $z)<option value="{{ $z }}" @selected(old('timezone', $event->timezone) === $z)>{{ $z }}</option>@endforeach</select></div>
        </fieldset>
        <fieldset><legend>{{ __('Lieu') }}</legend>
            <label class="switch mb"><input type="checkbox" name="is_online" value="1" id="is_online" @checked(old('is_online', $event->is_online))><span class="track"></span>{{ __('Événement en ligne') }}</label>
            <div class="field" data-online><label for="online_url" class="label">{{ __('Lien de participation') }}</label><input id="online_url" type="url" name="online_url" class="input" value="{{ old('online_url', $event->online_url) }}" placeholder="https://"></div>
            <div data-physical>
                <div class="grid grid-2">
                    <div class="field"><label for="location_name" class="label">{{ __('Nom du lieu') }}</label><input id="location_name" name="location_name" class="input" value="{{ old('location_name', $event->location_name) }}"></div>
                    <div class="field"><label for="address" class="label">{{ __('Adresse') }}</label><input id="address" name="address" class="input" value="{{ old('address', $event->address) }}"></div>
                    <div class="field"><label for="city" class="label">{{ __('Ville') }}</label><input id="city" name="city" class="input" value="{{ old('city', $event->city) }}"></div>
                    <div class="field"><label for="department" class="label">{{ __('Département') }}</label><select id="department" name="department" class="select"><option value="">—</option>@foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}" @selected(old('department', $event->department) === $k)>{{ $d['name'] }}</option>@endforeach</select></div>
                </div>
                <label class="label">{{ __('Position sur la carte (cliquez pour placer)') }}</label>
                <div id="pick-map" style="height:260px;border-radius:var(--radius);border:1px solid var(--border)"></div>
                <div class="row mt-sm"><button type="button" class="btn btn-ghost btn-sm" data-locate><x-icon name="pin"/>{{ __('Ma position') }}</button><button type="button" class="btn btn-ghost btn-sm" data-geocode><x-icon name="search"/>{{ __('Trouver l\'adresse') }}</button></div>
                <input type="hidden" name="lat" id="lat" value="{{ old('lat', $event->lat) }}"><input type="hidden" name="lng" id="lng" value="{{ old('lng', $event->lng) }}">
            </div>
            <div class="field mt"><label for="country" class="label">{{ __('Pays') }}</label><select id="country" name="country" class="select">@foreach(all_countries() as $c => $n)<option value="{{ $c }}" @selected(old('country', $event->country) === $c)>{{ $n }}</option>@endforeach</select></div>
        </fieldset>
        <div class="grid grid-3">
            <div class="field"><label for="category_id" class="label">{{ __('Thème') }}</label><select id="category_id" name="category_id" class="select"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $event->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div class="field"><label for="community_id" class="label">{{ __('Communauté') }}</label><select id="community_id" name="community_id" class="select"><option value="">—</option>@foreach($communities as $c)<option value="{{ $c->id }}" @selected(old('community_id', $event->community_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div class="field"><label for="capacity" class="label">{{ __('Capacité') }}</label><input id="capacity" type="number" name="capacity" min="1" class="input" value="{{ old('capacity', $event->capacity) }}"></div>
        </div>
        <div class="field"><label for="visibility" class="label">{{ __('Visibilité') }}</label><select id="visibility" name="visibility" class="select"><option value="public">{{ __('Public') }}</option><option value="followers" @selected($event->visibility === 'followers')>{{ __('Abonnés') }}</option></select></div>
        <div class="row" style="justify-content:flex-end">
            @if($event->exists)
                <button type="submit" form="delete-event" class="btn btn-danger-outline">{{ __('Supprimer') }}</button>
            @endif
            <button class="btn btn-primary">{{ $event->exists ? __('Enregistrer') : __('Créer l\'événement') }}</button>
        </div>
    </div></form>
    @if($event->exists)<form id="delete-event" method="POST" action="{{ route('events.destroy', $event) }}" data-confirm="{{ __('Supprimer cet événement ? Les participants seront notifiés.') }}">@csrf @method('DELETE')</form>@endif
@endsection
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
(function () {
    const online = document.getElementById('is_online');
    const toggle = () => { document.querySelector('[data-online]').hidden = !online.checked; document.querySelector('[data-physical]').hidden = online.checked; if (!online.checked && map) setTimeout(() => map.invalidateSize(), 50); };
    const lat = document.getElementById('lat'), lng = document.getElementById('lng');
    let map = null, marker = null;
    if (window.L) {
        const start = lat.value ? [+lat.value, +lng.value] : [18.97, -72.29];
        map = L.map('pick-map').setView(start, lat.value ? 14 : 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 18 }).addTo(map);
        const place = (ll) => { lat.value = ll.lat.toFixed(7); lng.value = ll.lng.toFixed(7); marker ? marker.setLatLng(ll) : (marker = L.marker(ll).addTo(map)); };
        if (lat.value) place({ lat: +lat.value, lng: +lng.value });
        map.on('click', (e) => place(e.latlng));
        document.querySelector('[data-locate]').onclick = () => navigator.geolocation && navigator.geolocation.getCurrentPosition(p => { const ll = { lat: p.coords.latitude, lng: p.coords.longitude }; place(ll); map.setView(ll, 15); });
        document.querySelector('[data-geocode]').onclick = async () => {
            const q = [document.getElementById('address').value, document.getElementById('city').value, document.getElementById('country').selectedOptions[0].text].filter(Boolean).join(', ');
            try { const r = await (await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q))).json();
                if (r[0]) { const ll = { lat: +r[0].lat, lng: +r[0].lon }; place(ll); map.setView(ll, 15); } } catch (e) {}
        };
    }
    online.addEventListener('change', toggle); toggle();
})();
</script>
@endpush
