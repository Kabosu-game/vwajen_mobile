@extends('layouts.guest')
@section('title', __('Compte restreint'))
@section('content')
    <div class="card"><div class="card-body center">
        <x-icon name="ban" style="width:48px;height:48px;color:var(--danger);margin:0 auto .6rem"/>
        <h1 style="font-size:1.35rem">{{ $user->status === 'banned' ? __('Compte banni') : __('Compte suspendu') }}</h1>
        @if($user->status === 'suspended' && $user->suspended_until)<p>{{ __('Jusqu\'au :date', ['date' => $user->suspended_until->translatedFormat('d F Y à H:i')]) }}</p>@endif
        @if($user->status_reason)<div class="alert alert-warning" style="text-align:left"><x-icon name="info"/><div><strong>{{ __('Motif') }} :</strong> {{ $user->status_reason }}</div></div>@endif
        <p class="small muted">{{ __('Vous pouvez contester cette décision. Un autre modérateur examinera votre appel.') }}</p>
        <div class="stack-sm">
            @if($sanction && ! $sanction->appeals()->where('status', 'pending')->exists())<a href="{{ route('appeals.create', $sanction) }}" class="btn btn-primary btn-block">{{ __('Faire appel') }}</a>
            @elseif($sanction)<div class="alert alert-info">{{ __('Votre appel est en cours d\'examen.') }}</div>@endif
            <a href="{{ route('settings.sanctions') }}" class="btn btn-outline btn-block">{{ __('Historique des sanctions') }}</a>
            <form method="POST" action="{{ route('settings.export') }}">@csrf<button class="btn btn-ghost btn-block">{{ __('Exporter mes données') }}</button></form>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-block">{{ __('Se déconnecter') }}</button></form>
        </div>
        <p class="small faint mt"><a href="{{ route('pages.show', 'rules') }}">{{ __('Règles de la communauté') }}</a></p>
    </div></div>
@endsection
