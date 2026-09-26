@extends('layouts.app')
@section('title', __('Recherche dans les conversations'))
@section('content')
    <div class="page-head"><a href="{{ route('messages.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Recherche dans les conversations') }}</h1></div>
    <form method="GET" class="search-box mb"><x-icon name="search"/><input type="search" name="q" value="{{ $q }}" class="input" placeholder="{{ __('Mot-clé…') }}" autofocus aria-label="{{ __('Mot-clé') }}"></form>
    <div class="card">
        @forelse($results as $m)
            <a href="{{ route('messages.show', $m->conversation_id) }}#m{{ $m->id }}" class="item" style="color:var(--text)">
                <img src="{{ $m->user->avatarUrl() }}" alt="" class="avatar avatar-sm">
                <div class="item-body"><div class="small faint">{{ $m->conversation->titleFor(auth()->user()) }} · {{ $m->created_at->translatedFormat('d M Y H:i') }}</div>
                    <div><strong>{{ $m->user->name }}</strong> : {{ \Illuminate\Support\Str::limit($m->body, 160) }}</div></div></a>
        @empty
            <div class="empty"><p>{{ $q ? __('Aucun message trouvé.') : __('Saisissez un mot-clé.') }}</p></div>
        @endforelse
    </div>
@endsection
