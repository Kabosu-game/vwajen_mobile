@extends('layouts.app')
@section('title', __('Poser une question'))
@section('content')
    <div class="page-head"><a href="{{ route('questions.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Poser une question') }}</h1></div>
    <form method="POST" action="{{ route('questions.store') }}" class="card"><div class="card-body">
        @csrf
        <div class="field">
            <label for="candidate_id" class="label">{{ __('À qui s\'adresse votre question ?') }}</label>
            <select id="candidate_id" name="candidate_id" class="select">
                <option value="">{{ __('Question publique (tous les candidats peuvent répondre)') }}</option>
                @foreach($candidates as $c)<option value="{{ $c->id }}" @selected(old('candidate_id', $candidate?->id) == $c->id)>{{ $c->name }} ({{ '@'.$c->username }})</option>@endforeach
            </select>
        </div>
        <div class="field">
            <label for="title" class="label">{{ __('Votre question') }}</label>
            <input id="title" name="title" class="input" required minlength="10" maxlength="250" value="{{ old('title') }}" placeholder="{{ __('ex. Quel est votre plan pour l\'accès à l\'eau potable ?') }}">
            <div class="hint">{{ __('Soyez précis, respectueux et posez une seule question.') }}</div>
        </div>
        <div class="field">
            <label for="body" class="label">{{ __('Contexte (facultatif)') }}</label>
            <textarea id="body" name="body" class="textarea" maxlength="3000">{{ old('body') }}</textarea>
        </div>
        <div class="field">
            <label for="category_id" class="label">{{ __('Thème') }}</label>
            <select id="category_id" name="category_id" class="select"><option value="">—</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>{{ $c->name }}</option>@endforeach</select>
        </div>
        <div class="alert alert-info"><x-icon name="bell"/><div class="small">{{ __('Vous serez notifié dès qu\'un candidat répond. Les autres citoyens peuvent soutenir votre question pour lui donner plus de visibilité.') }}</div></div>
        <button class="btn btn-brand">{{ __('Publier la question') }}</button>
    </div></form>
@endsection
