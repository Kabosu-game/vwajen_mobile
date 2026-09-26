<?php

namespace App\Http\Controllers;

use App\Models\Appeal;
use App\Models\Sanction;
use Illuminate\Http\Request;

/** Système d'appel des sanctions de modération. */
class AppealController extends Controller
{
    public function restricted(Request $request)
    {
        $user = $request->user();
        if ($user->status === 'active') {
            return redirect()->route('home');
        }
        $sanction = $user->sanctions()->whereIn('type', ['suspension', 'ban'])->whereNull('revoked_at')->latest()->first();

        return view('moderation.restricted', compact('user', 'sanction'));
    }

    public function create(Request $request, Sanction $sanction)
    {
        abort_unless($sanction->user_id === $request->user()->id, 403);

        return view('moderation.appeal', compact('sanction'));
    }

    public function store(Request $request, Sanction $sanction)
    {
        abort_unless($sanction->user_id === $request->user()->id && ! $sanction->revoked_at, 403);
        abort_if($sanction->appeals()->where('status', 'pending')->exists(), 422, __('Un appel est déjà en cours d\'examen.'));
        abort_if($sanction->appeals()->count() >= 2, 422, __('Nombre maximal d\'appels atteint pour cette sanction.'));
        $data = $request->validate(['body' => ['required', 'string', 'min:20', 'max:3000']]);
        Appeal::create(['sanction_id' => $sanction->id, 'user_id' => $request->user()->id, 'body' => $data['body']]);

        return redirect()->route($request->user()->status === 'active' ? 'settings.sanctions' : 'account.restricted')
            ->with('status', __('Votre appel a été envoyé. Vous recevrez une réponse par notification.'));
    }
}
