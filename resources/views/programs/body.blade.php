@php
    $viewer = auth()->user();
    $selected = $selectedCategory ?? request('category');
    $proposals = $version?->proposals ?? collect();
    $byCat = $proposals->when($selected, fn ($c) => $c->filter(fn ($p) => $p->category?->slug === $selected))->groupBy('category_id');
    $canAddSource = $viewer && ($viewer->id === $program->user_id || $viewer->hasPermission('sources.verify'));
@endphp
<div class="card">
    <div class="card-body">
        <div class="row between wrap">
            <div>
                <h2 class="mb-0">{{ $version->title ?? $program->title }}</h2>
                <div class="small muted">{{ __('Version :v', ['v' => $version?->version_number]) }}
                    @if($version?->published_at) · {{ __('publiée le :date', ['date' => $version->published_at->translatedFormat('d F Y')]) }}@endif
                    @if($program->status === 'archived') · <span class="badge">{{ __('Archivé') }}</span>@endif</div>
            </div>
            <div class="row">
                <button type="button" class="btn btn-outline btn-sm" data-share data-share-url="{{ $program->url() }}" data-share-title="{{ $program->title }}" data-share-type="program" data-share-id="{{ $program->id }}"><x-icon name="share"/>{{ __('Partager') }}</button>
                @if($program->canManage($viewer))<a href="{{ route('programs.edit', $program) }}" class="btn btn-soft btn-sm"><x-icon name="edit"/>{{ __('Gérer') }}</a>@endif
            </div>
        </div>
        @if($version?->summary)<div class="prose mt">{!! nl2br(e($version->summary)) !!}</div>@endif
        @if($version?->changelog)<div class="alert alert-info mt"><x-icon name="history"/><div class="small"><b>{{ __('Changements de cette version') }} :</b> {{ $version->changelog }}</div></div>@endif
    </div>
    <div class="card-foot">
        <div class="chip-row" role="navigation" aria-label="{{ __('Filtrer par thème') }}">
            <a href="?{{ http_build_query(array_filter(['tab' => request('tab')])) }}" class="pill {{ ! $selected ? 'active' : '' }}">{{ __('Tous les thèmes') }}</a>
            @foreach($categories as $cat)
                @php $n = $proposals->where('category_id', $cat->id)->count(); @endphp
                <a href="?{{ http_build_query(array_filter(['tab' => request('tab'), 'category' => $cat->slug])) }}" class="pill {{ $selected === $cat->slug ? 'active' : '' }}" @if(! $n) style="opacity:.55" @endif>{{ $cat->name }} ({{ $n }})</a>
            @endforeach
        </div>
    </div>
</div>

@forelse($categories as $cat)
    @continue($selected && $selected !== $cat->slug)
    @php $items = $byCat->get($cat->id, collect()); @endphp
    @if($items->isNotEmpty() || $selected)
        <section class="card" aria-labelledby="cat-{{ $cat->slug }}">
            <div class="card-head"><h3 id="cat-{{ $cat->slug }}"><span class="category-chip" style="--c:{{ $cat->color }}">{{ $cat->name }}</span></h3><span class="small faint">{{ trans_choice(':n proposition|:n propositions', $items->count(), ['n' => $items->count()]) }}</span></div>
            <div class="card-body">
                @forelse($items as $p)
                    <article class="proposal" id="proposal-{{ $p->id }}" style="--c:{{ $cat->color }}">
                        <h4>{{ $p->title }}</h4>
                        <div class="prose small">{!! nl2br(e($p->description)) !!}</div>
                        <div class="row wrap small muted mt-sm" style="gap:.8rem">
                            @if($p->timeline)<span><x-icon name="clock" style="width:14px;height:14px;vertical-align:-2px"/> {{ $p->timeline }}</span>@endif
                            @if($p->budget)<span><x-icon name="chart" style="width:14px;height:14px;vertical-align:-2px"/> {{ $p->budget }}</span>@endif
                        </div>
                        @include('partials.sources', ['sources' => $p->sources, 'addType' => 'proposal', 'addId' => $p->id, 'canAdd' => $canAddSource])
                        @if($p->sources->isEmpty())<div class="missing mt-sm"><x-icon name="alert"/>{{ __('Aucune source fournie pour cette proposition.') }}</div>@endif
                    </article>
                @empty
                    <div class="missing"><x-icon name="alert"/>{{ __('Information manquante : aucune proposition sur ce thème.') }}</div>
                @endforelse
            </div>
        </section>
    @endif
@empty
@endforelse

@if($program->documents->isNotEmpty())
    <div class="card"><div class="card-head"><h3>{{ __('Documents') }}</h3></div>
        @foreach($program->documents as $doc)
            <a href="{{ $doc->url() }}" class="item" style="color:var(--text)" target="_blank" rel="noopener"><x-icon name="file"/>
                <div class="item-body"><strong>{{ $doc->title }}</strong><div class="small faint">{{ $doc->published_on?->translatedFormat('d M Y') }} · {{ $doc->sizeLabel() }}</div></div><x-icon name="download"/></a>
        @endforeach
    </div>
@endif

<div class="card"><div class="card-head"><h3>{{ __('Sources du programme') }}</h3></div><div class="card-body">
    @include('partials.sources', ['sources' => $program->sources, 'addType' => 'program', 'addId' => $program->id, 'canAdd' => $canAddSource])
    @if($program->sources->isEmpty())<p class="small muted">{{ __('Aucune source générale.') }}</p>@endif
</div></div>
