@extends('layouts.admin')
@section('title', __('Notifications système'))
@section('content')
    <form method="POST" action="{{ route('admin.announcements.store') }}" class="card" data-confirm="{{ __('Envoyer cette notification à l\'audience choisie ?') }}"><div class="card-head"><h3>{{ __('Nouvelle notification') }}</h3></div><div class="card-body">
        @csrf
        <div class="grid grid-2">
            <div class="field"><label class="label" for="title">{{ __('Titre') }}</label><input id="title" name="title" class="input" required maxlength="150"></div>
            <div class="field"><label class="label" for="audience">{{ __('Audience') }}</label><select id="audience" name="audience" class="select">@foreach(['all' => __('Tous les utilisateurs'), 'candidates' => __('Candidats'), 'verified' => __('Comptes vérifiés'), 'diaspora' => __('Diaspora'), 'staff' => __('Équipe')] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
        </div>
        <div class="field"><label class="label" for="body">{{ __('Message') }}</label><textarea id="body" name="body" class="textarea" required maxlength="1000"></textarea></div>
        <div class="field"><label class="label" for="url">{{ __('Lien (facultatif)') }}</label><input id="url" type="url" name="url" class="input"></div>
        <button class="btn btn-primary"><x-icon name="send"/>{{ __('Envoyer') }}</button>
        <p class="hint">{{ __('Envoyée dans l\'application, et par e-mail / push / SMS selon les préférences de chacun.') }}</p>
    </div></form>
    <div class="card"><div class="card-head"><h3>{{ __('Historique') }}</h3></div><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Titre') }}</th><th>{{ __('Audience') }}</th><th>{{ __('Destinataires') }}</th><th>{{ __('Par') }}</th></tr></thead>
        <tbody>@forelse($announcements as $a)<tr><td class="small">{{ $a->created_at->format('d/m/Y H:i') }}</td><td><strong>{{ $a->title }}</strong><div class="small muted">{{ \Illuminate\Support\Str::limit($a->body, 100) }}</div></td><td>{{ __($a->audience) }}</td><td>{{ $a->recipients_count }}</td><td class="small">{{ $a->user?->name }}</td></tr>
        @empty<tr><td colspan="5" class="muted">—</td></tr>@endforelse</tbody>
    </table></div></div>{{ $announcements->links() }}
@endsection
