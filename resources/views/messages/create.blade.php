@extends('layouts.app')
@section('title', __('Nouvelle conversation'))
@section('content')
    <div class="page-head"><a href="{{ route('messages.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Nouvelle conversation') }}</h1></div>
    <form method="POST" action="{{ route('messages.store') }}" class="card" id="new-conv"><div class="card-body">
        @csrf
        <div class="field" style="position:relative">
            <label class="label" for="to">{{ __('Destinataires') }}</label>
            <div class="pills mb" data-chips></div>
            <input id="to" class="input" placeholder="{{ __('Rechercher un nom ou @username') }}" autocomplete="off">
            <div class="suggest" hidden data-to-suggest></div>
            <div class="hint">{{ __('Un destinataire : conversation individuelle. Plusieurs : conversation de groupe.') }}</div>
        </div>
        <div class="field"><label class="label" for="name">{{ __('Nom du groupe (facultatif)') }}</label><input id="name" name="name" class="input" maxlength="100"></div>
        <button class="btn btn-primary">{{ __('Commencer') }}</button>
    </div></form>
@endsection
@push('scripts')
<script>
(function () {
    const V = window.Vwajen, input = document.getElementById('to'), box = document.querySelector('[data-to-suggest]'), chips = document.querySelector('[data-chips]'), form = document.getElementById('new-conv');
    let timer;
    const add = (u) => {
        if (form.querySelector(`input[value="${u.value}"]`)) return;
        const chip = document.createElement('span'); chip.className = 'pill active'; chip.textContent = u.label + ' ✕'; chip.style.cursor = 'pointer';
        const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'usernames[]'; hidden.value = u.value;
        chip.onclick = () => { chip.remove(); hidden.remove(); }; chips.appendChild(chip); form.appendChild(hidden); input.value = ''; box.hidden = true;
    };
    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(async () => {
        const q = input.value.trim(); if (!q) return (box.hidden = true);
        const list = (await V.api(V.routes.suggest + '?q=' + encodeURIComponent(q), { method: 'GET' })).filter(i => i.type === 'user');
        box.innerHTML = ''; list.forEach(u => { const a = document.createElement('a'); a.href = '#'; a.innerHTML = '<img class="avatar avatar-sm" alt=""><span><span class="name"></span> <span class="handle small"></span></span>';
            a.querySelector('img').src = u.avatar; a.querySelector('.name').textContent = u.label; a.querySelector('.handle').textContent = u.sub;
            a.onclick = (e) => { e.preventDefault(); add(u); }; box.appendChild(a); }); box.hidden = !list.length; }, 200); });
})();
</script>
@endpush
