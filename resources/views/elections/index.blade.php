@extends('layouts.app')
@section('title', __('Archives électorales'))
@php $types = ['presidential' => __('Présidentielle'), 'legislative' => __('Législatives'), 'municipal' => __('Municipales'), 'local' => __('Locales'), 'referendum' => __('Référendum')]; @endphp
@section('content')
    <div class="page-head"><div><h1>{{ __('Archives électorales') }}</h1><div class="small faint">{{ __('Résultats électoraux publics et suivi post-électoral') }}</div></div></div>
    <div class="card">
        @forelse($elections as $e)
            <a href="{{ route('elections.show', $e) }}" class="item" style="color:var(--text)"><x-icon name="archive"/>
                <div class="item-body"><strong>{{ $e->name }}</strong><div class="small faint">{{ $types[$e->type] ?? $e->type }} · {{ __('tour :n', ['n' => $e->round]) }} · {{ $e->held_on->translatedFormat('d F Y') }}</div></div>
                @if($e->results_published)<span class="badge badge-success">{{ __('Résultats publiés') }}</span>@else<span class="badge">{{ __('Résultats non publiés') }}</span>@endif</a>
        @empty<div class="empty"><x-icon name="archive"/><p>{{ __('Aucune élection archivée.') }}</p></div>@endforelse
    </div>
    {{ $elections->links() }}
    <a href="{{ route('observatory.index') }}" class="card row" style="padding:1rem;color:var(--text)"><x-icon name="chart"/><div class="grow"><strong>{{ __('Suivi post-électoral') }}</strong><div class="small muted">{{ __('Engagements des élus et réponses aux citoyens dans l\'Observatoire citoyen.') }}</div></div><x-icon name="chevron-right"/></a>
@endsection
