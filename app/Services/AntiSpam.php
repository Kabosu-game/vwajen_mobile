<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Protection anti-spam et anti-faux comptes :
 * pot de miel, délai minimal de remplissage, e-mails jetables, contenus dupliqués,
 * excès de liens, mots interdits, limites pour les comptes récents.
 */
class AntiSpam
{
    public function checkForm(Request $request, int $minSeconds = 3): void
    {
        if (filled($request->input('website_url'))) { // champ pot de miel invisible
            throw ValidationException::withMessages(['email' => __('Requête refusée.')]);
        }
        $started = (int) $request->input('_form_ts', 0);
        if ($started && (time() - $started) < $minSeconds) {
            throw ValidationException::withMessages(['email' => __('Formulaire envoyé trop rapidement. Réessayez.')]);
        }
    }

    public function isDisposableEmail(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));

        return in_array($domain, config('vwajen.disposable_domains'), true);
    }

    /** Vérifie un texte publié par un utilisateur ; lève une ValidationException si c'est du spam. */
    public function checkContent(User $user, ?string $text, string $field = 'body'): void
    {
        $text = trim((string) $text);
        if ($text === '') {
            return;
        }

        $banned = array_filter(array_map('trim', explode(',', (string) Setting::get('banned_words', ''))));
        foreach ($banned as $word) {
            if ($word !== '' && mb_stripos($text, $word) !== false) {
                throw ValidationException::withMessages([$field => __('Votre texte contient des termes interdits par les règles de la communauté.')]);
            }
        }

        $links = preg_match_all('~https?://~i', $text);
        $isNew = $user->created_at && $user->created_at->gt(now()->subDay());
        if ($links > ($isNew ? 1 : 5)) {
            throw ValidationException::withMessages([$field => __('Trop de liens dans ce contenu.')]);
        }

        $hash = 'spam.dup.'.$user->id.'.'.md5(mb_strtolower($text));
        if (mb_strlen($text) > 15 && Cache::has($hash)) {
            throw ValidationException::withMessages([$field => __('Vous avez déjà publié ce contenu récemment.')]);
        }
        Cache::put($hash, 1, now()->addMinutes(10));
    }

    /** Les comptes non vérifiés par e-mail ne peuvent pas publier (protection contre les faux comptes). */
    public function ensureCanPublish(User $user): void
    {
        if (Setting::get('require_email_verification', true) && ! $user->hasVerifiedEmail()) {
            abort(403, __('Vérifiez votre adresse e-mail pour publier.'));
        }
        if (! $user->isActive()) {
            abort(403, __('Votre compte est restreint.'));
        }
    }
}
