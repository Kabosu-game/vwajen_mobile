@extends('pages._page')
@section('title', __('API publique'))
@section('page')
    <p>{{ __('L\'API publique de Vwajèn donne accès en lecture aux données civiques ouvertes, au format JSON.') }}</p>
    <p><strong>{{ __('URL de base') }} :</strong> <code>{{ url('/api/v1') }}</code></p>
    <p>{{ __('Sans clé : 60 requêtes/minute par adresse IP. Avec une clé (en-tête X-Api-Key) : 600 requêtes/minute.') }} @auth<a href="{{ route('settings.api-keys') }}">{{ __('Créer une clé') }}</a>@endauth</p>
    <table class="table"><thead><tr><th>{{ __('Point d\'accès') }}</th><th>{{ __('Description') }}</th></tr></thead><tbody>
        @foreach([
            'GET /candidates?department=&position=' => __('Liste des candidats'),
            'GET /candidates/{username}' => __('Profil d\'un candidat et ses sources'),
            'GET /candidates/{username}/program' => __('Programme publié et propositions par thème'),
            'GET /categories' => __('Thèmes des propositions'),
            'GET /questions?status=&candidate=' => __('Questions aux candidats'),
            'GET /debates' => __('Débats'),
            'GET /events?department=' => __('Événements à venir'),
            'GET /officials' => __('Responsables élus'),
            'GET /officials/{username}/commitments' => __('Engagements documentés et suivi'),
            'GET /elections' => __('Archives électorales'),
            'GET /elections/{id}/results' => __('Résultats publics'),
            'GET /stats' => __('Statistiques générales'),
        ] as $e => $d)
            <tr><td><code>{{ $e }}</code></td><td>{{ $d }}</td></tr>
        @endforeach
    </tbody></table>
    <h2>{{ __('Exemple') }}</h2>
    <pre>curl -H "X-Api-Key: vwj_…" {{ url('/api/v1/candidates?department=ouest') }}</pre>
    <p class="small muted">{{ __('Licence des données : réutilisation libre avec mention « Source : Vwajèn ». Les données des programmes sont fournies par les candidats.') }}</p>
@endsection
