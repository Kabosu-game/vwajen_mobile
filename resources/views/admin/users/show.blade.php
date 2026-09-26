@extends('layouts.admin')
@section('title', $user->name)
@section('actions')
    <a href="{{ $user->profileUrl() }}" class="btn btn-outline btn-sm" target="_blank"><x-icon name="external"/>{{ __('Profil public') }}</a>
@endsection
@php $me = auth()->user(); @endphp
@section('content')
    <div class="split-layout">
        <div>
            <div class="card"><div class="card-body row top">
                <img src="{{ $user->avatarUrl() }}" alt="" class="avatar avatar-lg">
                <div class="grow">
                    <h2 style="margin:0">{{ $user->name }}@include('partials.verified', ['u' => $user])</h2>
                    <div class="muted">{{ '@'.$user->username }} · {{ $user->accountTypeLabel() }} · #{{ $user->id }}</div>
                    <div class="row wrap mt-sm" style="gap:.3rem">
                        @if($user->trashed())<span class="badge badge-danger">{{ __('Supprimé') }}</span>@endif
                        <span class="badge {{ $user->status === 'active' ? 'badge-success' : 'badge-danger' }}">{{ __($user->status) }}@if($user->suspended_until) → {{ $user->suspended_until->format('d/m/Y H:i') }}@endif</span>
                        @if($user->is_verified)<span class="badge badge-gold">{{ $user->badgeLabel() }} → {{ $user->verified_until?->format('d/m/Y') ?? '∞' }}</span>@endif
                        @foreach($user->roles as $r)<span class="badge badge-primary">{{ $r->label }}</span>@endforeach
                    </div>
                    @if($user->status_reason)<div class="small mt-sm">{{ __('Motif') }} : {{ $user->status_reason }}</div>@endif
                </div>
            </div>
            <div class="card-foot"><dl class="kv small">
                <dt>E-mail</dt><dd>{{ $user->email }} {!! $user->email_verified_at ? '✓' : '<span class="badge badge-warning">'.e(__('non vérifié')).'</span>' !!}</dd>
                <dt>{{ __('Téléphone') }}</dt><dd>{{ $user->phone ?: '—' }} {{ $user->phone_verified_at ? '✓' : '' }}</dd>
                <dt>{{ __('Pays / département') }}</dt><dd>{{ country_name($user->country) }} {{ department_name($user->department) }}</dd>
                <dt>{{ __('Inscription') }}</dt><dd>{{ $user->created_at->format('d/m/Y H:i') }} · IP {{ $user->registration_ip ?? '—' }}</dd>
                <dt>{{ __('Dernière activité') }}</dt><dd>{{ $user->last_active_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                <dt>{{ __('Connexions externes') }}</dt><dd>{{ collect(['Google' => $user->google_id, 'Apple' => $user->apple_id])->filter()->keys()->implode(', ') ?: '—' }}</dd>
                <dt>{{ __('Activité') }}</dt><dd>{{ $counts['posts'] }} {{ __('publications') }} · {{ $counts['videos'] }} {{ __('vidéos') }} · {{ $counts['comments'] }} {{ __('commentaires') }} · {{ $user->followers_count }} {{ __('abonnés') }} · {{ $counts['reports_made'] }} {{ __('signalements faits') }}</dd>
            </dl></div></div>

            <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="card"><div class="card-head"><h3>{{ __('Modifier le compte') }}</h3></div><div class="card-body">
                @csrf @method('PUT')
                <div class="grid grid-2">
                    <div class="field"><label class="label">{{ __('Nom') }}</label><input name="name" class="input" value="{{ $user->name }}"></div>
                    <div class="field"><label class="label">{{ __('Nom d\'utilisateur') }}</label><input name="username" class="input" value="{{ $user->username }}"></div>
                    <div class="field"><label class="label">E-mail</label><input name="email" type="email" class="input" value="{{ $user->email }}"></div>
                    <div class="field"><label class="label">{{ __('Type de compte') }}</label><select name="account_type" class="select">@foreach(\App\Models\User::ACCOUNT_TYPES as $t)<option value="{{ $t }}" @selected($user->account_type === $t)>{{ __($t) }}</option>@endforeach</select></div>
                </div>
                <label class="check"><input type="checkbox" name="email_verified" value="1" @checked($user->email_verified_at)>{{ __('E-mail vérifié') }}</label>
                <label class="check"><input type="checkbox" name="phone_verified" value="1" @checked($user->phone_verified_at)>{{ __('Téléphone vérifié') }}</label>
                <button class="btn btn-primary btn-sm">{{ __('Enregistrer') }}</button>
            </div></form>

            <div class="card"><div class="card-head"><h3>{{ __('Historique des sanctions') }}</h3></div>
                @forelse($sanctions as $s)
                    <div class="item"><div class="item-body"><strong>{{ $s->typeLabel() }}</strong> @if($s->revoked_at)<span class="badge badge-success">{{ __('Levée') }}</span>@elseif($s->isActive())<span class="badge badge-danger">{{ __('Active') }}</span>@endif
                        <div class="small">{{ $s->reason }}</div><div class="small faint">{{ $s->created_at->format('d/m/Y') }} · {{ $s->moderator?->name }}@if($s->expires_at) · {{ __('expire') }} {{ $s->expires_at->format('d/m/Y') }}@endif</div></div>
                        @if(! $s->revoked_at)<form method="POST" action="{{ route('admin.sanctions.revoke', $s) }}" data-confirm="{{ __('Lever cette sanction ?') }}">@csrf<button class="btn btn-outline btn-sm">{{ __('Lever') }}</button></form>@endif</div>
                @empty<div class="p small muted">{{ __('Aucune sanction.') }}</div>@endforelse
            </div>
            <div class="card"><div class="card-head"><h3>{{ __('Signalements le concernant') }}</h3></div>
                @forelse($reportsAgainst as $r)<a href="{{ route('admin.reports.show', $r) }}" class="item" style="color:var(--text)"><div class="item-body small"><strong>{{ \App\Models\Report::reasonLabel($r->reason) }}</strong> · {{ $r->reportable_type }} #{{ $r->reportable_id }} · <span class="badge">{{ __($r->status) }}</span> · {{ $r->created_at->format('d/m/Y') }}</div></a>
                @empty<div class="p small muted">{{ __('Aucun signalement.') }}</div>@endforelse
            </div>
            <div class="card"><div class="card-head"><h3>{{ __('Appareils et sessions') }}</h3>
                <form method="POST" action="{{ route('admin.users.logout', $user->id) }}" data-confirm="{{ __('Déconnecter toutes les sessions ?') }}">@csrf<button class="btn btn-outline btn-sm">{{ __('Déconnecter partout') }}</button></form></div>
                <div class="table-wrap"><table class="table"><tbody>
                    @foreach($user->devices as $d)<tr><td>{{ $d->name }}</td><td class="small">{{ $d->ip_address }}</td><td class="small">{{ $d->last_used_at?->diffForHumans() }}</td><td>{!! $d->revoked_at ? '<span class="badge badge-danger">'.e(__('révoqué')).'</span>' : '' !!}</td></tr>@endforeach
                    @foreach($sessions as $s)<tr><td class="small">{{ __('Session') }} {{ \Illuminate\Support\Str::limit($s->user_agent, 60) }}</td><td class="small">{{ $s->ip_address }}</td><td class="small">{{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</td><td></td></tr>@endforeach
                </tbody></table></div>
            </div>
            <div class="card"><div class="card-head"><h3>{{ __('Journal d\'audit') }}</h3></div><div class="table-wrap"><table class="table"><tbody>
                @forelse($audit as $a)<tr><td class="small faint">{{ $a->created_at->format('d/m/Y H:i') }}</td><td class="small">{{ $a->user?->name }}</td><td><code>{{ $a->action }}</code></td><td class="small">{{ $a->ip_address }}</td></tr>@empty<tr><td class="muted small">—</td></tr>@endforelse
            </tbody></table></div></div>
        </div>

        <aside>
            @if($me->hasPermission('moderation.manage') && ! $user->hasRole('superadmin'))
                <form method="POST" action="{{ route('admin.sanctions.store', $user->id) }}" class="card"><div class="card-head"><h3>{{ __('Sanctionner') }}</h3></div><div class="card-body">
                    @csrf
                    <select name="type" class="select mb" style="margin-bottom:.5rem" aria-label="{{ __('Type') }}"><option value="warning">{{ __('Avertissement') }}</option><option value="suspension">{{ __('Suspension') }}</option><option value="ban">{{ __('Bannissement') }}</option></select>
                    <input type="number" name="days" min="1" class="input mb" placeholder="{{ __('Durée (jours) pour une suspension') }}" style="margin-bottom:.5rem" aria-label="{{ __('Durée') }}">
                    <textarea name="reason" class="textarea" rows="2" required placeholder="{{ __('Motif (communiqué à l\'utilisateur)') }}" aria-label="{{ __('Motif') }}"></textarea>
                    <button class="btn btn-danger btn-sm mt-sm btn-block">{{ __('Appliquer') }}</button>
                </div></form>
            @endif
            @if($me->hasPermission('roles.manage'))
                <form method="POST" action="{{ route('admin.users.roles', $user->id) }}" class="card"><div class="card-head"><h3>{{ __('Rôles') }}</h3></div><div class="card-body">
                    @csrf
                    @foreach($roles as $r)<label class="check"><input type="checkbox" name="roles[]" value="{{ $r->id }}" @checked($user->roles->contains($r)) @disabled($r->name === 'superadmin' && ! $me->hasRole('superadmin'))>{{ $r->label }}</label>@endforeach
                    <button class="btn btn-primary btn-sm">{{ __('Mettre à jour') }}</button>
                </div></form>
            @endif
            @if($me->hasPermission('verifications.manage') && $user->is_verified)
                <form method="POST" action="{{ route('admin.verifications.revoke', $user->id) }}" class="card"><div class="card-head"><h3>{{ __('Retirer le badge') }}</h3></div><div class="card-body">
                    @csrf<input name="note" class="input" required placeholder="{{ __('Motif') }}" aria-label="{{ __('Motif') }}"><button class="btn btn-outline btn-sm mt-sm">{{ __('Retirer') }}</button></div></form>
            @endif
            @if($user->verificationRequests->isNotEmpty())
                <div class="card"><div class="card-head"><h3>{{ __('Historique de vérification') }}</h3></div>
                    @foreach($user->verificationRequests as $v)<a href="{{ route('admin.verifications.show', $v) }}" class="item small" style="color:var(--text)">{{ \App\Models\VerificationRequest::typeLabel($v->type) }} · {{ $v->statusLabel() }} · {{ $v->created_at->format('d/m/Y') }}</a>@endforeach</div>
            @endif
            @if($me->hasPermission('users.manage'))
                @if($user->trashed())
                    <form method="POST" action="{{ route('admin.users.restore', $user->id) }}">@csrf<button class="btn btn-success btn-block">{{ __('Restaurer le compte') }}</button></form>
                @elseif(! $user->hasRole('superadmin') && $user->id !== $me->id)
                    <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" data-confirm="{{ __('Supprimer ce compte ? (restaurable)') }}">@csrf @method('DELETE')<button class="btn btn-danger-outline btn-block">{{ __('Supprimer le compte') }}</button></form>
                @endif
            @endif
        </aside>
    </div>
@endsection
