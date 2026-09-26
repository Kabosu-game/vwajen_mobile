@extends('layouts.admin')
@section('title', __('Monitoring'))
@section('content')
    <div class="grid grid-3 mb">
        @foreach($checks as $name => $c)
            <div class="stat"><div class="row"><span class="status-dot {{ $c['ok'] ? 'ok' : 'ko' }}"></span><strong>{{ ucfirst(str_replace('_', ' ', $name)) }}</strong></div><div class="l">{{ $c['detail'] }}</div></div>
        @endforeach
    </div>
    <div class="grid grid-2">
        <div class="card"><div class="card-head"><h3>{{ __('Environnement') }}</h3></div><div class="card-body"><dl class="kv">@foreach($env as $k => $v)<dt>{{ __($k) }}</dt><dd>{{ $v }}</dd>@endforeach</dl></div></div>
        <div class="card"><div class="card-head"><h3>{{ __('Tâches planifiées') }}</h3></div><div class="card-body small">
            <p>{{ __('Lancez le planificateur (cron chaque minute) :') }}</p><pre>* * * * * php {{ base_path('artisan') }} schedule:run</pre>
            <ul><li><code>vwajen:reminders</code> — {{ __('chaque minute') }}</li><li><code>vwajen:lift-suspensions</code> — {{ __('5 min') }}</li><li><code>vwajen:cleanup</code> — {{ __('horaire') }}</li><li><code>vwajen:expire-verifications</code> — {{ __('quotidien') }}</li><li><code>vwajen:purge-accounts</code> — {{ __('quotidien 03:00') }}</li></ul>
        </div></div>
    </div>
    <div class="card"><div class="card-head"><h3>{{ __('Erreurs récentes') }}</h3></div>
        @forelse($logErrors as $e)<div class="item"><span class="badge badge-danger">{{ $e[2] }}</span><div class="item-body small"><div class="faint">{{ $e[1] }}</div><code style="white-space:pre-wrap">{{ $e[3] }}</code></div></div>
        @empty<div class="p small muted">{{ __('Aucune erreur récente.') }} ✓</div>@endforelse
    </div>
@endsection
