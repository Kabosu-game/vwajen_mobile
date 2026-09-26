@extends('layouts.admin')
@section('title', __('Traductions : :name', ['name' => $language->native_name]))
@section('actions')<a href="{{ route('admin.languages.index') }}" class="btn btn-outline btn-sm">{{ __('Retour') }}</a>@endsection
@section('content')
    <form method="GET" class="filters"><input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Rechercher un texte') }}" aria-label="{{ __('Rechercher') }}">
        <label class="check mb-0"><input type="checkbox" name="missing" value="1" @checked(request('missing'))>{{ __('Manquantes uniquement') }}</label><button class="btn btn-primary">{{ __('Filtrer') }}</button></form>
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th style="width:45%">{{ __('Texte source (français)') }}</th><th>{{ $language->native_name }}</th></tr></thead>
        <tbody>@foreach($keys as $key)
            <tr><td class="small">{{ $key }}</td>
                <td><form method="POST" action="{{ route('admin.languages.translations.save', $language) }}" data-ajax class="row">@csrf<input type="hidden" name="key" value="{{ $key }}">
                    <input name="value" value="{{ $overrides[$key] ?? ($base[$key] ?? '') }}" class="input input-sm grow" aria-label="{{ __('Traduction') }}" @if($language->code === 'fr') placeholder="{{ $key }}" @endif>
                    @if(isset($overrides[$key]))<span class="badge badge-info" title="{{ __('Modifiée depuis l\'administration') }}">✎</span>@endif
                    <button class="btn btn-ghost btn-sm">OK</button></form></td></tr>
        @endforeach</tbody>
    </table></div></div>{{ $keys->links() }}
@endsection
