@extends('layouts.admin')
@section('title', __('Élections et résultats'))
@php $types = ['presidential' => __('Présidentielle'), 'legislative' => __('Législatives'), 'municipal' => __('Municipales'), 'local' => __('Locales'), 'referendum' => __('Référendum')]; @endphp
@section('content')
    <details class="card"><summary class="card-body" style="cursor:pointer"><strong>+ {{ __('Nouvelle élection') }}</strong></summary>
        <form method="POST" action="{{ route('admin.elections.store') }}" class="card-body" style="padding-top:0">@csrf @include('admin.election-fields', ['e' => null])<button class="btn btn-primary btn-sm mt-sm">{{ __('Créer') }}</button></form></details>
    @forelse($elections as $e)
        <details class="card">
            <summary class="card-body row" style="cursor:pointer"><x-icon name="archive"/><strong class="grow">{{ $e->name }}</strong><span class="small faint">{{ $e->held_on->format('d/m/Y') }} · {{ $e->results_count }} {{ __('résultats') }}</span>
                @if($e->results_published)<span class="badge badge-success">{{ __('Publiés') }}</span>@else<span class="badge">{{ __('Brouillon') }}</span>@endif</summary>
            <div class="card-body" style="padding-top:0">
                <form method="POST" action="{{ route('admin.elections.update', $e) }}">@csrf @method('PUT') @include('admin.election-fields', ['e' => $e])
                    <div class="row mt-sm"><button class="btn btn-primary btn-sm">{{ __('Enregistrer') }}</button><button type="submit" form="del-el-{{ $e->id }}" class="btn btn-danger-outline btn-sm">{{ __('Supprimer') }}</button></div></form>
                <form id="del-el-{{ $e->id }}" method="POST" action="{{ route('admin.elections.destroy', $e) }}" data-confirm="{{ __('Supprimer cette élection ?') }}">@csrf @method('DELETE')</form>
                <h4 class="mt">{{ __('Résultats') }}</h4>
                <div class="table-wrap"><table class="table"><thead><tr><th>{{ __('Candidat') }}</th><th>{{ __('Parti') }}</th><th>{{ __('Circonscription') }}</th><th>{{ __('Voix') }}</th><th>%</th><th>{{ __('Élu') }}</th><th></th></tr></thead><tbody>
                    @foreach($e->results as $r)<tr><td>{{ $r->candidate_name }}</td><td>{{ $r->party }}</td><td>{{ $r->constituency }}</td><td>{{ number_format($r->votes, 0, ',', ' ') }}</td><td>{{ $r->percentage }}</td><td>{{ $r->elected ? '✓' : '' }}</td>
                        <td><form method="POST" action="{{ route('admin.elections.results.destroy', $r) }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm"><x-icon name="trash"/></button></form></td></tr>@endforeach
                </tbody></table></div>
                <form method="POST" action="{{ route('admin.elections.results.store', $e) }}" class="row wrap mt-sm">@csrf
                    <input name="candidate_name" class="input input-sm" placeholder="{{ __('Candidat') }}" required style="max-width:180px" aria-label="{{ __('Candidat') }}">
                    <input name="username" class="input input-sm" placeholder="{{ __('Compte (facultatif)') }}" style="max-width:140px" aria-label="username">
                    <input name="party" class="input input-sm" placeholder="{{ __('Parti') }}" style="max-width:140px" aria-label="{{ __('Parti') }}">
                    <input name="constituency" class="input input-sm" placeholder="{{ __('Circonscription') }}" style="max-width:150px" aria-label="{{ __('Circonscription') }}">
                    <input name="votes" type="number" class="input input-sm" placeholder="{{ __('Voix') }}" required style="max-width:110px" aria-label="{{ __('Voix') }}">
                    <input name="percentage" type="number" step="0.01" class="input input-sm" placeholder="%" style="max-width:80px" aria-label="%">
                    <label class="check mb-0 small"><input type="checkbox" name="elected" value="1">{{ __('Élu') }}</label>
                    <button class="btn btn-soft btn-sm">{{ __('Ajouter') }}</button></form>
            </div>
        </details>
    @empty<div class="card empty"><p>{{ __('Aucune élection.') }}</p></div>@endforelse
@endsection
