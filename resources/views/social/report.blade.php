@extends('layouts.app')
@section('title', __('Signaler'))
@section('content')
    <div class="page-head"><a href="{{ url()->previous() }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Signaler : :type', ['type' => \App\Support\Morph::label($type)]) }}</h1></div>
    <form method="POST" action="{{ route('reports.store', [$type, $target->id]) }}" class="card"><div class="card-body">
        @csrf
        <input type="hidden" name="return" value="{{ url()->previous() }}">
        <div class="quote mb">
            @if($type === 'user')
                <div class="row"><img src="{{ $target->avatarUrl() }}" class="avatar avatar-sm" alt=""><strong>{{ $target->name }}</strong> <span class="handle">{{ '@'.$target->username }}</span></div>
            @else
                <div class="small faint">{{ optional($target->user ?? $target->owner ?? null)->name }}</div>
                <div class="clamp-3">{{ $target->title ?? $target->name ?? $target->body ?? $target->description ?? '' }}</div>
            @endif
        </div>
        <fieldset>
            <legend>{{ __('Pourquoi signalez-vous ce contenu ?') }}</legend>
            @foreach($reasons as $r)
                <label class="check"><input type="radio" name="reason" value="{{ $r }}" required @checked(old('reason') === $r)> {{ \App\Models\Report::reasonLabel($r) }}</label>
            @endforeach
        </fieldset>
        <div class="field">
            <label for="details" class="label">{{ __('Précisions (facultatif)') }}</label>
            <textarea id="details" name="details" class="textarea" maxlength="1000" placeholder="{{ __('Aidez-nous à comprendre le problème…') }}">{{ old('details') }}</textarea>
        </div>
        @if($type !== 'user' || $target->id !== auth()->id())
            <label class="check"><input type="checkbox" name="block" value="1"> {{ __('Bloquer également l\'auteur') }}</label>
        @endif
        <div class="alert alert-info"><x-icon name="shield"/><div class="small">{{ __('Les signalements sont confidentiels. Notre équipe examine chaque signalement selon les règles de la communauté. Les abus de signalement peuvent être sanctionnés.') }}</div></div>
        <div class="row" style="justify-content:flex-end"><a href="{{ url()->previous() }}" class="btn btn-ghost">{{ __('Annuler') }}</a><button class="btn btn-danger">{{ __('Envoyer le signalement') }}</button></div>
    </div></form>
@endsection
