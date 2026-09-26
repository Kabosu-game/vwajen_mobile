@php $mine = $m->user_id === $me->id; @endphp
<div class="bubble-row {{ $mine ? 'me' : '' }}" id="m{{ $m->id }}" data-mid="{{ $m->id }}">
    @unless($mine)<img src="{{ $m->user->avatarUrl() }}" alt="" class="avatar avatar-xs" title="{{ $m->user->name }}">@endunless
    <div>
        <div class="msg">
            @if($m->trashed())<em class="faint">{{ __('Message supprimé') }}</em>
            @else
                @if($m->media_type === 'image')<img src="{{ $m->mediaUrl() }}" alt="" data-lightbox loading="lazy">@endif
                @if($m->media_type === 'video')<video src="{{ $m->mediaUrl() }}" controls playsinline preload="metadata"></video>@endif
                @if($m->shared)
                    <a href="{{ $m->shared->url() }}" class="quote" style="background:var(--surface);color:var(--text)">
                        <div class="small faint">{{ \App\Support\Morph::label($m->shared_type) }}</div>
                        <div class="clamp-2">{{ $m->shared->title ?? $m->shared->name ?? \Illuminate\Support\Str::limit($m->shared->body ?? '', 120) }}</div></a>
                @endif
                @if($m->body)<div>{!! render_text($m->body) !!}</div>@endif
            @endif
        </div>
        <div class="msg-time" style="text-align:{{ $mine ? 'right' : 'left' }}">
            @if(! $mine && ($isGroup ?? false)){{ $m->user->name }} · @endif{{ $m->created_at->format('H:i') }}
            @if($mine && ! $m->trashed())· <button type="button" class="btn btn-ghost" style="padding:0;font-size:.7rem" data-action="{{ route('messages.delete-message', $m) }}" data-method="DELETE" data-confirm="{{ __('Supprimer ce message ?') }}" data-remove-closest=".bubble-row">{{ __('Supprimer') }}</button>@endif
            @unless($mine)· <a href="{{ route('reports.create', ['message', $m->id]) }}" class="faint">{{ __('Signaler') }}</a>@endunless
        </div>
    </div>
</div>
