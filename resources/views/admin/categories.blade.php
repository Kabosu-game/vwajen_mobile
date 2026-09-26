@extends('layouts.admin')
@section('title', __('Catégories'))
@section('content')
    @foreach($categories as $c)
        <form id="cat-{{ $c->id }}" method="POST" action="{{ route('admin.categories.update', $c) }}">@csrf @method('PUT')</form>
        <form id="cat-del-{{ $c->id }}" method="POST" action="{{ route('admin.categories.destroy', $c) }}" data-confirm="{{ __('Supprimer ?') }}">@csrf @method('DELETE')</form>
    @endforeach
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>Slug</th><th>Kreyòl</th><th>Français</th><th>English</th><th>{{ __('Couleur') }}</th><th>{{ __('Ordre') }}</th><th>{{ __('Active') }}</th><th></th></tr></thead>
        <tbody>
        @foreach($categories as $c)
            @php $f = 'cat-'.$c->id; @endphp
            <tr>
                <td><input form="{{ $f }}" name="slug" value="{{ $c->slug }}" class="input input-sm" style="width:130px" aria-label="slug"></td>
                <td><input form="{{ $f }}" name="name_ht" value="{{ $c->name_ht }}" class="input input-sm" aria-label="Kreyòl"></td>
                <td><input form="{{ $f }}" name="name_fr" value="{{ $c->name_fr }}" class="input input-sm" aria-label="Français"></td>
                <td><input form="{{ $f }}" name="name_en" value="{{ $c->name_en }}" class="input input-sm" aria-label="English"></td>
                <td><input form="{{ $f }}" type="color" name="color" value="{{ $c->color ?? '#1d4ed8' }}" aria-label="{{ __('Couleur') }}"></td>
                <td><input form="{{ $f }}" type="number" name="position" value="{{ $c->position }}" class="input input-sm" style="width:70px" aria-label="{{ __('Ordre') }}"></td>
                <td><input form="{{ $f }}" type="checkbox" name="is_active" value="1" @checked($c->is_active) aria-label="{{ __('Active') }}"></td>
                <td class="actions-cell"><button form="{{ $f }}" class="btn btn-primary btn-sm">OK</button>
                    <button form="cat-del-{{ $c->id }}" class="btn btn-ghost btn-sm" aria-label="{{ __('Supprimer') }}"><x-icon name="trash"/></button></td>
            </tr>
        @endforeach
        </tbody>
    </table></div></div>
    <form method="POST" action="{{ route('admin.categories.store') }}" class="card"><div class="card-head"><h3>{{ __('Nouvelle catégorie') }}</h3></div><div class="card-body row wrap">
        @csrf
        <input name="name_ht" class="input" placeholder="Kreyòl" required style="max-width:180px" aria-label="Kreyòl"><input name="name_fr" class="input" placeholder="Français" required style="max-width:180px" aria-label="Français">
        <input name="name_en" class="input" placeholder="English" required style="max-width:180px" aria-label="English"><input name="slug" class="input" placeholder="slug" style="max-width:140px" aria-label="slug">
        <input type="color" name="color" value="#1d4ed8" aria-label="{{ __('Couleur') }}"><input type="hidden" name="is_active" value="1">
        <button class="btn btn-primary">{{ __('Ajouter') }}</button>
    </div></form>
@endsection
