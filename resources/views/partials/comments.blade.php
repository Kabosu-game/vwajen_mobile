@php
    /** Section de commentaires pour un contenu. $target, $type, $comments */
    $viewer = auth()->user();
    $closed = ($target->allow_comments ?? true) === false || ($target->is_locked ?? false);
@endphp
<section class="card" id="comments" aria-labelledby="comments-title">
    <div class="card-head">
        <h2 id="comments-title">{{ __('Commentaires') }} <span class="faint">({{ (int) ($target->comments_count ?? $comments->total()) }})</span></h2>
    </div>
    @auth
        @if($closed)
            <div class="p muted small">{{ __('Les commentaires sont fermés.') }}</div>
        @else
            <form method="POST" action="{{ route('comments.store', [$type, $target->id]) }}" class="comment-form" data-comment-form>
                @csrf
                <input type="hidden" name="parent_id" value="">
                <img src="{{ $viewer->avatarUrl() }}" alt="" class="avatar avatar-sm">
                <div class="grow">
                    <div class="small faint" data-reply-label hidden></div>
                    <label for="comment-body-{{ $type }}-{{ $target->id }}" class="sr-only">{{ __('Votre commentaire') }}</label>
                    <textarea id="comment-body-{{ $type }}-{{ $target->id }}" name="body" class="textarea" rows="1" required maxlength="{{ config('vwajen.limits.comment_length') }}"
                              placeholder="{{ __('Ajouter un commentaire…') }}" data-autosize data-mention></textarea>
                </div>
                <button class="btn btn-primary btn-sm">{{ __('Répondre') }}</button>
            </form>
        @endif
    @else
        <div class="p small"><a href="{{ route('login') }}">{{ __('Connectez-vous') }}</a> {{ __('pour commenter.') }}</div>
    @endauth
    <div class="comments" data-comment-list>
        @forelse($comments as $comment)
            @include('partials.comment', ['comment' => $comment])
        @empty
            <p class="muted small p" data-empty>{{ __('Aucun commentaire pour le moment. Soyez le premier !') }}</p>
        @endforelse
    </div>
    {{ $comments->fragment('comments')->links() }}
</section>
