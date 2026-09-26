@extends('layouts.app')
@section('title', $question->title)
@php $viewer = auth()->user(); @endphp
@section('content')
    <div class="page-head"><a href="{{ route('questions.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>{{ __('Question') }}</h1></div>

    <article class="card" id="question-{{ $question->id }}" data-item="question:{{ $question->id }}"><div class="card-body">
        <div class="row wrap" style="gap:.35rem">
            <span class="badge {{ $question->status === 'answered' ? 'badge-success' : ($question->status === 'closed' ? '' : 'badge-warning') }}">{{ $question->statusLabel() }}</span>
            @if($question->category)<span class="category-chip" style="--c:{{ $question->category->color }}">{{ $question->category->name }}</span>@endif
            @if(! $question->candidate)<span class="badge badge-info">{{ __('Question publique') }}</span>@endif
        </div>
        <h2 class="mt-sm" style="font-size:1.35rem">{{ $question->title }}</h2>
        @if($question->body)<div class="post-text" data-text>{!! render_text($question->body) !!}</div>@endif
        <div class="row small muted wrap">
            <img src="{{ $question->user->avatarUrl() }}" class="avatar avatar-xs" alt=""><a href="{{ $question->user->profileUrl() }}">{{ $question->user->name }}</a> · {{ $question->created_at->translatedFormat('d M Y') }}
            @if($question->candidate) · {{ __('adressée à') }} <a href="{{ route('candidates.show', $question->candidate->username) }}"><b>{{ $question->candidate->name }}</b></a>@include('partials.verified', ['u' => $question->candidate])@endif
        </div>
        @if($question->status === 'closed' && $question->close_reason)<div class="alert alert-warning mt"><x-icon name="lock"/><div>{{ __('Fermée') }} : {{ $question->close_reason }}</div></div>@endif

        <div class="row mt wrap">
            <button type="button" class="btn {{ $supported ? 'btn-soft' : 'btn-outline' }}" data-action="{{ route('questions.support', $question) }}" data-support data-auth @disabled($question->status === 'closed')>
                <x-icon name="chevron-up"/>{{ __('Soutenir') }} · <span data-count>{{ $question->supports_count }}</span></button>
            @include('partials.item-menu', ['model' => $question, 'type' => 'question'])
            <div class="right row">
                @auth
                    @if(in_array($viewer->id, [$question->user_id, $question->candidate_id]) || $viewer->hasPermission('moderation.manage'))
                        @if($question->status !== 'closed')
                            <form method="POST" action="{{ route('questions.close', $question) }}" data-confirm="{{ __('Fermer cette question ?') }}">@csrf<button class="btn btn-ghost btn-sm"><x-icon name="lock"/>{{ __('Fermer') }}</button></form>
                        @elseif($viewer->id === $question->user_id || $viewer->hasPermission('moderation.manage'))
                            <form method="POST" action="{{ route('questions.reopen', $question) }}">@csrf<button class="btn btn-ghost btn-sm"><x-icon name="unlock"/>{{ __('Rouvrir') }}</button></form>
                        @endif
                    @endif
                    @if(($viewer->id === $question->user_id && ! $question->answers_count) || $viewer->hasPermission('content.delete'))
                        <form method="POST" action="{{ route('questions.destroy', $question) }}" data-confirm="{{ __('Supprimer cette question ?') }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm"><x-icon name="trash"/></button></form>
                    @endif
                @endauth
            </div>
        </div>
        @include('partials.actions', ['model' => $question, 'type' => 'question'])
    </div></article>

    <div class="section-title"><h2>{{ trans_choice(':n réponse|:n réponses', $question->answers->count(), ['n' => $question->answers->count()]) }}</h2></div>
    @forelse($question->answers as $answer)
        <div class="answer-card" id="answer-{{ $answer->id }}" data-item="answer:{{ $answer->id }}">
            <div class="answer-label"><x-icon name="check-circle"/>{{ __('Réponse du candidat') }}</div>
            <div class="row"><img src="{{ $answer->user->avatarUrl() }}" class="avatar avatar-sm" alt="">
                <div><a href="{{ $answer->user->profileUrl() }}" class="name">{{ $answer->user->name }}</a>@include('partials.verified', ['u' => $answer->user])
                    <div class="small faint">{{ $answer->created_at->translatedFormat('d M Y, H:i') }}</div></div></div>
            @if($answer->video)
                <div class="player mt-sm"><video controls playsinline preload="{{ $viewer?->data_saver ? 'none' : 'metadata' }}" src="{{ $answer->video->videoUrl() }}" @if($answer->video->thumbnailUrl()) poster="{{ $answer->video->thumbnailUrl() }}" @endif>
                    @foreach($answer->video->subtitles as $s)<track kind="subtitles" src="{{ $s->url() }}" srclang="{{ $s->lang }}" label="{{ $s->label }}">@endforeach</video></div>
            @endif
            @if($answer->body)<div class="post-text mt-sm" data-text>{!! render_text($answer->body) !!}</div>@endif
            @include('partials.actions', ['model' => $answer, 'type' => 'answer'])
        </div>
    @empty
        <div class="card empty"><x-icon name="clock"/><p>{{ __('Pas encore de réponse. Soutenez la question pour augmenter sa visibilité.') }}</p></div>
    @endforelse

    @if($question->canBeAnsweredBy($viewer))
        <form method="POST" action="{{ route('questions.answer', $question) }}" enctype="multipart/form-data" class="card" id="answer-form"><div class="card-head"><h3>{{ __('Répondre en tant que candidat') }}</h3></div><div class="card-body">
            @csrf
            <div class="field"><label for="answer-body" class="label">{{ __('Réponse texte') }}</label><textarea id="answer-body" name="body" class="textarea" rows="5" maxlength="20000"></textarea></div>
            <div class="field" data-upload-wrap>
                <label class="label">{{ __('Réponse vidéo (facultatif)') }}</label>
                <input type="file" accept="video/*" class="input" data-resumable="#answer-upload" capture="user">
                <input type="hidden" name="video_upload" id="answer-upload">
                <video data-file-preview hidden controls playsinline style="max-height:220px;margin-top:.5rem;border-radius:12px"></video>
                <div class="upload-progress"><span></span></div><div class="small faint" data-upload-status></div>
                <div class="hint">{{ __('Vous pouvez enregistrer directement depuis votre téléphone. Le téléversement reprend automatiquement en cas de coupure.') }}</div>
            </div>
            <button class="btn btn-success">{{ __('Publier la réponse') }}</button>
        </div></form>
    @endif

    @include('partials.comments', ['target' => $question, 'type' => 'question', 'comments' => $comments])
@endsection
@section('right')
    @include('partials.right')
    @if($related->isNotEmpty())
        <div class="panel"><h3>{{ __('Questions similaires') }}</h3>
            @foreach($related as $r)<a href="{{ $r->url() }}" class="trend" style="padding:.4rem 0"><div class="clamp-2" style="font-weight:600">{{ $r->title }}</div><div class="small faint">{{ $r->supports_count }} {{ __('soutiens') }} · {{ $r->statusLabel() }}</div></a>@endforeach
        </div>
    @endif
@endsection
