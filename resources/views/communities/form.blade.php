@extends('layouts.app')
@section('title', $community->exists ? __('Paramètres de la communauté') : __('Créer une communauté'))
@section('content')
    <div class="page-head"><a href="{{ $community->exists ? $community->url() : route('communities.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ $community->exists ? __('Paramètres') : __('Créer une communauté') }}</h1></div>
    <form method="POST" action="{{ $community->exists ? route('communities.update', $community) : route('communities.store') }}" enctype="multipart/form-data" class="card"><div class="card-body">
        @csrf @if($community->exists) @method('PUT') @endif
        <div class="field"><label for="name" class="label">{{ __('Nom') }}</label><input id="name" name="name" class="input" required maxlength="100" value="{{ old('name', $community->name) }}"></div>
        <div class="field"><label for="description" class="label">{{ __('Description') }}</label><textarea id="description" name="description" class="textarea" maxlength="3000">{{ old('description', $community->description) }}</textarea></div>
        <div class="field"><label for="rules" class="label">{{ __('Règles de communauté') }}</label><textarea id="rules" name="rules" class="textarea" maxlength="5000" placeholder="1. …">{{ old('rules', $community->rules) }}</textarea></div>
        <div class="grid grid-2">
            <div class="field"><label for="avatar" class="label">{{ __('Logo') }}</label><input id="avatar" type="file" name="avatar" accept="image/*" class="input"></div>
            <div class="field"><label for="cover" class="label">{{ __('Couverture') }}</label><input id="cover" type="file" name="cover" accept="image/*" class="input"></div>
            <div class="field"><label for="visibility" class="label">{{ __('Type') }}</label><select id="visibility" name="visibility" class="select">
                <option value="public" @selected($community->visibility === 'public')>{{ __('Publique : tout le monde voit et peut rejoindre') }}</option>
                <option value="private" @selected($community->visibility === 'private')>{{ __('Privée : adhésion sur approbation') }}</option></select></div>
            <div class="field"><label for="category_id" class="label">{{ __('Thème') }}</label><select id="category_id" name="category_id" class="select"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $community->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div class="field"><label for="country" class="label">{{ __('Pays') }}</label><select id="country" name="country" class="select"><option value="">—</option>@foreach(all_countries() as $c => $n)<option value="{{ $c }}" @selected(old('country', $community->country) === $c)>{{ $n }}</option>@endforeach</select></div>
            <div class="field"><label for="city" class="label">{{ __('Ville') }}</label><input id="city" name="city" class="input" value="{{ old('city', $community->city) }}"></div>
        </div>
        <label class="switch"><input type="checkbox" name="is_diaspora" value="1" @checked(old('is_diaspora', $community->is_diaspora))><span class="track"></span>{{ __('Communauté de la diaspora (Vwajèn Mond)') }}</label>
        <div class="row mt" style="justify-content:flex-end">
            @if($community->exists && auth()->id() === $community->owner_id)<button type="submit" form="del-com" class="btn btn-danger-outline">{{ __('Supprimer') }}</button>@endif
            <button class="btn btn-primary">{{ $community->exists ? __('Enregistrer') : __('Créer') }}</button>
        </div>
    </div></form>
    @if($community->exists)<form id="del-com" method="POST" action="{{ route('communities.destroy', $community) }}" data-confirm="{{ __('Supprimer définitivement cette communauté ?') }}">@csrf @method('DELETE')</form>@endif
@endsection
