@php
    /** Barre d'actions : commentaire, repost, like, partage, enregistrement — pour tout contenu interactif. */
    $viewer = auth()->user();
    $liked = $model->isLikedBy($viewer);
    $bookmarked = $model->isBookmarkedBy($viewer);
    $reposted = $model->isRepostedBy($viewer);
    $canRepost = ($model->visibility ?? 'public') === 'public';
    $hasCounter = fn ($c) => array_key_exists($c, $model->getAttributes());
@endphp
<div class="actions" role="group" aria-label="{{ __('Actions') }}">
    <a href="{{ $model->url() }}#comments" class="action" aria-label="{{ __('Commenter') }} ({{ (int) ($model->comments_count ?? 0) }})">
        <x-icon name="comment"/><span>{{ $model->comments_count ? short_number($model->comments_count) : '' }}</span>
    </a>
    @if($hasCounter('reposts_count'))
        <div class="dropdown">
            <button type="button" class="action repost {{ $reposted ? 'active' : '' }}" @if($canRepost) data-dropdown @else disabled @endif
                    aria-label="{{ __('Repost') }} ({{ (int) $model->reposts_count }})" aria-pressed="{{ $reposted ? 'true' : 'false' }}">
                <x-icon name="repost"/><span data-count>{{ $model->reposts_count ? short_number($model->reposts_count) : '' }}</span>
            </button>
            @if($canRepost)
                <div class="menu left" role="menu">
                    <button type="button" data-action="{{ route('interact.repost', [$type, $model->id]) }}" data-toggle-class="active" data-target-closest=".dropdown" data-auth role="menuitem">
                        <x-icon name="repost"/>{{ $reposted ? __('Annuler le repost') : __('Reposter') }}</button>
                    @if($type === 'post')
                        <button type="button" data-quote="{{ $model->id }}" data-quote-text="{{ \Illuminate\Support\Str::limit($model->body, 200) }}" data-quote-author="{{ $model->user->name }}" data-auth role="menuitem">
                            <x-icon name="edit"/>{{ __('Citer') }}</button>
                    @endif
                </div>
            @endif
        </div>
    @endif
    <button type="button" class="action like {{ $liked ? 'active' : '' }}" data-action="{{ route('interact.like', [$type, $model->id]) }}" data-like data-auth
            aria-label="{{ __('J\'aime') }} ({{ (int) ($model->likes_count ?? 0) }})" aria-pressed="{{ $liked ? 'true' : 'false' }}">
        <x-icon name="heart"/><span data-count>{{ ($model->likes_count ?? 0) ? short_number($model->likes_count) : '' }}</span>
    </button>
    @if($hasCounter('views_count') && $type !== 'post')
        <span class="action" title="{{ __('Vues') }}"><x-icon name="eye"/><span>{{ short_number($model->views_count) }}</span></span>
    @endif
    <div class="row" style="gap:0">
        @if($hasCounter('bookmarks_count') || in_array($type, ['post', 'video', 'question', 'event', 'debate', 'live']))
            <button type="button" class="action bookmark {{ $bookmarked ? 'active' : '' }}" data-action="{{ route('interact.bookmark', [$type, $model->id]) }}" data-toggle-class="active" data-auth
                    aria-label="{{ __('Enregistrer') }}" aria-pressed="{{ $bookmarked ? 'true' : 'false' }}"><x-icon name="bookmark"/></button>
        @endif
        <button type="button" class="action" data-share data-share-url="{{ $model->url() }}" data-share-title="{{ $model->shareTitle() }}"
                data-share-type="{{ $type }}" data-share-id="{{ $model->id }}" aria-label="{{ __('Partager') }}"><x-icon name="share"/></button>
    </div>
</div>
