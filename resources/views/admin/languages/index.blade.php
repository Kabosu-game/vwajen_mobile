@extends('layouts.admin')
@section('title', __('Langues'))
@section('content')
    @foreach($languages as $l)<form id="lang-{{ $l->id }}" method="POST" action="{{ route('admin.languages.update', $l) }}">@csrf @method('PUT')</form>@endforeach
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Nom') }}</th><th>{{ __('Nom natif') }}</th><th>{{ __('Traduction') }}</th><th>{{ __('Active') }}</th><th>{{ __('Par défaut') }}</th><th></th></tr></thead>
        <tbody>@foreach($languages as $l)
            @php $f = 'lang-'.$l->id; @endphp
            <tr>
                <td><code>{{ $l->code }}</code></td>
                <td><input form="{{ $f }}" name="name" value="{{ $l->name }}" class="input input-sm" aria-label="{{ __('Nom') }}"></td>
                <td><input form="{{ $f }}" name="native_name" value="{{ $l->native_name }}" class="input input-sm" aria-label="{{ __('Nom natif') }}"></td>
                <td style="min-width:140px"><div class="progress"><span style="width:{{ $coverage[$l->code] }}%"></span></div><span class="small faint">{{ $coverage[$l->code] }} %</span></td>
                <td><input form="{{ $f }}" type="checkbox" name="is_active" value="1" @checked($l->is_active) aria-label="{{ __('Active') }}"></td>
                <td><input form="{{ $f }}" type="checkbox" name="is_default" value="1" @checked($l->is_default) aria-label="{{ __('Par défaut') }}"></td>
                <td class="actions-cell"><button form="{{ $f }}" class="btn btn-primary btn-sm">OK</button>
                    <a href="{{ route('admin.languages.translations', $l) }}" class="btn btn-outline btn-sm">{{ __('Traductions') }}</a></td>
            </tr>
        @endforeach</tbody>
    </table></div></div>
    <form method="POST" action="{{ route('admin.languages.store') }}" class="card"><div class="card-head"><h3>{{ __('Ajouter une langue') }}</h3></div><div class="card-body row wrap">
        @csrf<input name="code" class="input" placeholder="es" maxlength="5" required style="max-width:90px" aria-label="{{ __('Code') }}"><input name="name" class="input" placeholder="{{ __('Espagnol') }}" required style="max-width:200px" aria-label="{{ __('Nom') }}">
        <input name="native_name" class="input" placeholder="Español" required style="max-width:200px" aria-label="{{ __('Nom natif') }}"><button class="btn btn-primary">{{ __('Ajouter') }}</button>
        <p class="hint" style="width:100%">{{ __('Traduisez l\'interface puis activez la langue. Les contenus des utilisateurs peuvent être traduits automatiquement (IA).') }}</p>
    </div></form>
@endsection
