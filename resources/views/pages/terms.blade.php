@extends('pages._page')
@section('title', __('Conditions d\'utilisation'))
@section('page')
    <h2>1. {{ __('Objet') }}</h2>
    <p>{{ __('Vwajèn est un service gratuit de publication, d\'information civique et d\'échange. En créant un compte, vous acceptez ces conditions et les règles de la communauté.') }}</p>
    <h2>2. {{ __('Compte') }}</h2>
    <p>{{ __('Vous devez fournir des informations exactes, protéger votre mot de passe et ne pas usurper l\'identité d\'autrui. Un compte par personne.') }}</p>
    <h2>3. {{ __('Contenus') }}</h2>
    <p>{{ __('Vous restez propriétaire de vos contenus et accordez à Vwajèn une licence d\'affichage et de diffusion sur la plateforme. Vous êtes responsable de ce que vous publiez.') }}</p>
    <h2>4. {{ __('Candidats et élus') }}</h2>
    <p>{{ __('Les informations des programmes sont fournies par les candidats sous leur responsabilité. Le statut de vérification des sources est indiqué. Vwajèn ne garantit pas l\'exactitude des propos des candidats.') }}</p>
    <h2>5. {{ __('Modération') }}</h2>
    <p>{{ __('Vwajèn peut masquer ou supprimer un contenu, avertir, suspendre ou bannir un compte en cas de violation. Toute sanction peut faire l\'objet d\'un appel.') }}</p>
    <h2>6. {{ __('Lives') }}</h2>
    <p>{{ __('Les lives sont réservés aux comptes certifiés, qui s\'engagent à respecter les règles et à modérer leur chat.') }}</p>
    <h2>7. {{ __('Responsabilité') }}</h2>
    <p>{{ __('Le service est fourni « en l\'état ». Vwajèn s\'efforce d\'assurer sa disponibilité, y compris en faible connexion, sans garantie d\'absence d\'interruption.') }}</p>
@endsection
