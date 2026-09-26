@extends('settings.layout')
@section('title', __('Appareils'))
@section('settings')
    <div class="card"><div class="card-head"><h3>{{ __('Gestion des appareils') }}</h3></div>
        <p class="p small muted" style="padding-bottom:0">{{ __('Appareils utilisés pour vous connecter. Révoquez un appareil que vous ne reconnaissez pas : il sera déconnecté immédiatement.') }}</p>
        @forelse($devices as $d)
            <div class="item">
                <x-icon name="{{ in_array($d->platform, ['Android', 'iOS']) ? 'phone' : 'monitor' }}"/>
                <div class="item-body">
                    <form method="POST" action="{{ route('settings.devices.update', $d) }}" class="row wrap">@csrf @method('PUT')
                        <input name="name" value="{{ $d->name }}" class="input input-sm" style="max-width:240px" aria-label="{{ __('Nom de l\'appareil') }}">
                        <label class="check small mb-0"><input type="checkbox" name="trusted" value="1" @checked($d->trusted)>{{ __('Appareil de confiance') }}</label>
                        <button class="btn btn-ghost btn-sm">{{ __('Enregistrer') }}</button>
                    </form>
                    <div class="small faint">{{ $d->browser }} · {{ $d->platform }} · {{ $d->ip_address }} · {{ __('utilisé') }} {{ $d->last_used_at?->diffForHumans() }}
                        @if($d->session_id === $currentSession)<span class="badge badge-success">{{ __('Cet appareil') }}</span>@endif
                        @if($d->revoked_at)<span class="badge badge-danger">{{ __('Révoqué') }}</span>@endif</div>
                </div>
                @if(! $d->revoked_at && $d->session_id !== $currentSession)
                    <form method="POST" action="{{ route('settings.devices.revoke', $d) }}" data-confirm="{{ __('Déconnecter cet appareil ?') }}">@csrf @method('DELETE')<button class="btn btn-danger-outline btn-sm">{{ __('Révoquer') }}</button></form>
                @endif
            </div>
        @empty <div class="p muted small">{{ __('Aucun appareil enregistré.') }}</div> @endforelse
    </div>
@endsection
