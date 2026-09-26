@php
    $viewer = auth()->user();
    $owner = $model->user;
    $isOwner = $viewer && $owner && $viewer->id === $owner->id;
@endphp
<div class="dropdown item-menu">
    <button type="button" class="btn btn-ghost btn-icon btn-sm" data-dropdown aria-haspopup="menu" aria-label="{{ __('Plus d\'options') }}"><x-icon name="more"/></button>
    <div class="menu" role="menu">
        <button type="button" data-copy="{{ $model->url() }}" role="menuitem"><x-icon name="link"/>{{ __('Copier le lien') }}</button>
        @if(in_array($type, ['post', 'video', 'live']))
            <button type="button" data-copy='<iframe src="{{ route('embed.'.$type, $model->id) }}" width="560" height="{{ $type === 'post' ? 360 : 315 }}" style="border:0" allowfullscreen loading="lazy"></iframe>' role="menuitem"><x-icon name="code"/>{{ __('Intégrer (code iframe)') }}</button>
        @endif
        @if(($model->body ?? $model->description ?? null))
            <button type="button" data-translate="{{ route('interact.translate', [$type, $model->id]) }}" data-translate-target="#{{ $type }}-{{ $model->id }} [data-text]" role="menuitem"><x-icon name="translate"/>{{ __('Traduire') }}</button>
        @endif
        @auth
            @if($isOwner)
                @if($type === 'post')<a href="{{ route('posts.edit', $model) }}" role="menuitem"><x-icon name="edit"/>{{ __('Modifier') }}</a>@endif
                @if($type === 'video')<a href="{{ route('videos.edit', $model) }}" role="menuitem"><x-icon name="edit"/>{{ __('Modifier') }}</a>@endif
                @if($type === 'event')<a href="{{ route('events.edit', $model) }}" role="menuitem"><x-icon name="edit"/>{{ __('Modifier') }}</a>@endif
            @endif
            @if($type === 'post' && $model->canBeDeletedBy($viewer))
                <form method="POST" action="{{ route('posts.destroy', $model) }}" data-confirm="{{ __('Supprimer cette Vwa ?') }}">@csrf @method('DELETE')
                    <button type="submit" class="danger" role="menuitem"><x-icon name="trash"/>{{ __('Supprimer') }}</button></form>
            @endif
            @unless($isOwner)
                @if($owner)
                    @if($viewer->isFollowing($owner))
                        <button type="button" data-action="{{ route('users.unfollow', $owner) }}" data-method="DELETE" data-reload role="menuitem"><x-icon name="user-x"/>{{ __('Ne plus suivre :name', ['name' => '@'.$owner->username]) }}</button>
                    @else
                        <button type="button" data-action="{{ route('users.follow', $owner) }}" data-reload role="menuitem"><x-icon name="user-plus"/>{{ __('Suivre :name', ['name' => '@'.$owner->username]) }}</button>
                    @endif
                @endif
                <button type="button" data-action="{{ route('interact.hide', [$type, $model->id]) }}" data-remove-closest="[data-item]" role="menuitem"><x-icon name="eye-off"/>{{ __('Masquer ce contenu') }}</button>
                @if($owner)
                    <button type="button" data-action="{{ route('users.mute', $owner) }}" data-remove-closest="[data-item]" role="menuitem"><x-icon name="volume-off"/>{{ __('Masquer :name', ['name' => '@'.$owner->username]) }}</button>
                    <button type="button" data-action="{{ route('users.block', $owner) }}" data-confirm="{{ __('Bloquer :name ? Vous ne verrez plus ses contenus.', ['name' => '@'.$owner->username]) }}" data-remove-closest="[data-item]" class="danger" role="menuitem"><x-icon name="ban"/>{{ __('Bloquer :name', ['name' => '@'.$owner->username]) }}</button>
                @endif
                <a href="{{ route('reports.create', [$type, $model->id]) }}" class="danger" role="menuitem"><x-icon name="flag"/>{{ __('Signaler') }}</a>
            @endunless
            @if($viewer->hasPermission('moderation.manage') && ! $isOwner)
                <hr>
                <form method="POST" action="{{ route('admin.content.action', [$type === 'video' ? 'videos' : \Illuminate\Support\Str::plural($type), $model->id, 'hide']) }}">@csrf
                    <button type="submit" role="menuitem"><x-icon name="shield"/>{{ __('Masquer (modération)') }}</button></form>
            @endif
        @endauth
    </div>
</div>
