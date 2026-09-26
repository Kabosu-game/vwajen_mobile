@extends('settings.layout')
@section('title', __('Modifier le profil'))
@section('settings')
    <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" class="card" style="overflow:hidden">
        @csrf @method('PUT')
        <div class="cover" @if($user->coverUrl()) style="background-image:url('{{ $user->coverUrl() }}')" @endif></div>
        <div class="card-body">
            <div class="row" style="margin-top:-60px"><img src="{{ $user->avatarUrl() }}" alt="" class="avatar avatar-xl"></div>
            <div class="grid grid-2 mt">
                <div class="field"><label class="label" for="avatar">{{ __('Photo de profil') }}</label><input id="avatar" type="file" name="avatar" accept="image/*" class="input">
                    @if($user->avatar)<label class="check small mt-sm"><input type="checkbox" name="remove_avatar" value="1">{{ __('Supprimer la photo') }}</label>@endif</div>
                <div class="field"><label class="label" for="cover">{{ __('Photo de couverture') }}</label><input id="cover" type="file" name="cover" accept="image/*" class="input">
                    @if($user->cover)<label class="check small mt-sm"><input type="checkbox" name="remove_cover" value="1">{{ __('Supprimer la couverture') }}</label>@endif</div>
            </div>
            <div class="hint mb">{{ __('Les images sont automatiquement compressées pour économiser les données.') }}</div>
            <div class="field"><label class="label" for="name">{{ __('Nom') }}</label><input id="name" name="name" class="input" required maxlength="80" value="{{ old('name', $user->name) }}"></div>
            <div class="field"><label class="label" for="bio">{{ __('Biographie') }}</label><textarea id="bio" name="bio" class="textarea" maxlength="500" rows="3" data-mention>{{ old('bio', $user->bio) }}</textarea></div>
            <div class="grid grid-2">
                <div class="field"><label class="label" for="location">{{ __('Localisation') }}</label><input id="location" name="location" class="input" maxlength="120" value="{{ old('location', $user->location) }}"></div>
                <div class="field"><label class="label" for="website">{{ __('Site web') }}</label><input id="website" type="url" name="website" class="input" value="{{ old('website', $user->website) }}"></div>
                <div class="field"><label class="label" for="city">{{ __('Ville') }}</label><input id="city" name="city" class="input" value="{{ old('city', $user->city) }}"></div>
                <div class="field"><label class="label" for="department">{{ __('Département (Haïti)') }}</label><select id="department" name="department" class="select"><option value="">—</option>@foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}" @selected(old('department', $user->department) === $k)>{{ $d['name'] }}</option>@endforeach</select></div>
                <div class="field"><label class="label" for="country">{{ __('Pays de résidence') }}</label><select id="country" name="country" class="select">@foreach(all_countries() as $c => $n)<option value="{{ $c }}" @selected(old('country', $user->country) === $c)>{{ $n }}</option>@endforeach</select></div>
            </div>
            <button class="btn btn-primary">{{ __('Enregistrer') }}</button>
        </div>
    </form>
@endsection
