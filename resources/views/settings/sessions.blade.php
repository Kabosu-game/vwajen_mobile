@extends('settings.layout')
@section('title', __('Sessions'))
@section('settings')
    <div class="card"><div class="card-head"><h3>{{ __('Sessions actives') }}</h3></div>
        @foreach($sessions as $s)
            <div class="item">
                <x-icon name="{{ in_array($s->platform, ['Android', 'iOS']) ? 'phone' : 'monitor' }}"/>
                <div class="item-body"><strong>{{ $s->browser }} — {{ $s->platform }}</strong> @if($s->current)<span class="badge badge-success">{{ __('Cette session') }}</span>@endif
                    <div class="small faint">{{ $s->ip_address }} · {{ __('dernière activité') }} {{ $s->last->diffForHumans() }}</div></div>
                @unless($s->current)
                    <form method="POST" action="{{ route('settings.sessions.destroy', $s->id) }}">@csrf @method('DELETE')<button class="btn btn-outline btn-sm">{{ __('Déconnecter') }}</button></form>
                @endunless
            </div>
        @endforeach
    </div>
    @if($sessions->count() > 1)
        <form method="POST" action="{{ route('settings.sessions.destroy-others') }}" class="card"><div class="card-head"><h3>{{ __('Déconnexion à distance') }}</h3></div><div class="card-body">
            @csrf @method('DELETE')
            <p class="small muted">{{ __('Déconnecte toutes les autres sessions (utile en cas de perte d\'un téléphone).') }}</p>
            @if(auth()->user()->password)<div class="field"><label class="label" for="pw">{{ __('Mot de passe') }}</label><input id="pw" type="password" name="password" class="input" required autocomplete="current-password"></div>@endif
            <button class="btn btn-danger">{{ __('Déconnecter toutes les autres sessions') }}</button>
        </div></form>
    @endif
@endsection
