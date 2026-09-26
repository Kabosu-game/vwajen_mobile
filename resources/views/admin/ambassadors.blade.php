@extends('layouts.admin')
@section('title', __('Programme ambassadeurs'))
@section('content')
    <div class="pills mb">@foreach(['pending' => __('En attente'), 'approved' => __('Approuvés'), 'rejected' => __('Refusés'), 'all' => __('Tous')] as $k => $l)<a href="?status={{ $k }}" class="pill {{ request('status', 'pending') === $k ? 'active' : '' }}">{{ $l }}</a>@endforeach</div>
    @forelse($ambassadors as $a)
        <div class="card"><div class="card-body row top">
            <img src="{{ $a->user->avatarUrl() }}" class="avatar" alt="">
            <div class="grow"><a href="{{ route('admin.users.show', $a->user_id) }}"><strong>{{ $a->user->name }}</strong></a> <span class="small faint">{{ $a->city }}, {{ country_name($a->country) }} · {{ $a->created_at->diffForHumans() }}</span>
                <p class="small mt-sm">{{ $a->motivation }}</p><div class="small faint">{{ __('Code') }} : {{ $a->referral_code }} · {{ $a->referrals_count }} {{ __('parrainages') }}</div></div>
            <form method="POST" action="{{ route('admin.ambassadors.decide', $a) }}" class="row">@csrf
                <button name="status" value="approved" class="btn btn-success btn-sm" @disabled($a->status === 'approved')>{{ __('Approuver') }}</button>
                <button name="status" value="rejected" class="btn btn-outline btn-sm" @disabled($a->status === 'rejected')>{{ __('Refuser') }}</button></form>
        </div></div>
    @empty<div class="card empty"><p>{{ __('Aucune candidature.') }}</p></div>@endforelse
    {{ $ambassadors->links() }}
@endsection
