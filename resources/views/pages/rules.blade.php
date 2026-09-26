@extends('pages._page')
@section('title', __('Règles de la communauté'))
@section('page')
    <p>{{ __('Vwajèn est un espace de débat démocratique. Les désaccords sont bienvenus ; les attaques ne le sont pas.') }}</p>
    <h2>{{ __('Interdit') }}</h2>
    <ul>
        <li>{{ __('Harcèlement, intimidation, menaces ou appels à la violence.') }}</li>
        <li>{{ __('Discours de haine visant une origine, une religion, un genre, une orientation, un handicap.') }}</li>
        <li>{{ __('Fausses informations sur le processus électoral (dates, lieux, modalités de vote).') }}</li>
        <li>{{ __('Usurpation d\'identité, faux comptes, comportements coordonnés inauthentiques.') }}</li>
        <li>{{ __('Spam, arnaques, contenus sexuels explicites, contenus illégaux.') }}</li>
        <li>{{ __('Publication d\'informations personnelles d\'autrui sans consentement.') }}</li>
    </ul>
    <h2>{{ __('Encouragé') }}</h2>
    <ul>
        <li>{{ __('Citer ses sources.') }}</li>
        <li>{{ __('Critiquer les idées, pas les personnes.') }}</li>
        <li>{{ __('Signaler les contenus problématiques plutôt que d\'y répondre.') }}</li>
    </ul>
    <h2>{{ __('Sanctions') }}</h2>
    <p>{{ __('Selon la gravité : contenu masqué ou supprimé, avertissement, suspension temporaire, bannissement. Chaque sanction est motivée, historisée et peut faire l\'objet d\'un appel examiné par un autre modérateur.') }}</p>
@endsection
