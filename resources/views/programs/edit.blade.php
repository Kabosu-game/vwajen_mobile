@extends('layouts.app')
@section('title', __('Gérer le programme'))
@section('layout', 'wide')
@php $editable = ! $version->published_at; @endphp
@section('content')
    <div class="page-head"><a href="{{ route('candidate.dashboard', 'program') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a>
        <div><h1>{{ $program->title }}</h1><div class="small faint">{{ __('Version :v', ['v' => $version->version_number]) }} · {{ $editable ? __('brouillon') : __('publiée') }}</div></div>
        <div class="actions">
            <a href="{{ $program->url() }}" class="btn btn-outline btn-sm"><x-icon name="eye"/>{{ __('Aperçu') }}</a>
            @if($editable)
                <form method="POST" action="{{ route('programs.publish', $program) }}" data-confirm="{{ __('Publier cette version ? Elle deviendra la version officielle.') }}">@csrf<button class="btn btn-success btn-sm"><x-icon name="check"/>{{ __('Publier la version') }}</button></form>
            @else
                <form method="POST" action="{{ route('programs.versions.store', $program) }}">@csrf<button class="btn btn-primary btn-sm"><x-icon name="plus"/>{{ __('Créer une nouvelle version') }}</button></form>
            @endif
            @if($program->status === 'published')
                <form method="POST" action="{{ route('programs.archive', $program) }}" data-confirm="{{ __('Archiver ce programme ?') }}">@csrf<button class="btn btn-outline btn-sm"><x-icon name="archive"/>{{ __('Archiver') }}</button></form>
            @elseif($program->status === 'archived')
                <form method="POST" action="{{ route('programs.unarchive', $program) }}">@csrf<button class="btn btn-outline btn-sm">{{ __('Réactiver') }}</button></form>
            @endif
        </div>
    </div>

    @unless($editable)
        <div class="alert alert-info"><x-icon name="lock"/><div>{{ __('Cette version est publiée et ne peut plus être modifiée (transparence). Créez une nouvelle version pour apporter des changements : l\'historique des versions reste public.') }}</div></div>
    @endunless

    <div class="split-layout wide-left">
        <div>
            <form method="POST" action="{{ route('programs.update', $program) }}" class="card"><div class="card-body">
                @csrf @method('PUT')
                <fieldset @disabled(! $editable)>
                    <legend>{{ __('Informations générales') }}</legend>
                    <div class="field"><label class="label" for="p-title">{{ __('Titre') }}</label><input id="p-title" name="title" class="input" required maxlength="200" value="{{ old('title', $version->title) }}"></div>
                    <div class="field"><label class="label" for="p-summary">{{ __('Résumé') }}</label><textarea id="p-summary" name="summary" class="textarea" rows="5">{{ old('summary', $version->summary) }}</textarea></div>
                    <div class="field"><label class="label" for="p-changelog">{{ __('Notes de version (ce qui change)') }}</label><input id="p-changelog" name="changelog" class="input" maxlength="2000" value="{{ old('changelog', $version->changelog) }}"></div>
                    <button class="btn btn-primary">{{ __('Enregistrer') }}</button>
                </fieldset>
            </div></form>

            <div class="section-title"><h2>{{ __('Propositions') }} ({{ $version->proposals->count() }})</h2></div>
            @foreach($categories as $cat)
                @php $items = $version->proposals->where('category_id', $cat->id); @endphp
                @if($items->isNotEmpty())
                    <div class="card"><div class="card-head"><span class="category-chip" style="--c:{{ $cat->color }}">{{ $cat->name }}</span></div><div class="card-body">
                        @foreach($items as $p)
                            <details class="proposal" style="--c:{{ $cat->color }}">
                                <summary style="cursor:pointer"><strong>{{ $p->title }}</strong> <span class="small faint">· {{ trans_choice(':n source|:n sources', $p->sources->count(), ['n' => $p->sources->count()]) }}</span></summary>
                                @if($editable)
                                    <form method="POST" action="{{ route('proposals.update', $p) }}" class="mt-sm">@csrf @method('PUT')
                                        @include('programs.proposal-fields', ['p' => $p])
                                        <div class="row"><button class="btn btn-primary btn-sm">{{ __('Enregistrer') }}</button></div>
                                    </form>
                                    <form method="POST" action="{{ route('proposals.destroy', $p) }}" data-confirm="{{ __('Supprimer cette proposition ?') }}" class="mt-sm">@csrf @method('DELETE')
                                        <button class="btn btn-danger-outline btn-sm"><x-icon name="trash"/>{{ __('Supprimer') }}</button></form>
                                @else
                                    <div class="small prose mt-sm">{!! nl2br(e($p->description)) !!}</div>
                                @endif
                                @include('partials.sources', ['sources' => $p->sources])
                            </details>
                        @endforeach
                    </div></div>
                @endif
            @endforeach

            @if($editable)
                <form method="POST" action="{{ route('proposals.store', $program) }}" class="card"><div class="card-head"><h3>{{ __('Ajouter une proposition') }}</h3></div><div class="card-body">
                    @csrf
                    @include('programs.proposal-fields', ['p' => null])
                    <button class="btn btn-primary"><x-icon name="plus"/>{{ __('Ajouter') }}</button>
                </div></form>
            @endif
        </div>

        <div>
            <div class="card"><div class="card-head"><h3>{{ __('Documents') }}</h3></div><div class="card-body">
                @foreach($program->documents as $doc)
                    <div class="row mb" style="margin-bottom:.5rem"><x-icon name="file"/><a href="{{ $doc->url() }}" class="grow truncate" target="_blank">{{ $doc->title }}</a>
                        <form method="POST" action="{{ route('documents.destroy', $doc) }}" data-confirm="{{ __('Supprimer ce document ?') }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-icon btn-sm" aria-label="{{ __('Supprimer') }}"><x-icon name="trash"/></button></form></div>
                @endforeach
                <form method="POST" action="{{ route('programs.documents.store', $program) }}" enctype="multipart/form-data" class="mt">@csrf
                    <input name="title" class="input input-sm mb" placeholder="{{ __('Titre du document') }}" required style="margin-bottom:.4rem">
                    <input type="date" name="published_on" class="input input-sm" style="margin-bottom:.4rem" aria-label="{{ __('Date de publication') }}">
                    <input type="file" name="file" class="input input-sm" required accept=".pdf,.doc,.docx,.odt,image/*" style="margin-bottom:.4rem">
                    <button class="btn btn-soft btn-sm btn-block"><x-icon name="upload"/>{{ __('Ajouter le document') }}</button>
                </form>
            </div></div>
            <div class="card"><div class="card-head"><h3>{{ __('Sources générales') }}</h3></div><div class="card-body">
                @include('partials.sources', ['sources' => $program->sources, 'addType' => 'program', 'addId' => $program->id, 'canAdd' => true])
            </div></div>
            <div class="card"><div class="card-head"><h3>{{ __('Versions') }}</h3></div><div class="card-body version-list">
                @foreach($program->versions as $v)
                    <a href="{{ route('programs.version', [$program, $v]) }}"><span>v{{ $v->version_number }} @unless($v->published_at)<span class="badge badge-warning">{{ __('brouillon') }}</span>@endunless</span><span class="small faint">{{ $v->published_at?->format('d/m/Y') }}</span></a>
                @endforeach
            </div></div>
            @if(! $program->published_at)
                <form method="POST" action="{{ route('programs.destroy', $program) }}" data-confirm="{{ __('Supprimer ce brouillon ?') }}">@csrf @method('DELETE')
                    <button class="btn btn-danger-outline btn-block"><x-icon name="trash"/>{{ __('Supprimer le brouillon') }}</button></form>
            @endif
        </div>
    </div>
@endsection
