@extends('layouts.app')
@section('title', $live->title)
@section('layout', 'wide')
@section('og_image', $live->coverUrl() ?? asset('images/logo-400.jpg'))
@section('content')
    <div class="page-head"><a href="{{ $live->isAudio() ? route('spaces.index') : route('lives.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a>
        <h1 class="truncate">{{ $live->title }}</h1>
        <div class="actions">
            @if($live->status === 'scheduled')
                <button type="button" class="btn {{ $reminded ? 'btn-soft' : 'btn-outline' }} btn-sm" data-action="{{ route('lives.remind', $live) }}" data-auth data-reload><x-icon name="bell"/>{{ $reminded ? __('Rappel programmé') : __('Me rappeler') }}</button>
            @endif
            @if(auth()->id() === $live->user_id)
                <a href="{{ route('lives.edit', $live) }}" class="btn btn-ghost btn-sm"><x-icon name="edit"/>{{ __('Modifier') }}</a>
            @endif
        </div>
    </div>

    @if($banned)
        <div class="card empty"><x-icon name="ban"/><h3>{{ __('Vous avez été bloqué de ce live.') }}</h3></div>
    @else
        @if($invitation)
            <div class="alert alert-info"><x-icon name="user-plus"/><div class="grow">{{ __(':name vous invite à rejoindre ce live comme :role.', ['name' => $live->user->name, 'role' => $invitation->role === 'moderator' ? __('modérateur') : __('invité')]) }}</div>
                <form method="POST" action="{{ route('lives.respond', $live) }}">@csrf<input type="hidden" name="answer" value="accept"><button class="btn btn-primary btn-sm">{{ __('Accepter') }}</button></form>
                <form method="POST" action="{{ route('lives.respond', $live) }}">@csrf<input type="hidden" name="answer" value="decline"><button class="btn btn-ghost btn-sm">{{ __('Refuser') }}</button></form></div>
        @endif

        @include('lives.stage')

        <div class="split-layout" style="margin-top:1rem">
            <div class="card" id="live-{{ $live->id }}" data-item="live:{{ $live->id }}"><div class="card-body">
                <div class="row">
                    <img src="{{ $live->user->avatarUrl() }}" alt="" class="avatar">
                    <div class="grow"><a href="{{ $live->user->profileUrl() }}" class="name">{{ $live->user->name }}</a>@include('partials.verified', ['u' => $live->user])
                        <div class="small faint">{{ $live->isAudio() ? 'Audio Space' : __('Live vidéo') }} · {{ ($live->started_at ?? $live->scheduled_at ?? $live->created_at)->translatedFormat('d M Y, H:i') }}
                            · {{ __('pic : :n spectateurs', ['n' => $live->peak_viewers]) }}</div></div>
                    @include('partials.follow-button', ['u' => $live->user])
                    @include('partials.item-menu', ['model' => $live, 'type' => 'live'])
                </div>
                @if($live->description)<div class="post-text mt" data-text>{!! render_text($live->description) !!}</div>@endif
                @include('partials.actions', ['model' => $live, 'type' => 'live'])
            </div></div>

            <div>
                @php $guests = $live->participants->where('status', 'accepted'); @endphp
                @if($guests->isNotEmpty() || auth()->id() === $live->user_id)
                    <div class="card"><div class="card-head"><h3>{{ __('Participants') }}</h3></div><div class="card-body">
                        @foreach($guests as $p)
                            <div class="row mb" style="margin-bottom:.4rem"><img src="{{ $p->user->avatarUrl() }}" class="avatar avatar-sm" alt=""><span class="grow">{{ $p->user->name }}</span><span class="badge">{{ $p->role === 'moderator' ? __('Modérateur') : __('Invité') }}</span></div>
                        @endforeach
                        @if(auth()->id() === $live->user_id)
                            <form method="POST" action="{{ route('lives.invite', $live) }}" class="mt-sm">@csrf
                                <label class="label" for="invite-user">{{ __('Inviter des participants') }}</label>
                                <input id="invite-user" name="username" class="input input-sm mb" placeholder="@username" required style="margin-bottom:.4rem">
                                <div class="row"><select name="role" class="select input-sm"><option value="guest">{{ __('Invité (à l\'écran)') }}</option><option value="moderator">{{ __('Modérateur du chat') }}</option></select>
                                    <button class="btn btn-soft btn-sm">{{ __('Inviter') }}</button></div>
                            </form>
                        @endif
                    </div></div>
                @endif
                @if(auth()->id() === $live->user_id && $live->status === 'scheduled')
                    <form method="POST" action="{{ route('lives.cancel', $live) }}" data-confirm="{{ __('Annuler ce live ?') }}">@csrf<button class="btn btn-danger-outline btn-block">{{ __('Annuler le live') }}</button></form>
                @endif
                @if(auth()->id() === $live->user_id && $live->status === 'ended' && ! $live->replay_video_id)
                    <form method="POST" action="{{ route('lives.replay', $live) }}" enctype="multipart/form-data" class="card"><div class="card-body">@csrf
                        <label class="label">{{ __('Publier un replay (fichier)') }}</label><input type="file" name="file" accept="video/*,audio/*" class="input" required>
                        <button class="btn btn-primary btn-sm mt-sm">{{ __('Publier') }}</button></div></form>
                @endif
            </div>
        </div>
        @include('partials.comments', ['target' => $live, 'type' => 'live', 'comments' => $live->rootComments()->where('is_hidden', false)->with(['user', 'replies.user'])->latest()->paginate(20)])
    @endif
@endsection
