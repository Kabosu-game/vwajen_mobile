<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    /** Changement de langue (visiteurs et membres). */
    public function locale(Request $request, string $locale)
    {
        abort_unless(array_key_exists($locale, config('vwajen.locales')), 404);
        session(['locale' => $locale]);
        $request->user()?->update(['locale' => $locale]);

        return redirect()->back(fallback: route('home'))->withCookie(cookie()->forever('locale', $locale));
    }

    /** Consentement cookies (bannière). */
    public function cookieConsent(Request $request)
    {
        $choice = $request->input('choice', 'necessary');
        $prefs = ['necessary' => true, 'preferences' => $choice === 'all', 'analytics' => $choice === 'all'];
        $request->user()?->update(['cookie_prefs' => $prefs, 'cookie_consent_at' => now()]);

        return $this->reply($request, ['ok' => true])->withCookie(cookie()->forever('cookie_consent', json_encode($prefs)));
    }

    public function show(string $page)
    {
        return view('pages.'.$page);
    }

    public function offline()
    {
        return view('pages.offline');
    }

    public function developers()
    {
        return view('pages.developers');
    }

    /** Manifeste PWA (installation mobile, deep links). */
    public function manifest()
    {
        return response()->json([
            'name' => 'Vwajèn',
            'short_name' => 'Vwajèn',
            'description' => __('La voix des citoyens haïtiens'),
            'start_url' => '/?source=pwa',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#050d2b',
            'theme_color' => '#0b1f5c',
            'lang' => app()->getLocale(),
            'icons' => [
                ['src' => asset('images/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => asset('images/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
            'share_target' => ['action' => '/', 'method' => 'GET', 'params' => ['title' => 'share_title', 'text' => 'share_text', 'url' => 'share_url']],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }
}
