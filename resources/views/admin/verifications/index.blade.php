@extends('layouts.admin')
@section('title', __('Vérifications'))
@section('content')
    <div class="pills mb">@foreach(['pending' => __('En attente'), 'approved' => __('Validées'), 'rejected' => __('Rejetées'), 'expired' => __('Expirées'), 'all' => __('Toutes')] as $k => $l)<a href="?status={{ $k }}" class="pill {{ $status === $k ? 'active' : '' }}">{{ $l }}</a>@endforeach
        <form method="GET" class="row"><input type="hidden" name="status" value="{{ $status }}"><select name="type" class="select input-sm" onchange="this.form.submit()" aria-label="{{ __('Type') }}"><option value="">{{ __('Tous types') }}</option>@foreach(\App\Models\VerificationRequest::TYPES as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ \App\Models\VerificationRequest::typeLabel($t) }}</option>@endforeach</select></form></div>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Demandeur') }}</th><th>{{ __('Type') }}</th><th>{{ __('Documents') }}</th><th>{{ __('Statut') }}</th><th>{{ __('Date') }}</th><th>{{ __('Examinée par') }}</th><th></th></tr></thead>
        <tbody>@forelse($requests as $r)
            <tr><td><div class="row"><img src="{{ $r->user->avatarUrl() }}" class="avatar avatar-sm" alt=""><div><strong>{{ $r->user->name }}</strong><div class="small faint">{{ '@'.$r->user->username }}</div></div></div></td>
                <td>{{ \App\Models\VerificationRequest::typeLabel($r->type) }}</td><td>{{ $r->documents_count }}</td><td><span class="badge">{{ $r->statusLabel() }}</span></td>
                <td class="small">{{ $r->created_at->format('d/m/Y') }}</td><td class="small">{{ $r->reviewer?->name ?? '—' }}</td>
                <td class="actions-cell"><a href="{{ route('admin.verifications.show', $r) }}" class="btn btn-primary btn-sm">{{ $r->status === 'pending' ? __('Examiner') : __('Voir') }}</a></td></tr>
        @empty<tr><td colspan="7" class="muted">{{ __('Aucune demande.') }}</td></tr>@endforelse</tbody>
    </table></div></div>{{ $requests->links() }}
    @if($expiring->isNotEmpty())
        <div class="card"><div class="card-head"><h3>{{ __('Badges expirant sous 30 jours') }}</h3></div>
            @foreach($expiring as $u)<div class="user-card"><img src="{{ $u->avatarUrl() }}" class="avatar avatar-sm" alt=""><div class="info"><a href="{{ route('admin.users.show', $u->id) }}">{{ $u->name }}</a> <span class="small faint">{{ $u->badgeLabel() }}</span></div><span class="badge badge-warning">{{ $u->verified_until->format('d/m/Y') }}</span></div>@endforeach</div>
    @endif
@endsection
