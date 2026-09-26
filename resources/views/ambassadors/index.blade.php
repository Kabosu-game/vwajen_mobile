@extends('layouts.app')
@section('title', __('Programme ambassadeurs'))
@section('content')
    <div class="hero"><h1>{{ __('Programme ambassadeurs') }}</h1><p>{{ __('Aidez vos voisins à s\'informer et à faire entendre leur voix. Les ambassadeurs animent Vwajèn dans leur ville, en Haïti et dans la diaspora.') }}</p></div>
    @auth
        @if($me)
            <div class="card"><div class="card-body">
                <strong>{{ __('Votre candidature') }}</strong> : <span class="badge {{ $me->status === 'approved' ? 'badge-success' : ($me->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ ['approved' => __('Approuvée'), 'rejected' => __('Refusée'), 'pending' => __('En attente')][$me->status] }}</span>
                @if($me->status === 'approved')
                    <p class="mt">{{ __('Votre lien de parrainage :') }} <code>{{ route('register', ['ref' => $me->referral_code]) }}</code> <button class="btn btn-ghost btn-sm" data-copy="{{ route('register', ['ref' => $me->referral_code]) }}">{{ __('Copier') }}</button></p>
                    <p class="small muted">{{ trans_choice(':n inscription parrainée|:n inscriptions parrainées', $me->referrals_count, ['n' => $me->referrals_count]) }}</p>
                @endif
            </div></div>
        @else
            <form method="POST" action="{{ route('ambassadors.store') }}" class="card"><div class="card-head"><h3>{{ __('Devenir ambassadeur') }}</h3></div><div class="card-body">
                @csrf
                <div class="grid grid-3">
                    <div class="field"><label class="label" for="city">{{ __('Ville') }}</label><input id="city" name="city" class="input" required value="{{ auth()->user()->city }}"></div>
                    <div class="field"><label class="label" for="department">{{ __('Département') }}</label><select id="department" name="department" class="select"><option value="">—</option>@foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}">{{ $d['name'] }}</option>@endforeach</select></div>
                    <div class="field"><label class="label" for="country">{{ __('Pays') }}</label><select id="country" name="country" class="select">@foreach(all_countries() as $c => $n)<option value="{{ $c }}" @selected(auth()->user()->country === $c)>{{ $n }}</option>@endforeach</select></div>
                </div>
                <div class="field"><label class="label" for="motivation">{{ __('Votre motivation') }}</label><textarea id="motivation" name="motivation" class="textarea" required minlength="50"></textarea></div>
                <button class="btn btn-primary">{{ __('Envoyer ma candidature') }}</button>
            </div></form>
        @endif
    @else
        <a href="{{ route('register') }}" class="btn btn-brand">{{ __('Créer un compte pour postuler') }}</a>
    @endauth
    @if($ambassadors->isNotEmpty())
        <div class="section-title"><h2>{{ __('Nos ambassadeurs') }}</h2></div>
        <div class="card">@foreach($ambassadors as $a)<div class="user-card"><img src="{{ $a->user->avatarUrl() }}" class="avatar" alt=""><div class="info"><a href="{{ $a->user->profileUrl() }}" class="name">{{ $a->user->name }}</a><div class="small faint">{{ $a->city }}, {{ country_name($a->country) }}</div></div></div>@endforeach</div>
    @endif
@endsection
