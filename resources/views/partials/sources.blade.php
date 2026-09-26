@php
    /** Liste de sources ($sources) + formulaire d'ajout (si $addType/$addId et autorisé). */
    $viewer = auth()->user();
@endphp
@foreach($sources as $s)
    <div class="source">
        <x-icon name="{{ $s->status === 'verified' ? 'shield-check' : 'link' }}"/>
        <div class="grow">
            <div>
                @if($s->url)<a href="{{ $s->url }}" target="_blank" rel="noopener nofollow">{{ $s->name }}</a>@else<strong>{{ $s->name }}</strong>@endif
                @if($s->published_on)<span class="faint small"> · {{ $s->published_on->translatedFormat('d M Y') }}</span>@endif
            </div>
            <div class="row wrap mt-sm" style="gap:.3rem">
                <span class="badge badge-{{ $s->statusColor() }}">{{ $s->statusLabel() }}</span>
                @if($s->provided_by_candidate)<span class="badge">{{ __('Fournie par le candidat') }}</span>@endif
                <a href="{{ route('sources.show', $s) }}" class="small">{{ __('Historique') }}</a>
            </div>
        </div>
        @if($viewer && (($s->user_id === $viewer->id && $s->status !== 'verified') || $viewer->hasPermission('sources.verify')))
            <form method="POST" action="{{ route('sources.destroy', $s) }}" data-confirm="{{ __('Supprimer cette source ?') }}">@csrf @method('DELETE')
                <button class="btn btn-ghost btn-icon btn-sm" aria-label="{{ __('Supprimer') }}"><x-icon name="trash"/></button></form>
        @endif
    </div>
@endforeach
@if(isset($addType) && ($canAdd ?? false))
    <details class="mt-sm">
        <summary class="small" style="cursor:pointer;color:var(--primary)">+ {{ __('Ajouter une source') }}</summary>
        <form method="POST" action="{{ route('sources.store', [$addType, $addId]) }}" class="panel mt-sm">@csrf
            <div class="grid grid-2">
                <div class="field"><label class="label">{{ __('Nom de la source') }}</label><input name="name" class="input input-sm" required maxlength="200"></div>
                <div class="field"><label class="label">{{ __('Lien') }}</label><input name="url" type="url" class="input input-sm" placeholder="https://"></div>
                <div class="field"><label class="label">{{ __('Date de publication') }}</label><input name="published_on" type="date" class="input input-sm"></div>
                <div class="field"><label class="label">{{ __('Note') }}</label><input name="note" class="input input-sm" maxlength="1000"></div>
            </div>
            <button class="btn btn-primary btn-sm">{{ __('Ajouter') }}</button>
        </form>
    </details>
@endif
