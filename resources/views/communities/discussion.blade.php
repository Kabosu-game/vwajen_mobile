@extends('layouts.app')
@section('title', $discussion->title)
@php $viewer = auth()->user(); @endphp
@section('content')
    <div class="page-head"><a href="{{ route('communities.show', [$community, 'tab' => 'discussions']) }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a>
        <div><h1 class="truncate">{{ $discussion->title }}</h1><div class="small faint">{{ $community->name }}</div></div></div>
    <article class="card" id="discussion-{{ $discussion->id }}" data-item="discussion:{{ $discussion->id }}"><div class="card-body">
        <div class="row"><img src="{{ $discussion->user->avatarUrl() }}" alt="" class="avatar">
            <div class="grow"><a href="{{ $discussion->user->profileUrl() }}" class="name">{{ $discussion->user->name }}</a><div class="small faint">{{ $discussion->created_at->translatedFormat('d M Y H:i') }}</div></div>
            @if($community->isModerator($viewer))
                <div class="dropdown"><button class="btn btn-ghost btn-sm" data-dropdown><x-icon name="shield"/></button>
                    <div class="menu">@foreach(['pin' => $discussion->is_pinned ? __('Désépingler') : __('Épingler'), 'lock' => $discussion->is_locked ? __('Déverrouiller') : __('Verrouiller'), 'hide' => $discussion->is_hidden ? __('Afficher') : __('Masquer')] as $a => $l)
                        <form method="POST" action="{{ route('discussions.moderate', [$community, $discussion]) }}">@csrf<input type="hidden" name="action" value="{{ $a }}"><button>{{ $l }}</button></form>@endforeach
                    </div></div>
            @endif
            @if($viewer && ($viewer->id === $discussion->user_id || $community->isModerator($viewer)))
                <form method="POST" action="{{ route('discussions.destroy', [$community, $discussion]) }}" data-confirm="{{ __('Supprimer cette discussion ?') }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-icon btn-sm" aria-label="{{ __('Supprimer') }}"><x-icon name="trash"/></button></form>
            @endif
        </div>
        <h2 class="mt">{{ $discussion->title }}</h2>
        <div class="post-text" data-text>{!! render_text($discussion->body) !!}</div>
        @include('partials.actions', ['model' => $discussion, 'type' => 'discussion'])
    </div></article>
    @include('partials.comments', ['target' => $discussion, 'type' => 'discussion', 'comments' => $comments])
@endsection
