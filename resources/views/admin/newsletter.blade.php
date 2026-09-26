@extends('layouts.admin')
@section('title', __('Newsletter'))
@section('content')
    <div class="grid grid-2 mb"><div class="stat"><div class="v">{{ $subscribers }}</div><div class="l">{{ __('Abonnés confirmés') }}</div></div><div class="stat"><div class="v">{{ $pending }}</div><div class="l">{{ __('En attente de confirmation') }}</div></div></div>
    <form method="POST" action="{{ route('admin.newsletter.send') }}" class="card" data-confirm="{{ __('Envoyer à tous les abonnés confirmés ?') }}"><div class="card-head"><h3>{{ __('Nouvelle newsletter') }}</h3></div><div class="card-body">
        @csrf<input name="subject" class="input" placeholder="{{ __('Objet') }}" required aria-label="{{ __('Objet') }}">
        <textarea name="body" class="textarea mt-sm" rows="8" required placeholder="{{ __('Contenu (texte)') }}" aria-label="{{ __('Contenu') }}"></textarea>
        <button class="btn btn-primary mt-sm"><x-icon name="send"/>{{ __('Envoyer') }}</button>
    </div></form>
    <div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>{{ __('Objet') }}</th><th>{{ __('Envoyée') }}</th><th>{{ __('Destinataires') }}</th><th>{{ __('Par') }}</th></tr></thead>
        <tbody>@forelse($newsletters as $n)<tr><td>{{ $n->subject }}</td><td class="small">{{ $n->sent_at?->format('d/m/Y H:i') }}</td><td>{{ $n->recipients_count }}</td><td class="small">{{ $n->user?->name }}</td></tr>@empty<tr><td colspan="4" class="muted">—</td></tr>@endforelse</tbody></table></div></div>
    {{ $newsletters->links() }}
@endsection
