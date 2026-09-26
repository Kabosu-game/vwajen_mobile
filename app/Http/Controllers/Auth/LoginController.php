<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\DeviceTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request, DeviceTracker $devices)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($data['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : (preg_match('/^\+?[0-9 ]{8,}$/', $login) ? 'phone' : 'username');
        $value = $field === 'phone' ? preg_replace('/\s+/', '', $login) : strtolower(ltrim($login, '@'));

        $user = User::where($field, $value)->first();
        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => __('Identifiants incorrects.')]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $devices->track($user, $request);

        if ($user->deletion_requested_at) {
            session()->flash('warning', __('La suppression de votre compte est programmée. Vous pouvez l\'annuler dans vos paramètres.'));
        }

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request)
    {
        UserDevice::where('user_id', $request->user()->id)->where('session_id', $request->session()->getId())->update(['session_id' => null]);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
