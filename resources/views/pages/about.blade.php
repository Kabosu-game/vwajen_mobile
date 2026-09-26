@extends('pages._page')
@section('title', __('À propos de Vwajèn'))
@section('page')
    <p><strong>Vwajèn</strong> — « la voix des citoyens » — {{ __('est une plateforme civique et sociale pour les Haïtiens en Haïti et dans la diaspora.') }}</p>
    <h2>{{ __('Notre mission') }}</h2>
    <p>{{ __('Permettre à chaque citoyen de s\'exprimer, de s\'informer à partir de sources vérifiables, d\'interpeller directement les candidats et les élus, et de suivre les engagements pris.') }}</p>
    <h2>{{ __('Nos principes') }}</h2>
    <ul>
        <li><strong>{{ __('Neutralité') }}</strong> — {{ __('Vwajèn ne soutient aucun candidat ni parti. Les comparaisons ne comportent ni classement ni score politique.') }}</li>
        <li><strong>{{ __('Transparence') }}</strong> — {{ __('Les programmes sont versionnés, les modifications importantes historisées et les sources affichées avec leur statut de vérification.') }}</li>
        <li><strong>{{ __('Inclusion') }}</strong> — {{ __('Kreyòl, français, anglais ; fonctionnement adapté aux connexions lentes ; accessibilité.') }}</li>
        <li><strong>{{ __('Respect') }}</strong> — {{ __('Des règles de communauté claires, une modération humaine et un système d\'appel.') }}</li>
    </ul>
    <h2>{{ __('Fonctionnalités') }}</h2>
    <div class="grid grid-3 feature-grid">
        @foreach([['message', __('Réseau social « Vwa »')], ['vote', __('Candidats et programmes')], ['compare', __('Comparaison informative')], ['question', 'Kesyon pou kandida yo'], ['debate', __('Débats')], ['live', 'Vwajèn Live'], ['shorts', 'Vwajèn Shorts'], ['map', 'Vwajèn Map'], ['globe', 'Vwajèn Mond']] as [$i, $l])
            <div class="panel"><x-icon name="{{ $i }}"/><div style="font-weight:700">{{ $l }}</div></div>
        @endforeach
    </div>
@endsection
