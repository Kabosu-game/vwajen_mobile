@extends('pages._page')
@section('title', __('Politique de confidentialité'))
@section('page')
    <p>{{ __('Cette politique explique quelles données Vwajèn collecte, pourquoi, et quels sont vos droits.') }}</p>
    <h2>1. {{ __('Données collectées') }}</h2>
    <ul>
        <li>{{ __('Compte : nom, nom d\'utilisateur, e-mail, téléphone (facultatif), mot de passe (chiffré), langue, pays.') }}</li>
        <li>{{ __('Profil : photo, couverture, biographie, localisation — selon vos choix de visibilité.') }}</li>
        <li>{{ __('Contenus : publications, commentaires, messages, vidéos, questions, réactions.') }}</li>
        <li>{{ __('Techniques : adresse IP, navigateur, appareil, sessions — pour la sécurité et la gestion des appareils.') }}</li>
        <li>{{ __('Vérification : documents justificatifs, stockés sur un espace privé et consultés uniquement par l\'équipe de vérification.') }}</li>
    </ul>
    <h2>2. {{ __('Finalités') }}</h2>
    <p>{{ __('Fournir le service, personnaliser votre fil, envoyer les notifications que vous avez choisies, assurer la sécurité (anti-spam, faux comptes), modérer et produire des statistiques agrégées.') }}</p>
    <h2>3. {{ __('Données sensibles') }}</h2>
    <p>{{ __('Les opinions politiques sont des données sensibles. Vous contrôlez l\'affichage de votre affiliation déclarée. Vwajèn ne vend aucune donnée et ne fait pas de ciblage publicitaire politique.') }}</p>
    <h2>4. {{ __('Partage') }}</h2>
    <p>{{ __('Vos contenus publics sont visibles par tous. Les données ne sont partagées qu\'avec des prestataires techniques (hébergement, e-mail, SMS, IA pour les fonctions optionnelles) ou sur obligation légale.') }}</p>
    <h2>5. {{ __('Vos droits') }}</h2>
    <ul>
        <li>{{ __('Accès et portabilité : export complet depuis Paramètres > Données personnelles.') }}</li>
        <li>{{ __('Rectification : modification du profil et du compte à tout moment.') }}</li>
        <li>{{ __('Suppression : suppression sécurisée du compte (définitive après le délai de grâce).') }}</li>
        <li>{{ __('Opposition : réglages de confidentialité, de notifications et de cookies.') }}</li>
    </ul>
    <h2>6. {{ __('Conservation') }}</h2>
    <p>{{ __('Les données sont conservées tant que le compte est actif. Les journaux de sécurité et d\'audit sont conservés 12 mois.') }}</p>
@endsection
