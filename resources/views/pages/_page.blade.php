@extends('layouts.app')
@section('content')
    <div class="page-head"><a href="{{ route('home') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1>@yield('title')</h1></div>
    <article class="card"><div class="card-body prose">
        @yield('page')
        <p class="small faint mt">{{ __('Dernière mise à jour : :date', ['date' => '1er septembre 2026']) }} · {{ __('Contact') }} : {{ setting('contact_email', 'contact@vwajen.ht') }}</p>
    </div></article>
@endsection
