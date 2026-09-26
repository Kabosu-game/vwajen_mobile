@extends('layouts.app')
@section('title', __('Créer un podcast'))
@section('content')
    <div class="page-head"><a href="{{ route('podcasts.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Créer un podcast') }}</h1></div>
    <form method="POST" action="{{ route('podcasts.store') }}" enctype="multipart/form-data" class="card"><div class="card-body">
        @csrf
        <div class="field"><label for="title" class="label">{{ __('Titre') }}</label><input id="title" name="title" class="input" required maxlength="200"></div>
        <div class="field"><label for="description" class="label">{{ __('Description') }}</label><textarea id="description" name="description" class="textarea"></textarea></div>
        <div class="grid grid-3">
            <div class="field"><label for="cover" class="label">{{ __('Pochette') }}</label><input id="cover" type="file" name="cover" accept="image/*" class="input"></div>
            <div class="field"><label for="category_id" class="label">{{ __('Thème') }}</label><select id="category_id" name="category_id" class="select"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
            <div class="field"><label for="lang" class="label">{{ __('Langue') }}</label><select id="lang" name="lang" class="select">@foreach(config('vwajen.locales') as $c => $l)<option value="{{ $c }}">{{ $l['name'] }}</option>@endforeach</select></div>
        </div>
        <button class="btn btn-primary">{{ __('Créer') }}</button>
    </div></form>
@endsection
