<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Models\TranslationOverride;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/** Langue de l'interface : utilisateur > session > cookie > navigateur > défaut (kreyòl). */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('vwajen.locales'));
        try {
            $active = Language::where('is_active', true)->pluck('code')->all() ?: $available;
        } catch (\Throwable) {
            $active = $available;
        }

        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? $request->cookie('locale')
            ?? $request->getPreferredLanguage($active);

        if (! in_array($locale, $active, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        // Traductions modifiées depuis l'administration (prioritaires sur les fichiers lang/*.json).
        try {
            $overrides = Cache::rememberForever("translations.overrides.$locale",
                fn () => TranslationOverride::where('locale', $locale)->pluck('value', 'key')->all());
            if ($overrides) {
                app('translator')->addLines(collect($overrides)->mapWithKeys(fn ($v, $k) => ['*.'.$k => $v])->all(), $locale);
            }
        } catch (\Throwable) {
            // base non migrée : on ignore
        }
        Carbon::setLocale($locale === 'ht' ? 'fr' : $locale);

        return $next($request);
    }
}
