@extends('settings.layout')
@section('title', __('Personnalisation'))
@php $mine = $user->interests ?? []; @endphp
@section('settings')
    <form method="POST" action="{{ route('settings.interests.update') }}" class="card"><div class="card-body">
        @csrf @method('PUT')
        <h3>{{ __('Centres d\'intérêt') }}</h3>
        <p class="small muted">{{ __('Ils personnalisent votre fil « Pour vous », vos suggestions et les contenus découverts.') }}</p>
        <div class="pills mb">
            @foreach($categories as $c)
                <label><input type="checkbox" name="interests[]" value="{{ $c->slug }}" class="pill-check" @checked(in_array($c->slug, $mine))><span class="pill">{{ $c->name }}</span></label>
            @endforeach
        </div>
        <h3>{{ __('Préférences de contenu') }}</h3>
        <div class="field"><label class="label" for="feed_mode">{{ __('Fil par défaut') }}</label>
            <select id="feed_mode" name="feed_mode" class="select">@foreach(['personalized' => __('Pour vous (personnalisé)'), 'chronological' => __('Abonnements (chronologique)'), 'popular' => __('Populaires'), 'recent' => __('Récentes')] as $k => $l)<option value="{{ $k }}" @selected($user->feed_mode === $k)>{{ $l }}</option>@endforeach</select></div>
        <label class="switch mb"><input type="checkbox" name="hide_reposts" value="1" @checked($user->content_prefs['hide_reposts'] ?? false)><span class="track"></span>{{ __('Masquer les reposts dans mon fil') }}</label>
        <h3 class="mt">{{ __('Hashtags suivis') }}</h3>
        <div class="pills mb">
            @forelse($hashtags as $h)<span class="pill active">#{{ $h->name }} <button type="button" data-action="{{ route('hashtags.follow', $h->name) }}" data-remove-closest=".pill" style="background:none;border:0;color:inherit;cursor:pointer" aria-label="{{ __('Ne plus suivre') }}">✕</button></span>
            @empty<span class="small muted">{{ __('Aucun hashtag suivi.') }}</span>@endforelse
        </div>
        <div class="field"><label class="label" for="follow_hashtags">{{ __('Suivre des hashtags') }}</label><input id="follow_hashtags" name="follow_hashtags" class="input" placeholder="#Edikasyon #Sante"></div>
        @if($suggested->isNotEmpty())<div class="small muted">{{ __('Suggestions :') }} @foreach($suggested as $s)<a href="{{ $s->url() }}">#{{ $s->name }}</a> @endforeach</div>@endif
        <button class="btn btn-primary mt">{{ __('Enregistrer') }}</button>
    </div></form>
    <div class="card"><div class="card-head"><h3>{{ __('Comptes suivis') }} ({{ $user->following_count }})</h3><a href="{{ route('profile.following', $user) }}" class="small">{{ __('Tout voir') }}</a></div>
        @foreach($following->take(10) as $u) @include('partials.user-card', ['u' => $u, 'compact' => true]) @endforeach
        @if($following->isEmpty())<div class="p small muted">{{ __('Vous ne suivez personne.') }} <a href="{{ route('discover.index', ['section' => 'users']) }}">{{ __('Découvrir') }}</a></div>@endif
    </div>
@endsection
