@php $viewer = auth()->user(); @endphp
<article class="item" data-item="question:{{ $q->id }}">
    <div class="center" style="width:58px;flex-shrink:0">
        <button type="button" class="btn btn-outline btn-sm {{ $q->isSupportedBy($viewer) ? 'btn-soft' : '' }}" style="flex-direction:column;padding:.35rem .5rem;width:100%"
                data-action="{{ route('questions.support', $q) }}" data-support data-auth aria-label="{{ __('Soutenir cette question') }}" @if($q->status === 'closed') disabled @endif>
            <x-icon name="chevron-up"/><span data-count style="font-weight:800">{{ short_number($q->supports_count) }}</span>
        </button>
    </div>
    <div class="item-body">
        <a href="{{ $q->url() }}" style="color:var(--text);font-weight:700;font-size:1.02rem">{{ $q->title }}</a>
        <div class="row wrap small mt-sm" style="gap:.35rem">
            <span class="badge {{ $q->status === 'answered' ? 'badge-success' : ($q->status === 'closed' ? '' : 'badge-warning') }}">{{ $q->statusLabel() }}</span>
            @if($q->category)<span class="category-chip" style="--c:{{ $q->category->color }}">{{ $q->category->name }}</span>@endif
            @if($q->candidate)
                <span class="muted">→ <a href="{{ route('candidates.show', $q->candidate->username) }}">{{ $q->candidate->name }}</a></span>
            @else
                <span class="badge badge-info">{{ __('Question publique') }}</span>
            @endif
            <span class="faint">· {{ __('par') }} {{ $q->user->name }} · {{ $q->created_at->diffForHumans(null, true, true) }}</span>
            <span class="faint">· <x-icon name="comment" style="width:13px;height:13px;vertical-align:-2px"/> {{ $q->comments_count }}</span>
        </div>
    </div>
</article>
