<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** Système de newsletter (double opt-in). */
class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $sub = NewsletterSubscriber::firstOrCreate(['email' => strtolower($data['email'])], ['token' => Str::random(48), 'locale' => app()->getLocale()]);
        if (! $sub->confirmed_at || $sub->unsubscribed_at) {
            $sub->update(['unsubscribed_at' => null]);
            $link = route('newsletter.confirm', $sub->token);
            Mail::raw(__('Confirmez votre inscription à la newsletter Vwajèn : :link', ['link' => $link]), fn ($m) => $m->to($sub->email)->subject(__('Confirmez votre inscription')));
        }

        return back()->with('status', __('Vérifiez votre boîte e-mail pour confirmer votre inscription.'));
    }

    public function confirm(string $token)
    {
        NewsletterSubscriber::where('token', $token)->firstOrFail()->update(['confirmed_at' => now(), 'unsubscribed_at' => null]);

        return redirect()->route('home')->with('status', __('Inscription à la newsletter confirmée.'));
    }

    public function unsubscribe(string $token)
    {
        NewsletterSubscriber::where('token', $token)->firstOrFail()->update(['unsubscribed_at' => now()]);

        return redirect()->route('home')->with('status', __('Vous êtes désinscrit de la newsletter.'));
    }
}
