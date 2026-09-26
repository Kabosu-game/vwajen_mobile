@php $viewer = auth()->user(); @endphp
<div class="comment" id="comment-{{ $comment->id }}" data-item="comment:{{ $comment->id }}">
    <a href="{{ $comment->user->profileUrl() }}" tabindex="-1" aria-hidden="true"><img src="{{ $comment->user->avatarUrl() }}" alt="" class="avatar avatar-sm" loading="lazy"></a>
    <div class="bubble">
        <div class="item-meta">
            <a href="{{ $comment->user->profileUrl() }}" class="name">{{ $comment->user->name }}</a>@include('partials.verified', ['u' => $comment->user])
            <span class="handle small">{{ '@'.$comment->user->username }}</span><span class="sep"></span>
            <span class="handle small">{{ $comment->created_at->diffForHumans(null, true, true) }}</span>
            @if($comment->edited_at)<span class="edited">· {{ __('modifié') }}</span>@endif
        </div>
        <div class="post-text" data-text style="margin:.15rem 0">{!! render_text($comment->body) !!}</div>
        <div class="comment-actions">
            <button type="button" class="action like {{ $comment->isLikedBy($viewer) ? 'active' : '' }}" data-action="{{ route('comments.like', $comment) }}" data-like data-auth aria-label="{{ __('J\'aime') }}">
                <x-icon name="heart"/><span data-count>{{ $comment->likes_count ?: '' }}</span></button>
            @auth
                @if(! $comment->parent_id)
                    <button type="button" class="action" data-reply="{{ $comment->id }}" data-reply-name="{{ $comment->user->username }}"><x-icon name="comment"/>{{ __('Répondre') }}</button>
                @endif
                <div class="dropdown">
                    <button type="button" class="action" data-dropdown aria-label="{{ __('Plus d\'options') }}"><x-icon name="more"/></button>
                    <div class="menu left">
                        <button type="button" data-translate="{{ route('interact.translate', ['comment', $comment->id]) }}" data-translate-target="#comment-{{ $comment->id }} [data-text]"><x-icon name="translate"/>{{ __('Traduire') }}</button>
                        @if($viewer->id === $comment->user_id)
                            <button type="button" data-edit-comment="{{ route('comments.update', $comment) }}"><x-icon name="edit"/>{{ __('Modifier') }}</button>
                        @endif
                        @if($viewer->id === $comment->user_id || $viewer->hasPermission('content.delete') || ($target->user_id ?? null) === $viewer->id)
                            <button type="button" class="danger" data-action="{{ route('comments.destroy', $comment) }}" data-method="DELETE" data-confirm="{{ __('Supprimer ce commentaire ?') }}" data-remove-closest=".comment"><x-icon name="trash"/>{{ __('Supprimer') }}</button>
                        @endif
                        @if($viewer->id !== $comment->user_id)
                            <a href="{{ route('reports.create', ['comment', $comment->id]) }}" class="danger"><x-icon name="flag"/>{{ __('Signaler') }}</a>
                        @endif
                    </div>
                </div>
            @endauth
        </div>
        @if(! $comment->parent_id)
            <div class="replies" data-replies="{{ $comment->id }}">
                @foreach($comment->replies as $reply)
                    @include('partials.comment', ['comment' => $reply])
                @endforeach
            </div>
        @endif
    </div>
</div>
