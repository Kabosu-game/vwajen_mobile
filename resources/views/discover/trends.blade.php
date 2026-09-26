@extends('layouts.app')
@section('title', __('Tendances'))
@section('content')
    <div class="page-head"><a href="{{ route('discover.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Tendances') }}</h1></div>
    <div class="card"><div class="card-head"><h3>{{ __('Hashtags populaires (48 h)') }}</h3></div>
        @forelse($hashtags as $i => $t)
            <a href="{{ route('hashtags.show', $t->name) }}" class="item" style="color:var(--text)">
                <span class="faint" style="width:24px">{{ $i + 1 }}</span>
                <div class="item-body"><strong>#{{ $t->name }}</strong><div class="small faint">{{ trans_choice(':n publication|:n publications', $t->recent_uses, ['n' => $t->recent_uses]) }}</div></div>
            </a>
        @empty <div class="empty"><p>{{ __('Pas encore de tendances.') }}</p></div> @endforelse
    </div>
    <div class="section-title"><h2>{{ __('Publications populaires') }}</h2></div>
    <div class="card feed">@forelse($popular as $post) @include('partials.post-card', ['post' => $post]) @empty <div class="empty"><p>{{ __('Rien pour le moment.') }}</p></div> @endforelse</div>
@endsection
