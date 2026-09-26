@extends('layouts.admin')
@section('title', __('Demande de vérification #:id', ['id' => $verification->id]))
@php $u = $verification->user; @endphp
@section('content')
    <div class="split-layout">
        <div>
            <div class="card"><div class="card-body row top">
                <img src="{{ $u->avatarUrl() }}" class="avatar avatar-lg" alt="">
                <div class="grow"><h2 style="margin:0">{{ $u->name }}</h2><div class="muted">{{ '@'.$u->username }} · {{ $u->email }} · {{ __('inscrit le :d', ['d' => $u->created_at->format('d/m/Y')]) }}</div>
                    <div class="row mt-sm" style="gap:.3rem"><span class="badge badge-primary">{{ \App\Models\VerificationRequest::typeLabel($verification->type) }}</span><span class="badge">{{ $verification->statusLabel() }}</span></div></div>
                <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-outline btn-sm">{{ __('Compte') }}</a>
            </div></div>
            <div class="card"><div class="card-head"><h3>{{ __('Message du demandeur') }}</h3></div><div class="card-body">{{ $verification->message ?: '—' }}</div></div>
            @if($verification->type === 'candidate' && $u->candidateProfile)
                @php $p = $u->candidateProfile; @endphp
                <div class="card"><div class="card-head"><h3>{{ __('Profil candidat') }}</h3><a href="{{ route('candidates.show', $u->username) }}" target="_blank" class="small">{{ __('Voir') }}</a></div><div class="card-body"><dl class="kv">
                    <dt>{{ __('Nom') }}</dt><dd>{{ $p->full_name }}</dd><dt>{{ __('Poste') }}</dt><dd>{{ position_name($p->position_sought) }}</dd><dt>{{ __('Circonscription') }}</dt><dd>{{ $p->constituency }}</dd>
                    <dt>{{ __('Parti') }}</dt><dd>{{ $p->party }}</dd><dt>{{ __('Biographie') }}</dt><dd class="small">{{ \Illuminate\Support\Str::limit($p->biography, 400) }}</dd></dl></div></div>
            @endif
            @if($verification->type === 'organization' && $u->organizationProfile)
                <div class="card"><div class="card-head"><h3>{{ __('Organisation') }}</h3></div><div class="card-body"><dl class="kv"><dt>{{ __('Raison sociale') }}</dt><dd>{{ $u->organizationProfile->legal_name }}</dd><dt>{{ __('Type') }}</dt><dd>{{ __($u->organizationProfile->org_type) }}</dd><dt>{{ __('N°') }}</dt><dd>{{ $u->organizationProfile->registration_number ?: '—' }}</dd></dl></div></div>
            @endif
            @if($verification->type === 'official' && $u->officialProfile)
                <div class="card"><div class="card-head"><h3>{{ __('Élu') }}</h3></div><div class="card-body"><dl class="kv"><dt>{{ __('Fonction') }}</dt><dd>{{ $u->officialProfile->office }}</dd><dt>{{ __('Institution') }}</dt><dd>{{ $u->officialProfile->institution }}</dd></dl></div></div>
            @endif
            <div class="card"><div class="card-head"><h3>{{ __('Documents justificatifs') }}</h3></div>
                @forelse($verification->documents as $d)
                    <div class="item"><x-icon name="file"/><div class="item-body"><strong>{{ $d->label }}</strong><div class="small faint">{{ $d->mime }}</div></div>
                        <a href="{{ route('admin.verifications.document', $d) }}" class="btn btn-outline btn-sm"><x-icon name="download"/>{{ __('Télécharger') }}</a></div>
                @empty<div class="p muted small">{{ __('Aucun document.') }}</div>@endforelse
                <div class="card-foot small faint">{{ __('Chaque consultation de document est enregistrée dans le journal d\'audit.') }}</div>
            </div>
        </div>
        <aside>
            @if($verification->status === 'pending')
                <form method="POST" action="{{ route('admin.verifications.approve', $verification) }}" class="card"><div class="card-head"><h3 style="color:var(--success)">{{ __('Valider') }}</h3></div><div class="card-body">
                    @csrf
                    <label class="label" for="months">{{ __('Durée de validité (mois)') }}</label><input id="months" type="number" name="months" value="{{ config('vwajen.verification_months') }}" min="1" max="60" class="input">
                    <textarea name="note" class="textarea mt-sm" rows="2" placeholder="{{ __('Note (facultatif)') }}" aria-label="{{ __('Note') }}"></textarea>
                    <button class="btn btn-success btn-block mt-sm"><x-icon name="check"/>{{ __('Attribuer le badge') }}</button>
                </div></form>
                <form method="POST" action="{{ route('admin.verifications.reject', $verification) }}" class="card"><div class="card-head"><h3 style="color:var(--danger)">{{ __('Rejeter') }}</h3></div><div class="card-body">
                    @csrf<textarea name="note" class="textarea" rows="3" required placeholder="{{ __('Motif communiqué au demandeur') }}" aria-label="{{ __('Motif') }}"></textarea>
                    <button class="btn btn-danger btn-block mt-sm">{{ __('Rejeter la demande') }}</button></div></form>
            @else
                <div class="card card-body"><strong>{{ $verification->statusLabel() }}</strong><div class="small muted">{{ $verification->reviewer?->name }} · {{ $verification->reviewed_at?->format('d/m/Y H:i') }}</div>@if($verification->review_note)<p class="small mt-sm">{{ $verification->review_note }}</p>@endif</div>
            @endif
            <div class="card"><div class="card-head"><h3>{{ __('Historique de vérification') }}</h3></div>
                @foreach($history as $h)<a href="{{ route('admin.verifications.show', $h) }}" class="item small" style="color:var(--text)">{{ \App\Models\VerificationRequest::typeLabel($h->type) }} · {{ $h->statusLabel() }}<span class="right faint">{{ $h->created_at->format('d/m/Y') }}</span></a>@endforeach</div>
        </aside>
    </div>
@endsection
