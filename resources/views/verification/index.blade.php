@extends('layouts.app')
@section('title', __('Vérification du compte'))
@section('content')
    <div class="page-head"><a href="{{ route('settings.profile') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Vérification du compte') }}</h1></div>
    @if($user->is_verified)
        <div class="alert alert-success"><x-icon name="badge-check"/><div><strong>{{ $user->badgeLabel() }}</strong> — {{ __('valable jusqu\'au :date', ['date' => $user->verified_until?->translatedFormat('d F Y') ?? '∞']) }}</div></div>
    @endif
    <div class="card"><div class="card-body">
        <h2>{{ __('Pourquoi vérifier ?') }}</h2>
        <ul class="small muted">
            <li>{{ __('Badge vérifié visible sur votre profil et vos contenus.') }}</li>
            <li>{{ __('Accès aux lives (réservés aux comptes certifiés).') }}</li>
            <li>{{ __('Protection contre l\'usurpation d\'identité.') }}</li>
        </ul>
    </div></div>

    @if(! $requests->where('status', 'pending')->count())
        <form method="POST" action="{{ route('verification.store') }}" enctype="multipart/form-data" class="card" id="vr-form"><div class="card-head"><h3>{{ __('Demande de vérification') }}</h3></div><div class="card-body">
            @csrf
            <div class="field"><label class="label">{{ __('Type de vérification') }}</label>
                <div class="pills">
                    @foreach(\App\Models\VerificationRequest::TYPES as $t)
                        <label><input type="radio" name="type" value="{{ $t }}" class="pill-check" @checked(old('type', $user->account_type === 'candidate' ? 'candidate' : ($user->account_type === 'organization' ? 'organization' : 'public')) === $t)><span class="pill">{{ \App\Models\VerificationRequest::typeLabel($t) }}</span></label>
                    @endforeach
                </div>
                @if(! $user->candidateProfile)<div class="hint">{{ __('Pour une vérification de candidat, remplissez d\'abord') }} <a href="{{ route('candidates.register') }}">{{ __('votre profil candidat') }}</a>.</div>@endif
            </div>
            <div data-type="organization" class="grid grid-2">
                <div class="field"><label class="label" for="legal_name">{{ __('Raison sociale') }}</label><input id="legal_name" name="legal_name" class="input" value="{{ old('legal_name', $user->organizationProfile?->legal_name) }}"></div>
                <div class="field"><label class="label" for="org_type">{{ __('Type d\'organisation') }}</label><select id="org_type" name="org_type" class="select">@foreach(['party' => __('Parti politique'), 'ngo' => __('ONG'), 'media' => __('Média'), 'government' => __('Institution publique'), 'association' => __('Association'), 'company' => __('Entreprise'), 'other' => __('Autre')] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                <div class="field"><label class="label" for="registration_number">{{ __('Numéro d\'enregistrement') }}</label><input id="registration_number" name="registration_number" class="input"></div>
            </div>
            <div data-type="official" class="grid grid-2">
                <div class="field"><label class="label" for="office">{{ __('Fonction élue') }}</label><input id="office" name="office" class="input"></div>
                <div class="field"><label class="label" for="institution">{{ __('Institution') }}</label><input id="institution" name="institution" class="input"></div>
                <div class="field"><label class="label" for="constituency">{{ __('Circonscription') }}</label><input id="constituency" name="constituency" class="input"></div>
            </div>
            <div class="field"><label for="message" class="label">{{ __('Présentez votre demande') }}</label><textarea id="message" name="message" class="textarea" required maxlength="2000">{{ old('message') }}</textarea></div>
            <fieldset><legend>{{ __('Documents justificatifs') }}</legend>
                <p class="small muted">{{ __('Pièce d\'identité, document officiel, statuts… Stockés de manière privée, consultés uniquement par l\'équipe de vérification.') }}</p>
                @for($i = 0; $i < 3; $i++)
                    <div class="row" style="margin-bottom:.5rem"><input type="text" name="document_labels[]" class="input input-sm" placeholder="{{ __('Type de document') }}" style="max-width:220px" aria-label="{{ __('Type de document') }}">
                        <input type="file" name="documents[]" accept=".pdf,image/*" class="input input-sm grow" @if($i === 0) required @endif aria-label="{{ __('Fichier') }}"></div>
                @endfor
            </fieldset>
            <button class="btn btn-primary">{{ __('Envoyer la demande') }}</button>
        </div></form>
    @endif

    <div class="card"><div class="card-head"><h3>{{ __('Historique de vérification') }}</h3></div>
        @forelse($requests as $r)
            <div class="item"><div class="item-body">
                <strong>{{ \App\Models\VerificationRequest::typeLabel($r->type) }}</strong> <span class="badge {{ ['approved' => 'badge-success', 'rejected' => 'badge-danger', 'pending' => 'badge-warning'][$r->status] ?? '' }}">{{ $r->statusLabel() }}</span>
                <div class="small faint">{{ $r->created_at->translatedFormat('d M Y') }} · {{ trans_choice(':n document|:n documents', $r->documents->count(), ['n' => $r->documents->count()]) }}@if($r->expires_at) · {{ __('expire le :d', ['d' => $r->expires_at->translatedFormat('d M Y')]) }}@endif</div>
                @if($r->review_note)<div class="small mt-sm">{{ __('Note de l\'équipe') }} : {{ $r->review_note }}</div>@endif
            </div>
                @if($r->status === 'pending')<form method="POST" action="{{ route('verification.cancel', $r) }}">@csrf<button class="btn btn-ghost btn-sm">{{ __('Annuler') }}</button></form>@endif</div>
        @empty<div class="p muted small">{{ __('Aucune demande.') }}</div>@endforelse
    </div>
@endsection
@push('scripts')
<script>
(function () { const f = document.getElementById('vr-form'); if (!f) return;
    const sync = () => { const t = f.querySelector('input[name=type]:checked')?.value; f.querySelectorAll('[data-type]').forEach(el => el.hidden = el.dataset.type !== t); };
    f.addEventListener('change', sync); sync(); })();
</script>
@endpush
