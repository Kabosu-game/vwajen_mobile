@extends('pages._page')
@section('title', __('Gestion des données sensibles'))
@section('page')
    <p>{{ __('Certaines données sont particulièrement sensibles dans un contexte politique. Voici comment Vwajèn les protège.') }}</p>
    <h2>{{ __('Opinions et affiliations politiques') }}</h2>
    <p>{{ __('Seuls les candidats et élus déclarent une affiliation, qu\'ils peuvent masquer. Les likes, soutiens et votes aux sondages ne sont jamais revendus ni utilisés pour du ciblage.') }}</p>
    <h2>{{ __('Localisation') }}</h2>
    <p>{{ __('Votre ville n\'est affichée que si vous l\'autorisez. La géolocalisation du navigateur n\'est utilisée qu\'à votre demande (carte, création d\'événement) et n\'est pas stockée.') }}</p>
    <h2>{{ __('Documents de vérification') }}</h2>
    <p>{{ __('Stockés hors de l\'espace public, accessibles uniquement à l\'équipe de vérification ; chaque consultation est journalisée dans l\'audit.') }}</p>
    <h2>{{ __('Messages privés') }}</h2>
    <p>{{ __('Visibles uniquement par les participants. L\'équipe de modération n\'y accède que sur signalement.') }}</p>
    <h2>{{ __('Signalements') }}</h2>
    <p>{{ __('L\'identité de la personne qui signale n\'est jamais communiquée à la personne signalée.') }}</p>
@endsection
