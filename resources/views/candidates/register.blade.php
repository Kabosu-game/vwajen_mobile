@extends('layouts.app')
@section('title', __('Inscription comme candidat'))
@section('content')
    <div class="page-head"><a href="{{ route('candidates.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Inscription comme candidat') }}</h1></div>
    <div class="alert alert-info"><x-icon name="shield-check"/><div class="small">{{ __('Votre profil sera visible avec la mention « vérification en cours » jusqu\'à la validation de vos documents par notre équipe. Le badge « Candidat vérifié » est attribué après examen.') }}</div></div>
    <form method="POST" action="{{ route('candidates.register.store') }}" enctype="multipart/form-data" class="card"><div class="card-body">
        @csrf
        <fieldset><legend>{{ __('Identité') }}</legend>
            <div class="field"><label class="label" for="full_name">{{ __('Nom complet (tel qu\'il apparaît sur le bulletin)') }}</label>
                <input id="full_name" name="full_name" class="input" required maxlength="150" value="{{ old('full_name', $profile->full_name ?? $user->name) }}"></div>
            <div class="field"><label class="label" for="photo">{{ __('Photo officielle') }}</label><input id="photo" type="file" name="photo" accept="image/*" class="input"></div>
        </fieldset>
        <fieldset><legend>{{ __('Candidature') }}</legend>
            <div class="grid grid-2">
                <div class="field"><label class="label" for="position_sought">{{ __('Poste recherché') }}</label>
                    <select id="position_sought" name="position_sought" class="select" required>
                        @foreach(config('vwajen.positions') as $k => $p)<option value="{{ $k }}" @selected(old('position_sought', $profile->position_sought ?? '') === $k)>{{ __($p) }}</option>@endforeach
                    </select></div>
                <div class="field"><label class="label" for="election_year">{{ __('Année de l\'élection') }}</label>
                    <input id="election_year" type="number" name="election_year" class="input" min="2020" max="2100" value="{{ old('election_year', $profile->election_year ?? date('Y')) }}"></div>
                <div class="field"><label class="label" for="department">{{ __('Département') }}</label>
                    <select id="department" name="department" class="select" required>
                        @foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}" @selected(old('department', $profile->department ?? $user->department) === $k)>{{ $d['name'] }}</option>@endforeach
                    </select></div>
                <div class="field"><label class="label" for="constituency">{{ __('Circonscription') }}</label>
                    <input id="constituency" name="constituency" class="input" maxlength="150" value="{{ old('constituency', $profile->constituency ?? '') }}"></div>
                <div class="field"><label class="label" for="city">{{ __('Ville') }}</label><input id="city" name="city" class="input" value="{{ old('city', $profile->city ?? $user->city) }}"></div>
                <div class="field"><label class="label" for="party">{{ __('Affiliation politique déclarée') }}</label>
                    <input id="party" name="party" class="input" maxlength="150" value="{{ old('party', $profile->party ?? '') }}" placeholder="{{ __('Parti, plateforme ou « Indépendant »') }}"></div>
            </div>
            <div class="field"><label class="label" for="website">{{ __('Site web') }}</label><input id="website" type="url" name="website" class="input" value="{{ old('website', $profile->website ?? '') }}"></div>
        </fieldset>
        <fieldset><legend>{{ __('Présentation') }}</legend>
            <div class="field"><label class="label" for="biography">{{ __('Biographie') }}</label>
                <textarea id="biography" name="biography" class="textarea" required maxlength="5000">{{ old('biography', $profile->biography ?? '') }}</textarea></div>
            <div class="field"><label class="label" for="career">{{ __('Parcours (formation, expériences, engagements)') }}</label>
                <textarea id="career" name="career" class="textarea" rows="6" maxlength="20000">{{ old('career', $profile->career ?? '') }}</textarea></div>
        </fieldset>
        <fieldset><legend>{{ __('Documents justificatifs') }}</legend>
            <p class="small muted">{{ __('Pièce d\'identité, attestation d\'inscription au CEP ou lettre du parti. Formats PDF ou image, 10 Mo max. Documents stockés de façon privée et consultés uniquement par l\'équipe de vérification.') }}</p>
            @for($i = 0; $i < 3; $i++)
                <div class="row mb" style="margin-bottom:.5rem">
                    <input type="text" name="document_labels[]" class="input input-sm" placeholder="{{ __('Type de document') }}" style="max-width:220px" aria-label="{{ __('Type de document') }}">
                    <input type="file" name="documents[]" accept=".pdf,image/*" class="input input-sm grow" @if($i === 0) required @endif aria-label="{{ __('Fichier') }}">
                </div>
            @endfor
            <div class="field"><label class="label" for="message">{{ __('Message à l\'équipe de vérification') }}</label><textarea id="message" name="message" class="textarea" rows="2" maxlength="2000"></textarea></div>
        </fieldset>
        <button class="btn btn-brand btn-lg">{{ __('Envoyer ma candidature') }}</button>
    </div></form>
@endsection
