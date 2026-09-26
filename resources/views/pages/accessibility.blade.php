@extends('pages._page')
@section('title', __('Accessibilité'))
@section('page')
    <p>{{ __('Vwajèn vise la conformité aux recommandations WCAG 2.1 niveau AA.') }}</p>
    <ul>
        <li>{{ __('Taille du texte configurable (4 niveaux) et mode contraste élevé.') }}</li>
        <li>{{ __('Thèmes clair et sombre, réduction des animations.') }}</li>
        <li>{{ __('Navigation complète au clavier, lien d\'évitement, focus visible.') }}</li>
        <li>{{ __('Étiquettes accessibles (ARIA) pour les lecteurs d\'écran.') }}</li>
        <li>{{ __('Texte alternatif pour les images publiées.') }}</li>
        <li>{{ __('Sous-titres pour les vidéos (manuels ou automatiques).') }}</li>
        <li>{{ __('Interface en kreyòl, français et anglais.') }}</li>
    </ul>
    <h2>{{ __('Raccourcis clavier') }}</h2>
    <ul><li><span class="kbd">n</span> {{ __('Publier une Vwa') }}</li><li><span class="kbd">/</span> {{ __('Rechercher') }}</li><li><span class="kbd">Échap</span> {{ __('Fermer') }}</li>
        <li><span class="kbd">↑</span> <span class="kbd">↓</span> {{ __('Naviguer dans les Shorts') }}</li><li><span class="kbd">m</span> {{ __('Son des Shorts') }}</li></ul>
    @auth<a href="{{ route('settings.accessibility') }}" class="btn btn-primary">{{ __('Réglages d\'accessibilité') }}</a>@endauth
@endsection
