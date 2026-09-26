@extends('layouts.app')
@section('title', $program->title)
@section('content')
    <div class="page-head"><a href="{{ route('candidates.show', [$program->user->username, 'tab' => 'program']) }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a>
        <div><h1>{{ __('Programme') }}</h1><div class="small faint">{{ $program->user->candidateProfile?->full_name ?? $program->user->name }}</div></div></div>

    @if($program->status === 'draft')<div class="alert alert-warning"><x-icon name="edit"/><div>{{ __('Brouillon : ce programme n\'est pas encore public.') }}</div></div>@endif
    @if($version && $program->current_version_id !== $version->id)
        <div class="alert alert-info"><x-icon name="history"/><div>{{ __('Vous consultez la version :v. ', ['v' => $version->version_number]) }}<a href="{{ $program->url() }}">{{ __('Voir la version actuelle') }}</a></div></div>
    @endif

    @include('programs.body', ['program' => $program, 'version' => $version, 'categories' => $categories, 'selectedCategory' => $selectedCategory])
@endsection
@section('right')
    <div class="panel">
        <a href="{{ route('candidates.show', $program->user->username) }}" class="row" style="color:var(--text)">
            <img src="{{ $program->user->candidateProfile?->photoUrl() ?? $program->user->avatarUrl() }}" alt="" class="avatar">
            <div><strong>{{ $program->user->candidateProfile?->full_name ?? $program->user->name }}</strong><div class="small muted">{{ position_name($program->user->candidateProfile?->position_sought) }}</div></div></a>
        <a href="{{ route('compare.index', ['c' => [$program->user->username]]) }}" class="btn btn-outline btn-block mt"><x-icon name="compare"/>{{ __('Comparer avec d\'autres candidats') }}</a>
    </div>
    <div class="panel">
        <h3>{{ __('Versions') }}</h3>
        <div class="version-list">
            @foreach($program->versions as $v)
                @continue(! $v->published_at && ! $program->canManage(auth()->user()))
                <a href="{{ route('programs.version', [$program, $v]) }}" class="{{ $version?->id === $v->id ? 'active' : '' }}">
                    <span>v{{ $v->version_number }} @if($v->id === $program->current_version_id)<span class="badge badge-success">{{ __('actuelle') }}</span>@endif @unless($v->published_at)<span class="badge badge-warning">{{ __('brouillon') }}</span>@endunless</span>
                    <span class="small faint">{{ $v->published_at?->translatedFormat('d/m/Y') }}</span></a>
            @endforeach
        </div>
    </div>
    <div class="panel">
        <h3>{{ __('Historique') }}</h3>
        <div class="timeline">
            @forelse($program->changeLogs->take(10) as $log)
                <div class="t-item"><div class="small faint">{{ $log->created_at->translatedFormat('d M Y') }}</div><div class="small">{{ $log->note ?: __(ucfirst($log->field)).' : '.$log->new_value }}</div></div>
            @empty
                <p class="small muted">{{ __('Aucune modification.') }}</p>
            @endforelse
        </div>
    </div>
@endsection
