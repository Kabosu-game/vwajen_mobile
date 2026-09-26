@extends('layouts.admin')
@section('title', __('Contenus : :type', ['type' => __(ucfirst($type))]))
@section('content')
    <form method="GET" class="filters">
        <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Texte') }}" aria-label="{{ __('Rechercher') }}">
        <input name="user" value="{{ request('user') }}" class="input" placeholder="@username" aria-label="{{ __('Auteur') }}">
        <select name="state" class="select" aria-label="{{ __('État') }}"><option value="">{{ __('Tous') }}</option>@foreach(['reported' => __('Signalés'), 'hidden' => __('Masqués')] as $k => $l)<option value="{{ $k }}" @selected(request('state') === $k)>{{ $l }}</option>@endforeach</select>
        <button class="btn btn-primary">{{ __('Filtrer') }}</button>
    </form>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Contenu') }}</th><th>{{ __('Auteur') }}</th><th>{{ __('État') }}</th><th>{{ __('Signal.') }}</th><th>{{ __('Date') }}</th><th></th></tr></thead>
        <tbody>
        @forelse($items as $m)
            @php $owner = $type === 'communities' ? $m->owner : $m->user; $url = method_exists($m, 'url') ? $m->url() : null; @endphp
            <tr>
                <td style="max-width:420px"><div class="truncate">@if($url)<a href="{{ $url }}" target="_blank">@endif<strong>{{ \Illuminate\Support\Str::limit($m->title ?? $m->name ?? $m->body ?? $m->description ?? '#'.$m->id, 90) }}</strong>@if($url)</a>@endif</div>
                    <div class="small faint">#{{ $m->id }} @if(isset($m->likes_count))· ♥ {{ $m->likes_count }}@endif @if(isset($m->comments_count))· 💬 {{ $m->comments_count }}@endif @if(isset($m->views_count))· 👁 {{ $m->views_count }}@endif</div></td>
                <td class="small">@if($owner)<a href="{{ route('admin.users.show', $owner->id) }}">{{ '@'.$owner->username }}</a>@endif</td>
                <td>@if($m->is_hidden)<span class="badge badge-warning">{{ __('Masqué') }}</span>@else<span class="badge badge-success">{{ __('Visible') }}</span>@endif</td>
                <td>@if($m->reports_count)<span class="badge badge-danger">{{ $m->reports_count }}</span>@else 0 @endif</td>
                <td class="small">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                <td class="actions-cell">
                    <form method="POST" data-confirm="{{ __('Confirmer ?') }}" class="row" style="justify-content:flex-end">@csrf
                        <input name="reason" class="input input-sm" placeholder="{{ __('Motif') }}" style="width:120px" aria-label="{{ __('Motif') }}">
                        @if($m->is_hidden)
                            <button formaction="{{ route('admin.content.action', [$type, $m->id, 'restore']) }}" class="btn btn-success btn-sm">{{ __('Afficher') }}</button>
                        @else
                            <button formaction="{{ route('admin.content.action', [$type, $m->id, 'hide']) }}" class="btn btn-outline btn-sm">{{ __('Masquer') }}</button>
                        @endif
                        <button formaction="{{ route('admin.content.action', [$type, $m->id, 'delete']) }}" class="btn btn-danger btn-sm" data-confirm="{{ __('Supprimer définitivement ce contenu ? Il sera effacé avec ses commentaires, réactions et fichiers. Cette action est irréversible.') }}">{{ __('Supprimer') }}</button>
                    </form>
                </td>
            </tr>
        @empty<tr><td colspan="6" class="muted">{{ __('Aucun contenu.') }}</td></tr>@endforelse
        </tbody>
    </table></div></div>
    {{ $items->links() }}
@endsection
