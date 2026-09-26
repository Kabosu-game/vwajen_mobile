<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** API publique : une clé (en-tête X-Api-Key) est facultative mais augmente la limite de requêtes. */
class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($key = $request->header('X-Api-Key')) {
            $apiKey = ApiKey::where('key_hash', hash('sha256', $key))->whereNull('revoked_at')->first();
            abort_unless($apiKey, 401, 'Invalid API key');
            $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();
            $request->attributes->set('api_key', $apiKey);
        }

        return $next($request);
    }
}
