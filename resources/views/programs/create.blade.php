@extends('layouts.app')
@section('title', __('Publier un programme'))
@section('content')
    <div class="page-head"><a href="{{ route('candidate.dashboard', 'program') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Nouveau programme') }}</h1></div>
    <form method="POST" action="{{ route('programs.store') }}" class="card"><div class="card-body">
        @csrf
        <div class="field"><label for="title" class="label">{{ __('Titre du programme') }}</label><input id="title" name="title" class="input" required maxlength="200" value="{{ old('title') }}"></div>
        <div class="field"><label for="summary" class="label">{{ __('Résumé / vision') }}</label><textarea id="summary" name="summary" class="textarea" rows="6" maxlength="20000">{{ old('summary') }}</textarea></div>
        <p class="hint">{{ __('Après création, vous pourrez ajouter vos propositions par thème (santé, éducation, économie…), vos sources et vos documents, puis publier.') }}</p>
        <button class="btn btn-primary">{{ __('Créer le brouillon') }}</button>
    </div></form>
@endsection
