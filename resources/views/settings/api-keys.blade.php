@extends('settings.layout')
@section('title', __('Clés API'))
@section('settings')
    @if(session('new_api_key'))
        <div class="alert alert-warning"><x-icon name="key"/><div>{{ __('Copiez votre clé maintenant, elle ne sera plus affichée :') }}<br><code style="user-select:all">{{ session('new_api_key') }}</code>
            <button type="button" class="btn btn-ghost btn-sm" data-copy="{{ session('new_api_key') }}">{{ __('Copier') }}</button></div></div>
    @endif
    <div class="card"><div class="card-head"><h3>{{ __('API publique') }}</h3><a href="{{ route('developers') }}" class="small">{{ __('Documentation') }}</a></div><div class="card-body">
        <p class="small muted">{{ __('Les clés augmentent la limite de requêtes de l\'API publique en lecture (candidats, programmes, questions, débats, événements, élus, élections).') }}</p>
        <form method="POST" action="{{ route('settings.api-keys.store') }}" class="row">@csrf<input name="name" class="input" placeholder="{{ __('Nom de la clé (ex. mon application)') }}" required aria-label="{{ __('Nom de la clé') }}"><button class="btn btn-primary">{{ __('Créer') }}</button></form>
    </div>
        @foreach($keys as $k)
            <div class="item"><x-icon name="key"/><div class="item-body"><strong>{{ $k->name }}</strong> <code class="small">{{ $k->prefix }}…</code>
                <div class="small faint">{{ __('Créée') }} {{ $k->created_at->diffForHumans() }} · {{ __('Dernière utilisation') }} : {{ $k->last_used_at?->diffForHumans() ?? '—' }} @if($k->revoked_at)<span class="badge badge-danger">{{ __('Révoquée') }}</span>@endif</div></div>
                @unless($k->revoked_at)<form method="POST" action="{{ route('settings.api-keys.destroy', $k) }}">@csrf @method('DELETE')<button class="btn btn-outline btn-sm">{{ __('Révoquer') }}</button></form>@endunless</div>
        @endforeach
    </div>
@endsection
