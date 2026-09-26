@extends('layouts.admin')
@section('title', __('Historique des sanctions'))
@section('content')
    <form method="GET" class="filters">
        <select name="type" class="select" aria-label="{{ __('Type') }}"><option value="">{{ __('Tous types') }}</option>@foreach(\App\Models\Sanction::TYPE_LABELS as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ __($l) }}</option>@endforeach</select>
        <input name="user" value="{{ request('user') }}" class="input" placeholder="@username" aria-label="{{ __('Utilisateur') }}">
        <label class="check mb-0"><input type="checkbox" name="active" value="1" @checked(request('active'))>{{ __('Actives uniquement') }}</label>
        <button class="btn btn-primary">{{ __('Filtrer') }}</button></form>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Utilisateur') }}</th><th>{{ __('Type') }}</th><th>{{ __('Motif') }}</th><th>{{ __('Modérateur') }}</th><th>{{ __('Expiration') }}</th><th>{{ __('État') }}</th><th></th></tr></thead>
        <tbody>@forelse($sanctions as $s)
            <tr><td class="small">{{ $s->created_at->format('d/m/Y H:i') }}</td><td>@if($s->user)<a href="{{ route('admin.users.show', $s->user_id) }}">{{ '@'.$s->user->username }}</a>@endif</td>
                <td><span class="badge">{{ $s->typeLabel() }}</span></td><td class="small" style="max-width:260px">{{ $s->reason }}</td><td class="small">{{ $s->moderator?->name }}</td>
                <td class="small">{{ $s->expires_at?->format('d/m/Y') ?? '—' }}</td>
                <td>@if($s->revoked_at)<span class="badge badge-success" title="{{ $s->revokedBy?->name }}">{{ __('Levée') }}</span>@elseif($s->isActive())<span class="badge badge-danger">{{ __('Active') }}</span>@else<span class="badge">{{ __('Expirée') }}</span>@endif</td>
                <td class="actions-cell">@if(! $s->revoked_at)<form method="POST" action="{{ route('admin.sanctions.revoke', $s) }}" data-confirm="{{ __('Lever cette sanction ?') }}">@csrf<button class="btn btn-outline btn-sm">{{ __('Lever') }}</button></form>@endif</td></tr>
        @empty<tr><td colspan="8" class="muted">—</td></tr>@endforelse</tbody>
    </table></div></div>{{ $sanctions->links() }}
@endsection
