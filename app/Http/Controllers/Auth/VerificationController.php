<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

/** Vérification de l'adresse e-mail. */
class VerificationController extends Controller
{
    public function notice(Request $request)
    {
        return $request->user()->hasVerifiedEmail() ? redirect()->route('home') : view('auth.verify-email');
    }

    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);
        abort_unless($request->user()->id === $user->id && hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->route('home')->with('status', __('Adresse e-mail vérifiée. Merci !'));
    }

    public function send(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', __('Un nouveau lien de vérification a été envoyé.'));
    }
}
