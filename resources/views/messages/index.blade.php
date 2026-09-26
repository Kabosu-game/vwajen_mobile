@extends('layouts.app')
@section('title', $active ? $active->titleFor(auth()->user()) : __('Messages'))
@section('layout', 'full')
@php $me = auth()->user(); @endphp
@section('content')
    <div class="messenger {{ $active ? 'has-active' : '' }}">
        <aside class="conv-list" aria-label="{{ __('Conversations') }}">
            <div class="thread-head" style="justify-content:space-between"><h1 style="font-size:1.2rem;margin:0">{{ __('Messages') }}</h1>
                <div class="row"><a href="{{ route('messages.search') }}" class="btn btn-ghost btn-icon btn-sm" aria-label="{{ __('Rechercher dans les conversations') }}"><x-icon name="search"/></a>
                    <a href="{{ route('messages.create') }}" class="btn btn-primary btn-icon btn-sm" aria-label="{{ __('Nouvelle conversation') }}"><x-icon name="edit"/></a></div></div>
            <form method="GET" action="{{ route('messages.index') }}" class="p" style="padding:.6rem"><input type="search" name="q" value="{{ request('q') }}" class="input input-sm" placeholder="{{ __('Filtrer les conversations') }}" aria-label="{{ __('Filtrer les conversations') }}"></form>
            @forelse($conversations as $c)
                @php $p = $c->participants->firstWhere('user_id', $me->id); $unread = $p?->unreadCount() ?? 0; @endphp
                <a href="{{ route('messages.show', $c) }}" class="conv {{ $active?->id === $c->id ? 'active' : '' }}">
                    <img src="{{ $c->avatarFor($me) }}" alt="" class="avatar">
                    <div class="grow" style="min-width:0">
                        <div class="row between"><strong class="truncate">{{ $c->titleFor($me) }}</strong><span class="small faint">{{ $c->last_message_at?->diffForHumans(null, true, true) }}</span></div>
                        <div class="small {{ $unread ? '' : 'faint' }} truncate">{{ $c->latestMessage ? ($c->latestMessage->user_id === $me->id ? __('Vous') . ' : ' : '').($c->latestMessage->body ?: ($c->latestMessage->media_type ? '📎' : '↗')) : __('Nouvelle conversation') }}</div>
                    </div>
                    @if($unread)<span class="unread">{{ $unread }}</span>@endif
                </a>
            @empty
                <div class="empty"><x-icon name="mail"/><p>{{ __('Aucune conversation.') }}</p><a href="{{ route('messages.create') }}" class="btn btn-primary btn-sm">{{ __('Écrire un message') }}</a></div>
            @endforelse
        </aside>

        <section class="thread" aria-label="{{ __('Conversation') }}">
            @if($active)
                <div class="thread-head">
                    <a href="{{ route('messages.index') }}" class="btn btn-ghost btn-icon btn-sm" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a>
                    <img src="{{ $active->avatarFor($me) }}" alt="" class="avatar avatar-sm">
                    <div class="grow" style="min-width:0">
                        <strong class="truncate" style="display:block">{{ $active->titleFor($me) }}</strong>
                        <div class="small faint truncate">@if($active->isGroup()){{ trans_choice(':n participant|:n participants', $active->users->whereNull('pivot.left_at')->count(), ['n' => $active->users->whereNull('pivot.left_at')->count()]) }}@elseif($other){{ '@'.$other->username }}@endif</div>
                    </div>
                    <form method="GET" class="row"><input type="search" name="q" value="{{ request('q') }}" class="input input-sm" style="width:140px" placeholder="{{ __('Rechercher') }}" aria-label="{{ __('Rechercher dans la conversation') }}"></form>
                    <div class="dropdown"><button class="btn btn-ghost btn-icon btn-sm" data-dropdown aria-label="{{ __('Options') }}"><x-icon name="more-v"/></button>
                        <div class="menu">
                            @if($other)<a href="{{ $other->profileUrl() }}"><x-icon name="user"/>{{ __('Voir le profil') }}</a>
                                @if($me->hasBlocked($other))<button data-action="{{ route('users.unblock', $other) }}" data-method="DELETE" data-reload><x-icon name="unlock"/>{{ __('Débloquer') }}</button>
                                @else<button data-action="{{ route('users.block', $other) }}" data-confirm="{{ __('Bloquer cet utilisateur ?') }}" data-reload class="danger"><x-icon name="ban"/>{{ __('Bloquer') }}</button>@endif
                                <a href="{{ route('reports.create', ['user', $other->id]) }}" class="danger"><x-icon name="flag"/>{{ __('Signaler') }}</a>@endif
                            @if($active->isGroup())
                                @if($participant->is_admin)
                                    <form method="POST" action="{{ route('messages.rename', $active) }}" style="padding:.4rem .6rem">@csrf<input name="name" class="input input-sm" placeholder="{{ __('Nom du groupe') }}" value="{{ $active->name }}" aria-label="{{ __('Nom du groupe') }}"></form>
                                    <form method="POST" action="{{ route('messages.participants', $active) }}" style="padding:.4rem .6rem">@csrf<input name="usernames[]" class="input input-sm" placeholder="{{ __('Ajouter : username') }}" aria-label="{{ __('Ajouter un participant') }}"></form>
                                @endif
                                <form method="POST" action="{{ route('messages.leave', $active) }}" data-confirm="{{ __('Quitter ce groupe ?') }}">@csrf<button class="danger"><x-icon name="logout"/>{{ __('Quitter le groupe') }}</button></form>
                            @endif
                            <form method="POST" action="{{ route('messages.destroy', $active) }}" data-confirm="{{ __('Supprimer cette conversation de votre liste ?') }}">@csrf @method('DELETE')<button class="danger"><x-icon name="trash"/>{{ __('Supprimer la conversation') }}</button></form>
                        </div></div>
                </div>
                <div class="thread-body" id="thread" data-poll="{{ route('messages.poll', $active) }}" aria-live="polite">
                    @foreach($messages as $m) @include('messages.bubble', ['m' => $m, 'me' => $me, 'isGroup' => $active->isGroup()]) @endforeach
                    @if($messages->isEmpty())<p class="center faint small">{{ __('Dites bonjour 👋') }}</p>@endif
                </div>
                @if($blocked)
                    <div class="thread-form center small muted">{{ __('Vous ne pouvez pas échanger avec cet utilisateur.') }}</div>
                @else
                    <form class="thread-form" method="POST" action="{{ route('messages.send', $active) }}" enctype="multipart/form-data" id="send-form">
                        @csrf
                        <label class="btn btn-ghost btn-icon" title="{{ __('Envoyer une image ou une vidéo') }}"><x-icon name="image"/><span class="sr-only">{{ __('Joindre un fichier') }}</span>
                            <input type="file" name="media" accept="image/*,video/*" hidden data-media></label>
                        <label for="msg-body" class="sr-only">{{ __('Message') }}</label>
                        <textarea id="msg-body" name="body" class="textarea grow" rows="1" maxlength="{{ config('vwajen.limits.message_length') }}" placeholder="{{ __('Écrire un message…') }}" data-autosize></textarea>
                        <button class="btn btn-primary btn-icon" aria-label="{{ __('Envoyer') }}"><x-icon name="send"/></button>
                    </form>
                    <div class="small faint" data-media-name style="padding:0 1rem .4rem"></div>
                @endif
            @else
                <div class="empty" style="margin:auto"><x-icon name="message"/><h3>{{ __('Vos messages') }}</h3><p>{{ __('Conversations individuelles et de groupe, textes, images et vidéos.') }}</p><a href="{{ route('messages.create') }}" class="btn btn-primary">{{ __('Nouvelle conversation') }}</a></div>
            @endif
        </section>
    </div>
@endsection
@push('scripts')
<script>
(function () {
    const thread = document.getElementById('thread'); if (!thread) return;
    const V = window.Vwajen, form = document.getElementById('send-form');
    const last = () => { const b = thread.querySelectorAll('[data-mid]'); return b.length ? b[b.length - 1].dataset.mid : 0; };
    const bottom = () => (thread.scrollTop = thread.scrollHeight);
    bottom();
    async function poll() {
        if (document.hidden) return;
        try { const r = await V.api(thread.dataset.poll + '?after=' + last(), { method: 'GET' });
            if (r.html) { const near = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 120; thread.insertAdjacentHTML('beforeend', r.html); V.enhance(thread); if (near) bottom(); } } catch (e) {}
    }
    setInterval(poll, V.dataSaver ? 8000 : 3000);
    if (form) {
        const media = form.querySelector('[data-media]'), label = document.querySelector('[data-media-name]');
        media.addEventListener('change', () => label.textContent = media.files[0] ? '📎 ' + media.files[0].name : '');
        form.querySelector('textarea').addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(form); if (!fd.get('body') && !media.files.length) return;
            const btn = form.querySelector('button:not([type=button])'); btn.disabled = true;
            try { const r = await V.api(form.action, { body: fd }); if (!thread.querySelector('#m' + r.id)) thread.insertAdjacentHTML('beforeend', r.html); form.reset(); label.textContent = ''; V.enhance(thread); bottom(); }
            catch (err) { V.toast(err.message, 'error'); } finally { btn.disabled = false; }
        });
    }
})();
</script>
@endpush
