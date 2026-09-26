@forelse($items as $it)
    @php $m = $it['model']; @endphp
    @if($m instanceof \App\Models\Post)
        @include('partials.post-card', ['post' => $m, 'reposter' => $it['reposter']])
    @else
        @include('partials.content-card', ['model' => $m, 'reposter' => $it['reposter']])
    @endif
@empty
    @if(($items->currentPage() ?? 1) === 1)
        <div class="empty">
            <x-icon name="sparkles"/>
            <h3>{{ __('Rien à afficher pour le moment') }}</h3>
            <p>{{ __('Suivez des personnes, des candidats ou des hashtags pour personnaliser votre fil.') }}</p>
            <a href="{{ route('discover.index') }}" class="btn btn-primary">{{ __('Découvrir') }}</a>
        </div>
    @endif
@endforelse
@if($items->hasMorePages())
    <div class="load-more" data-infinite="{{ $items->nextPageUrl() }}{{ str_contains($items->nextPageUrl(), '?') ? '&' : '?' }}partial=1">
        <a href="{{ $items->nextPageUrl() }}" class="btn btn-outline">{{ __('Charger plus') }}</a>
    </div>
@endif
