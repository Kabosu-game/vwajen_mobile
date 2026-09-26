@extends('layouts.app')
@section('title', 'Kesyon pou kandida yo')
@section('content')
    <div class="page-head"><div><h1>Kesyon pou kandida yo</h1><div class="small faint">{{ __('Posez vos questions aux candidats et soutenez celles des autres') }}</div></div>
        <div class="actions"><a href="{{ route('questions.create') }}" class="btn btn-primary btn-sm" data-auth><x-icon name="plus"/>{{ __('Poser une question') }}</a></div></div>

    <div class="card">
        <nav class="tabs">
            <a href="{{ route('questions.index', request()->except('status', 'page')) }}" class="tab {{ ! $status ? 'active' : '' }}">{{ __('Toutes') }} ({{ $counts->sum() }})</a>
            <a href="{{ route('questions.index', ['status' => 'open'] + request()->except('status', 'page')) }}" class="tab {{ $status === 'open' ? 'active' : '' }}">{{ __('Ouvertes') }} ({{ $counts['open'] ?? 0 }})</a>
            <a href="{{ route('questions.index', ['status' => 'answered'] + request()->except('status', 'page')) }}" class="tab {{ $status === 'answered' ? 'active' : '' }}">{{ __('Répondues') }} ({{ $counts['answered'] ?? 0 }})</a>
            <a href="{{ route('questions.index', ['status' => 'closed'] + request()->except('status', 'page')) }}" class="tab {{ $status === 'closed' ? 'active' : '' }}">{{ __('Fermées') }} ({{ $counts['closed'] ?? 0 }})</a>
        </nav>
        <form method="GET" class="card-body filters mb-0" style="margin:0">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <select name="scope" class="select" aria-label="{{ __('Type') }}"><option value="">{{ __('Publiques et adressées') }}</option>
                <option value="public" @selected(request('scope') === 'public')>{{ __('Questions publiques') }}</option>
                <option value="targeted" @selected(request('scope') === 'targeted')>{{ __('Adressées à un candidat') }}</option></select>
            <select name="category" class="select" aria-label="{{ __('Thème') }}"><option value="">{{ __('Tous les thèmes') }}</option>
                @foreach($categories as $c)<option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>@endforeach</select>
            <select name="sort" class="select" aria-label="{{ __('Tri') }}"><option value="">{{ __('Les plus soutenues') }}</option><option value="recent" @selected(request('sort') === 'recent')>{{ __('Les plus récentes') }}</option></select>
            <button class="btn btn-outline">{{ __('Filtrer') }}</button>
        </form>
    </div>

    <div class="card">
        @forelse($questions as $q) @include('partials.question-row', ['q' => $q]) @empty
            <div class="empty"><x-icon name="question"/><h3>{{ __('Aucune question') }}</h3><p>{{ __('Soyez le premier à interpeller les candidats.') }}</p></div>
        @endforelse
    </div>
    {{ $questions->links() }}
@endsection
