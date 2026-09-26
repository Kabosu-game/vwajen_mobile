@extends('layouts.admin')
@section('title', __('Appels'))
@section('content')
    <div class="pills mb">@foreach(['pending' => __('En attente'), 'accepted' => __('Acceptés'), 'rejected' => __('Rejetés'), 'all' => __('Tous')] as $k => $l)<a href="?status={{ $k }}" class="pill {{ request('status', 'pending') === $k ? 'active' : '' }}">{{ $l }}</a>@endforeach</div>
    @forelse($appeals as $a)
        <div class="card"><div class="card-body">
            <div class="row between wrap"><div><a href="{{ route('admin.users.show', $a->user_id) }}"><strong>{{ $a->user->name }}</strong></a> · <span class="badge">{{ $a->sanction->typeLabel() }}</span> <span class="small faint">{{ $a->created_at->diffForHumans() }}</span></div>
                <span class="badge {{ ['accepted' => 'badge-success', 'rejected' => 'badge-danger'][$a->status] ?? 'badge-warning' }}">{{ __($a->status) }}</span></div>
            <div class="quote small mt-sm"><strong>{{ __('Sanction') }}</strong> ({{ $a->sanction->moderator?->name }}) : {{ $a->sanction->reason }}</div>
            <p class="mt-sm"><strong>{{ __('Appel') }} :</strong> {{ $a->body }}</p>
            @if($a->status === 'pending')
                <form method="POST" action="{{ route('admin.appeals.decide', $a) }}" class="mt-sm">@csrf
                    <textarea name="response" class="textarea" rows="2" required placeholder="{{ __('Réponse motivée à l\'utilisateur') }}" aria-label="{{ __('Réponse') }}"></textarea>
                    <div class="row mt-sm"><button name="decision" value="accepted" class="btn btn-success btn-sm">{{ __('Accepter (lever la sanction)') }}</button><button name="decision" value="rejected" class="btn btn-danger btn-sm">{{ __('Rejeter') }}</button></div>
                </form>
            @else
                <div class="small muted">{{ $a->reviewer?->name }} · {{ $a->reviewed_at?->format('d/m/Y') }} — {{ $a->response }}</div>
            @endif
        </div></div>
    @empty<div class="card empty"><p>{{ __('Aucun appel.') }}</p></div>@endforelse
    {{ $appeals->links() }}
@endsection
