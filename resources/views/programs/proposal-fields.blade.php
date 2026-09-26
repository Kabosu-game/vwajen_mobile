@php $uid = $p?->id ?? 'new'; @endphp
<div class="grid grid-2">
    <div class="field"><label class="label" for="cat-{{ $uid }}">{{ __('Thème') }}</label>
        <select id="cat-{{ $uid }}" name="category_id" class="select" required>
            @foreach(\App\Models\Category::allActive() as $c)<option value="{{ $c->id }}" @selected($p?->category_id === $c->id)>{{ $c->name }}</option>@endforeach
        </select></div>
    <div class="field"><label class="label" for="title-{{ $uid }}">{{ __('Titre de la proposition') }}</label><input id="title-{{ $uid }}" name="title" class="input" required maxlength="200" value="{{ $p?->title }}"></div>
</div>
<div class="field"><label class="label" for="desc-{{ $uid }}">{{ __('Description détaillée') }}</label><textarea id="desc-{{ $uid }}" name="description" class="textarea" required rows="4">{{ $p?->description }}</textarea></div>
<div class="grid grid-2">
    <div class="field"><label class="label" for="tl-{{ $uid }}">{{ __('Calendrier') }}</label><input id="tl-{{ $uid }}" name="timeline" class="input" maxlength="150" value="{{ $p?->timeline }}" placeholder="{{ __('ex. 2 ans') }}"></div>
    <div class="field"><label class="label" for="bg-{{ $uid }}">{{ __('Budget estimé') }}</label><input id="bg-{{ $uid }}" name="budget" class="input" maxlength="150" value="{{ $p?->budget }}"></div>
</div>
<details class="mb"><summary class="small" style="cursor:pointer;color:var(--primary)">+ {{ __('Ajouter une source') }}</summary>
    <div class="grid grid-3 mt-sm">
        <input name="source_name" class="input input-sm" placeholder="{{ __('Nom de la source') }}" aria-label="{{ __('Nom de la source') }}">
        <input name="source_url" type="url" class="input input-sm" placeholder="https://" aria-label="{{ __('Lien source') }}">
        <input name="source_date" type="date" class="input input-sm" aria-label="{{ __('Date de publication') }}">
    </div>
</details>
