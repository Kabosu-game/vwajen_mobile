@extends('layouts.app')
@section('title', __('Modifier la Vwa'))
@section('content')
    <div class="page-head"><a href="{{ $post->url() }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Modifier la Vwa') }}</h1></div>
    <form method="POST" action="{{ route('posts.update', $post) }}" class="card"><div class="card-body">
        @csrf @method('PUT')
        <label for="body" class="label">{{ __('Texte') }}</label>
        <textarea id="body" name="body" class="textarea" maxlength="{{ config('vwajen.limits.post_length') }}" data-autosize data-mention>{{ old('body', $post->body) }}</textarea>
        @unless($post->community_id)
            <div class="field mt">
                <label for="visibility" class="label">{{ __('Visibilité') }}</label>
                <select id="visibility" name="visibility" class="select">
                    <option value="public" @selected($post->visibility === 'public')>{{ __('Public') }}</option>
                    <option value="followers" @selected($post->visibility === 'followers')>{{ __('Abonnés uniquement') }}</option>
                </select>
            </div>
        @endunless
        <p class="hint">{{ __('Les médias et sondages ne peuvent pas être modifiés. Une mention « modifié » sera affichée et l\'historique conservé.') }}</p>
        <div class="row" style="justify-content:flex-end"><a href="{{ $post->url() }}" class="btn btn-ghost">{{ __('Annuler') }}</a><button class="btn btn-primary">{{ __('Enregistrer') }}</button></div>
    </div></form>
@endsection
