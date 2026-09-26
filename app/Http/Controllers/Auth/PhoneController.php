<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** Vérification du numéro de téléphone par code SMS. */
class PhoneController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.verify-phone', ['user' => $request->user()]);
    }

    public function send(Request $request, SmsService $sms)
    {
        $user = $request->user();
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{8,20}$/', 'unique:users,phone,'.$user->id],
        ]);
        $phone = preg_replace('/\s+/', '', $data['phone']);
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'phone' => $phone,
            'phone_verified_at' => $phone === $user->getOriginal('phone') ? $user->phone_verified_at : null,
            'phone_code' => Hash::make($code),
            'phone_code_expires_at' => now()->addMinutes(10),
        ])->save();

        $sms->send($phone, __('Votre code Vwajèn : :code (valable 10 minutes)', ['code' => $code]));

        return back()->with('status', __('Un code a été envoyé au :phone.', ['phone' => $phone]))->with('code_sent', true);
    }

    public function confirm(Request $request)
    {
        $user = $request->user();
        $request->validate(['code' => ['required', 'digits:6']]);

        if (! $user->phone_code || ! $user->phone_code_expires_at?->isFuture() || ! Hash::check($request->code, $user->phone_code)) {
            return back()->withErrors(['code' => __('Code invalide ou expiré.')])->with('code_sent', true);
        }

        $user->forceFill(['phone_verified_at' => now(), 'phone_code' => null, 'phone_code_expires_at' => null])->save();

        return redirect()->route('settings.account')->with('status', __('Numéro de téléphone vérifié.'));
    }
}
