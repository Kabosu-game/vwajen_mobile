<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/** Mot de passe oublié + réinitialisation. */
class PasswordController extends Controller
{
    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        // Réponse identique que le compte existe ou non (pas d'énumération des comptes).
        return back()->with('status', __('Si un compte existe pour cette adresse, un lien de réinitialisation a été envoyé.'));
    }

    public function reset(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete(); // déconnecte toutes les sessions
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __('Mot de passe réinitialisé. Vous pouvez vous connecter.'))
            : back()->withInput($request->only('email'))->withErrors(['email' => __('Lien invalide ou expiré.')]);
    }
}
