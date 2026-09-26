@extends('layouts.app')
@section('title', __('Communautés'))
@section('content')
    <div class="page-head"><h1>{{ __('Communautés') }}</h1><div class="actions">@auth<a href="{{ route('communities.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus"/>{{ __('Créer') }}</a>@endauth</div></div>
    @if($mine->isNotEmpty())
        <div class="card"><div class="card-head"><h3>{{ __('Mes communautés') }}</h3></div><div class="card-body chip-row">
            @foreach($mine as $c)<a href="{{ $c->url() }}" class="center" style="width:90px;flex-shrink:0;color:var(--text)"><img src="{{ $c->avatarUrl() }}" alt="" class="avatar avatar-lg avatar-sq" style="margin:0 auto"><div class="small truncate mt-sm">{{ $c->name }}</div></a>@endforeach
        </div></div>
    @endif
    <form method="GET" class="filters">
        <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Rechercher une communauté') }}" aria-label="{{ __('Rechercher') }}">
        <select name="category" class="select" aria-label="{{ __('Thème') }}"><option value="">{{ __('Tous les thèmes') }}</option>@foreach($categories as $c)<option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>@endforeach</select>
        <select name="visibility" class="select" aria-label="{{ __('Type') }}"><option value="">{{ __('Publiques et privées') }}</option><option value="public" @selected(request('visibility') === 'public')>{{ __('Publiques') }}</option><option value="private" @selected(request('visibility') === 'private')>{{ __('Privées') }}</option></select>
        <label class="check mb-0"><input type="checkbox" name="diaspora" value="1" @checked(request('diaspora'))>{{ __('Diaspora') }}</label>
        <button class="btn btn-outline">{{ __('Filtrer') }}</button>
    </form>
    <div class="card">
        @forelse($communities as $c)
            <a href="{{ $c->url() }}" class="item" style="color:var(--text)">
                <img src="{{ $c->avatarUrl() }}" alt="" class="avatar avatar-lg avatar-sq">
                <div class="item-body">
                    <div class="row"><strong>{{ $c->name }}</strong>@if($c->visibility === 'private')<x-icon name="lock" style="width:14px;height:14px"/>@endif @if($c->is_diaspora)<span class="badge badge-info">{{ __('Diaspora') }}</span>@endif</div>
                    <div class="small muted clamp-2">{{ $c->description }}</div>
                    <div class="small faint">{{ trans_choice(':n membre|:n membres', $c->members_count, ['n' => short_number($c->members_count)]) }}@if($c->city) · {{ $c->city }}@endif</div>
                </div>
            </a>
        @empty <div class="empty"><x-icon name="users"/><p>{{ __('Aucune communauté.') }}</p></div> @endforelse
    </div>
    {{ $communities->links() }}
@endsection
