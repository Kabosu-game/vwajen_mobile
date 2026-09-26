<?php

use App\Http\Controllers\Api\PublicApiController as P;
use Illuminate\Support\Facades\Route;

/*
| API publique v1 (lecture seule) — données civiques ouvertes.
| Clé facultative via l'en-tête X-Api-Key (limite plus élevée). Documentation : /developers
*/
Route::prefix('v1')->middleware(['api.key', 'throttle:api'])->group(function () {
    Route::get('/candidates', [P::class, 'candidates']);
    Route::get('/candidates/{username}', [P::class, 'candidate']);
    Route::get('/candidates/{username}/program', [P::class, 'program']);
    Route::get('/categories', [P::class, 'categories']);
    Route::get('/questions', [P::class, 'questions']);
    Route::get('/debates', [P::class, 'debates']);
    Route::get('/events', [P::class, 'events']);
    Route::get('/officials', [P::class, 'officials']);
    Route::get('/officials/{username}/commitments', [P::class, 'commitments']);
    Route::get('/elections', [P::class, 'elections']);
    Route::get('/elections/{id}/results', [P::class, 'results']);
    Route::get('/stats', [P::class, 'stats']);
});
