<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Ambassador;
use App\Models\User;
use App\Services\AntiSpam;
use App\Services\DeviceTracker;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create()
    {
        abort_if(! setting('registration_open', true), 403, __('Les inscriptions sont temporairement fermées.'));
        if ($ref = request()->query('ref')) {
            session(['referral' => strtoupper(substr((string) $ref, 0, 16))]); // programme ambassadeurs
        }

        return view('auth.register');
    }

    public function store(Request $request, AntiSpam $spam, DeviceTracker $devices)
    {
        abort_if(! setting('registration_open', true), 403);
        $spam->checkForm($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_]+$/', 'unique:users,username',
                'not_in:admin,administrator,vwajen,support,moderator,api,settings,candidates,login,register'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ]{8,20}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'account_type' => ['required', 'in:personal,candidate,organization'],
            'country' => ['required', 'string', 'size:2'],
            'locale' => ['nullable', 'in:ht,fr,en'],
            'terms' => ['accepted'],
        ], [], ['username' => __('nom d\'utilisateur')]);

        if ($spam->isDisposableEmail($data['email'])) {
            return back()->withInput()->withErrors(['email' => __('Les adresses e-mail jetables ne sont pas acceptées.')]);
        }

        $user = User::create([
            'name' => $data['name'],
            'username' => strtolower($data['username']),
            'email' => strtolower($data['email']),
            'phone' => ! empty($data['phone']) ? preg_replace('/\s+/', '', $data['phone']) : null,
            'password' => $data['password'],
            'account_type' => $data['account_type'] === 'candidate' ? 'personal' : $data['account_type'],
            'country' => strtoupper($data['country']),
            'is_diaspora' => strtoupper($data['country']) !== 'HT',
            'locale' => $data['locale'] ?? app()->getLocale(),
            'terms_accepted_at' => now(),
            'registration_ip' => $request->ip(),
            'cookie_consent_at' => $request->cookie('cookie_consent') ? now() : null,
        ]);

        if ($code = $request->session()->pull('referral')) {
            Ambassador::where('referral_code', $code)->where('status', 'approved')->increment('referrals_count');
        }

        event(new Registered($user)); // envoie l'e-mail de vérification
        Auth::login($user);
        $request->session()->regenerate();
        $devices->track($user, $request);

        if ($data['account_type'] === 'candidate') {
            return redirect()->route('candidates.register')->with('status', __('Compte créé ! Complétez votre profil de candidat.'));
        }

        return redirect()->route('settings.interests')->with('status', __('Bienvenue sur Vwajèn ! Un e-mail de vérification vous a été envoyé.'));
    }
}
