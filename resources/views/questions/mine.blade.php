@extends('layouts.app')
@section('title', __('Historique des questions'))
@section('content')
    <div class="page-head"><a href="{{ route('questions.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Historique des questions') }}</h1></div>
    <nav class="tabs card" style="margin-bottom:.75rem">
        <a href="?tab=asked" class="tab {{ $tab === 'asked' ? 'active' : '' }}">{{ __('Mes questions') }}</a>
        <a href="?tab=supported" class="tab {{ $tab === 'supported' ? 'active' : '' }}">{{ __('Questions soutenues') }}</a>
    </nav>
    <div class="card">
        @forelse($questions as $q)
            <a href="{{ $q->url() }}" class="item" style="color:var(--text)"><div class="item-body">
                <strong>{{ $q->title }}</strong>
                <div class="small muted">{{ $q->created_at->translatedFormat('d M Y') }} · {{ $q->candidate ? __('à :name', ['name' => $q->candidate->name]) : __('Question publique') }} · {{ $q->supports_count }} {{ __('soutiens') }}</div>
            </div><span class="badge {{ $q->status === 'answered' ? 'badge-success' : ($q->status === 'closed' ? '' : 'badge-warning') }}">{{ $q->statusLabel() }}</span></a>
        @empty <div class="empty"><x-icon name="question"/><p>{{ __('Aucune question.') }}</p><a href="{{ route('questions.create') }}" class="btn btn-primary">{{ __('Poser une question') }}</a></div> @endforelse
    </div>
    {{ $questions->links() }}
@endsection
