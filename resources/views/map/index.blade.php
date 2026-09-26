@extends('layouts.app')
@section('title', 'Vwajèn Map')
@section('layout', 'wide')
@push('head')<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">@endpush
@section('content')
    <div class="page-head"><h1>Vwajèn Map</h1></div>
    <form class="map-filters" id="map-filters" onsubmit="return false">
        <label class="sr-only" for="m-dep">{{ __('Département') }}</label>
        <select id="m-dep" name="department" class="select input-sm" style="width:auto"><option value="">{{ __('Tout Haïti') }}</option>@foreach($departments as $k => $d)<option value="{{ $k }}" data-lat="{{ $d['lat'] }}" data-lng="{{ $d['lng'] }}">{{ $d['name'] }}</option>@endforeach</select>
        <label class="sr-only" for="m-city">{{ __('Ville') }}</label>
        <input id="m-city" name="city" class="input input-sm" style="width:160px" placeholder="{{ __('Rechercher une ville') }}">
        <label class="sr-only" for="m-period">{{ __('Période') }}</label>
        <select id="m-period" name="period" class="select input-sm" style="width:auto">
            <option value="upcoming">{{ __('À venir') }}</option><option value="today">{{ __('Aujourd\'hui') }}</option><option value="week">{{ __('7 prochains jours') }}</option><option value="past">{{ __('Passés') }}</option></select>
        @foreach(['events' => __('Événements'), 'lives' => __('Lives'), 'candidates' => __('Candidats'), 'communities' => __('Communautés')] as $k => $l)
            <label class="check mb-0 small"><input type="checkbox" name="types[]" value="{{ $k }}" @checked(in_array($k, ['events', 'lives']))>{{ $l }}</label>
        @endforeach
        <label class="check mb-0 small"><input type="checkbox" id="m-zone" checked>{{ __('Rechercher dans la zone visible') }}</label>
        <button type="button" class="btn btn-ghost btn-sm" id="m-locate"><x-icon name="pin"/>{{ __('Autour de moi') }}</button>
        <span class="small faint" id="m-count" aria-live="polite"></span>
    </form>
    <div class="map-wrap"><div id="map" role="application" aria-label="{{ __('Carte interactive des activités publiques') }}"></div></div>
    <div class="grid grid-4 mt" id="m-deps"></div>
@endsection
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
(function () {
    if (!window.L) return;
    const deps = @json($departments);
    const colors = { event: '#1d4ed8', live: '#e11d48', candidates: '#f5b21b', community: '#0e9f6e' };
    const map = L.map('map').setView([18.97, -72.6], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 18 }).addTo(map);
    const layer = L.layerGroup().addTo(map);
    const form = document.getElementById('map-filters');
    let timer;
    function marker(f) {
        const icon = L.divIcon({ className: '', html: `<span style="display:block;width:18px;height:18px;border-radius:50%;background:${colors[f.type] || '#1d4ed8'};border:3px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.5)${f.live ? ';animation:pulse 1.6s infinite' : ''}"></span>`, iconSize: [18, 18] });
        const m = L.marker([f.lat, f.lng], { icon, title: f.title });
        const div = document.createElement('div'); const a = document.createElement('a'); a.href = f.url; a.textContent = f.title; div.appendChild(a);
        const s = document.createElement('div'); s.className = 'small'; s.textContent = f.subtitle || ''; div.appendChild(s);
        m.bindPopup(div); return m;
    }
    async function load() {
        const fd = new FormData(form); const params = new URLSearchParams();
        fd.forEach((v, k) => v && params.append(k, v));
        if (document.getElementById('m-zone').checked) { const b = map.getBounds(); params.set('bounds', [b.getSouth(), b.getWest(), b.getNorth(), b.getEast()].map(n => n.toFixed(4)).join(',')); }
        const r = await (await fetch('{{ route('map.data') }}?' + params, { headers: { Accept: 'application/json' } })).json();
        layer.clearLayers(); r.features.forEach(f => layer.addLayer(marker(f)));
        document.getElementById('m-count').textContent = r.features.length + ' {{ __('résultats') }}';
        const box = document.getElementById('m-deps'); box.innerHTML = '';
        Object.entries(deps).forEach(([k, d]) => { const n = r.departments[k] || 0; const el = document.createElement('button'); el.type = 'button'; el.className = 'stat'; el.style.textAlign = 'left'; el.style.cursor = 'pointer';
            el.innerHTML = `<div class="v">${n}</div><div class="l"></div>`; el.querySelector('.l').textContent = d.name + ' — {{ __('événements à venir') }}';
            el.onclick = () => { document.getElementById('m-dep').value = k; map.setView([d.lat, d.lng], 10); }; box.appendChild(el); });
    }
    const reload = () => { clearTimeout(timer); timer = setTimeout(load, 350); };
    form.addEventListener('change', (e) => {
        if (e.target.id === 'm-dep') { const o = e.target.selectedOptions[0]; o.dataset.lat ? map.setView([+o.dataset.lat, +o.dataset.lng], 10) : map.setView([18.97, -72.6], 8); }
        reload();
    });
    document.getElementById('m-city').addEventListener('input', reload);
    map.on('moveend', () => { if (document.getElementById('m-zone').checked) reload(); });
    document.getElementById('m-locate').onclick = () => navigator.geolocation && navigator.geolocation.getCurrentPosition(p => map.setView([p.coords.latitude, p.coords.longitude], 12));
    load();
})();
</script>
@endpush
