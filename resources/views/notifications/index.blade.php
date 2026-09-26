@extends('layouts.app')
@section('title', __('Notifications'))
@section('content')
    <div class="page-head"><h1>{{ __('Notifications') }}</h1>
        <div class="actions">
            @if(config('vwajen.vapid.public'))<button type="button" class="btn btn-ghost btn-sm" data-push-subscribe><x-icon name="phone"/>{{ __('Activer le push') }}</button>@endif
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-outline btn-sm"><x-icon name="check"/>{{ __('Tout marquer comme lu') }}</button></form>
            <a href="{{ route('settings.notifications') }}" class="btn btn-ghost btn-icon" aria-label="{{ __('Paramètres des notifications') }}"><x-icon name="settings"/></a>
        </div></div>
    @php $pending = auth()->user()->followRequests()->count(); @endphp
    @if($pending)
        <a href="{{ route('follow.requests') }}" class="card row" style="padding:.8rem 1rem;color:var(--text)"><x-icon name="user-plus"/><strong class="grow">{{ trans_choice(':n demande d\'abonnement|:n demandes d\'abonnement', $pending, ['n' => $pending]) }}</strong><x-icon name="chevron-right"/></a>
    @endif
    <nav class="tabs card" style="margin-bottom:.75rem">
        <a href="?" class="tab {{ ! $filter ? 'active' : '' }}">{{ __('Toutes') }}</a>
        <a href="?filter=unread" class="tab {{ $filter === 'unread' ? 'active' : '' }}">{{ __('Non lues') }}</a>
        <a href="?filter=mentions" class="tab {{ $filter === 'mentions' ? 'active' : '' }}">{{ __('Mentions') }}</a>
        <a href="?filter=civic" class="tab {{ $filter === 'civic' ? 'active' : '' }}">{{ __('Civique') }}</a>
    </nav>
    <div class="card">@include('notifications.list')</div>
    {{ $notifications->links() }}
@endsection
