<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DeviceTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/** Connexion Google et Apple. */
class SocialController extends Controller
{
    public function redirect(string $provider)
    {
        abort_unless(config("services.$provider.client_id"), 404, __('Ce mode de connexion n\'est pas configuré.'));

        $driver = Socialite::driver($provider);
        if ($provider === 'apple') {
            $driver->scopes(['name', 'email']);
        }

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider, DeviceTracker $devices)
    {
        abort_unless(config("services.$provider.client_id"), 404);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['login' => __('La connexion a échoué. Réessayez.')]);
        }

        $column = $provider.'_id';
        $email = $social->getEmail() ? strtolower($social->getEmail()) : null;

        $user = User::where($column, $social->getId())->first()
            ?? ($email ? User::where('email', $email)->first() : null);

        if ($user) {
            if (! $user->{$column}) {
                $user->forceFill([$column => $social->getId()])->save();
            }
        } else {
            if (! $email) {
                return redirect()->route('register')->withErrors(['email' => __('Aucune adresse e-mail fournie par le service.')]);
            }
            abort_if(! setting('registration_open', true), 403);
            $base = Str::of($social->getNickname() ?: Str::before($email, '@'))->ascii()->lower()->replaceMatches('/[^a-z0-9_]/', '')->limit(20, '')->value() ?: 'user';
            $username = $base;
            while (User::where('username', $username)->exists()) {
                $username = $base.random_int(100, 9999);
            }
            $user = User::create([
                'name' => $social->getName() ?: $username,
                'username' => $username,
                'email' => $email,
                $column => $social->getId(),
                'avatar' => $social->getAvatar(),
                'locale' => app()->getLocale(),
                'terms_accepted_at' => now(),
                'registration_ip' => $request->ip(),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save(); // e-mail vérifié par le fournisseur
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $devices->track($user, $request);

        return redirect()->intended(route('home'));
    }
}
