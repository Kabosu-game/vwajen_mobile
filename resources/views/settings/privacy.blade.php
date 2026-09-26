@extends('settings.layout')
@section('title', __('Confidentialité'))
@php $opts = ['everyone' => __('Tout le monde'), 'following' => __('Personnes que je suis'), 'nobody' => __('Personne')]; @endphp
@section('settings')
    <form method="POST" action="{{ route('settings.privacy.update') }}" class="card"><div class="card-body">
        @csrf @method('PUT')
        <h3>{{ __('Profil') }}</h3>
        @if($user->account_type === 'personal')
            <label class="switch mb"><input type="checkbox" name="is_private" value="1" @checked($user->is_private)><span class="track"></span><span><strong>{{ __('Profil privé') }}</strong><br><span class="small muted">{{ __('Seuls vos abonnés approuvés voient vos publications.') }}</span></span></label>
        @else
            <div class="alert alert-info"><x-icon name="info"/><div class="small">{{ __('Les comptes candidats, organisations et élus sont publics par nature.') }}</div></div>
        @endif
        <label class="switch mb"><input type="checkbox" name="searchable" value="1" @checked($user->searchable)><span class="track"></span>{{ __('Apparaître dans la recherche et les suggestions') }}</label>
        <hr>
        <h3>{{ __('Contrôle des interactions') }}</h3>
        <div class="grid grid-3">
            <div class="field"><label class="label" for="am">{{ __('Qui peut m\'envoyer des messages') }}</label><select id="am" name="allow_messages" class="select">@foreach($opts as $k => $l)<option value="{{ $k }}" @selected($user->allow_messages === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="field"><label class="label" for="ac">{{ __('Qui peut commenter') }}</label><select id="ac" name="allow_comments" class="select">@foreach($opts as $k => $l)<option value="{{ $k }}" @selected($user->allow_comments === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="field"><label class="label" for="amn">{{ __('Qui peut me mentionner') }}</label><select id="amn" name="allow_mentions" class="select">@foreach($opts as $k => $l)<option value="{{ $k }}" @selected($user->allow_mentions === $k)>{{ $l }}</option>@endforeach</select></div>
        </div>
        <hr>
        <h3>{{ __('Gestion des données sensibles') }}</h3>
        <p class="small muted">{{ __('Choisissez les informations visibles sur votre profil public.') }} <a href="{{ route('pages.show', 'sensitive-data') }}">{{ __('En savoir plus') }}</a></p>
        <label class="switch mb"><input type="checkbox" name="show_location" value="1" @checked($user->show_location)><span class="track"></span>{{ __('Afficher ma localisation') }}</label><br>
        <label class="switch mb"><input type="checkbox" name="show_email" value="1" @checked($user->show_email)><span class="track"></span>{{ __('Afficher mon e-mail') }}</label><br>
        <label class="switch mb"><input type="checkbox" name="show_phone" value="1" @checked($user->show_phone)><span class="track"></span>{{ __('Afficher mon téléphone') }}</label><br>
        <label class="switch mb"><input type="checkbox" name="show_political" value="1" @checked($user->show_political)><span class="track"></span>{{ __('Afficher mon affiliation politique (si déclarée)') }}</label>
        <div class="mt"><button class="btn btn-primary">{{ __('Enregistrer') }}</button></div>
    </div></form>
@endsection
