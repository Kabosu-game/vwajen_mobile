@extends('layouts.app')
@section('title', __('Source : :name', ['name' => $source->name]))
@section('content')
    <div class="page-head"><a href="{{ url()->previous() }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Source') }}</h1></div>
    <div class="card"><div class="card-body">
        <h2>{{ $source->name }}</h2>
        <dl class="kv">
            <dt>{{ __('Lien source') }}</dt><dd>@if($source->url)<a href="{{ $source->url }}" target="_blank" rel="noopener nofollow">{{ $source->url }}</a>@else — @endif</dd>
            <dt>{{ __('Date de publication') }}</dt><dd>{{ $source->published_on?->translatedFormat('d F Y') ?? '—' }}</dd>
            <dt>{{ __('Statut de vérification') }}</dt><dd><span class="badge badge-{{ $source->statusColor() }}">{{ $source->statusLabel() }}</span></dd>
            <dt>{{ __('Fournie par') }}</dt><dd>{{ $source->provided_by_candidate ? __('Le candidat') : ($source->user?->name ?? '—') }}</dd>
            @if($source->verifier)<dt>{{ __('Vérifiée par') }}</dt><dd>{{ $source->verifier->name }} · {{ $source->verified_at?->translatedFormat('d M Y') }}</dd>@endif
            @if($source->note)<dt>{{ __('Note') }}</dt><dd>{{ $source->note }}</dd>@endif
            <dt>{{ __('Concerne') }}</dt><dd>@if($subject && method_exists($subject, 'url'))<a href="{{ $subject->url() }}">{{ \App\Support\Morph::label($source->sourceable_type) }} — {{ $subject->title ?? $subject->full_name ?? '#'.$subject->id }}</a>@else — @endif</dd>
        </dl>
    </div></div>
    <div class="card"><div class="card-head"><h3>{{ __('Historique de vérification') }}</h3></div><div class="card-body">
        <div class="timeline">
            @forelse($source->verifications as $v)
                <div class="t-item"><div class="small faint">{{ $v->created_at->translatedFormat('d M Y H:i') }} · {{ $v->user?->name }}</div>
                    <div>{{ $v->from_status ? __($v->from_status).' → ' : '' }}<strong>{{ __($v->to_status) }}</strong></div>@if($v->note)<div class="small muted">{{ $v->note }}</div>@endif</div>
            @empty<p class="muted small">{{ __('Aucun historique.') }}</p>@endforelse
        </div>
    </div></div>
@endsection
