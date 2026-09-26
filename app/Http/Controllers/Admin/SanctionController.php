<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appeal;
use App\Models\Sanction;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ModerationService;
use App\Services\Notifier;
use Illuminate\Http\Request;

/** Avertissements, suspensions, bannissements, historique des sanctions et appels. */
class SanctionController extends Controller
{
    public function index(Request $request)
    {
        $sanctions = Sanction::with(['user', 'moderator', 'revokedBy'])
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->query('active'), fn ($q) => $q->whereNull('revoked_at')->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->when($request->query('user'), fn ($q, $u) => $q->whereHas('user', fn ($x) => $x->where('username', ltrim($u, '@'))))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.sanctions.index', compact('sanctions'));
    }

    public function store(Request $request, int $id, ModerationService $moderation)
    {
        $user = User::findOrFail($id);
        abort_if($user->isStaff() && ! $request->user()->isAdmin(), 403);
        abort_if($user->hasRole('superadmin'), 403);
        $data = $request->validate([
            'type' => ['required', 'in:warning,suspension,ban'],
            'reason' => ['required', 'string', 'max:1000'],
            'days' => ['nullable', 'required_if:type,suspension', 'integer', 'min:1', 'max:3650'],
        ]);
        $moderation->sanction($user, $request->user(), $data['type'], $data['reason'], $data['type'] === 'suspension' ? now()->addDays((int) $data['days']) : null);

        return back()->with('status', __('Sanction appliquée.'));
    }

    public function revoke(Sanction $sanction, ModerationService $moderation)
    {
        $moderation->revoke($sanction, auth()->user());

        return back()->with('status', __('Sanction levée.'));
    }

    public function appeals(Request $request)
    {
        $appeals = Appeal::with(['user', 'sanction.moderator', 'reviewer'])
            ->when($request->query('status', 'pending'), fn ($q, $s) => $s === 'all' ? $q : $q->where('status', $s))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.sanctions.appeals', compact('appeals'));
    }

    public function decideAppeal(Request $request, Appeal $appeal, ModerationService $moderation, Notifier $notifier)
    {
        abort_unless($appeal->status === 'pending', 422);
        abort_if($appeal->sanction->moderator_id === $request->user()->id && ! $request->user()->isAdmin(), 403, __('Un autre modérateur doit examiner cet appel.'));
        $data = $request->validate(['decision' => ['required', 'in:accepted,rejected'], 'response' => ['required', 'string', 'max:2000']]);
        $appeal->update(['status' => $data['decision'], 'response' => $data['response'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        if ($data['decision'] === 'accepted') {
            $moderation->revoke($appeal->sanction, $request->user());
        }
        AuditLogger::log('appeal.'.$data['decision'], $appeal->sanction);
        $notifier->send($appeal->user, 'moderation', null, $data['decision'] === 'accepted' ? 'Votre appel a été accepté : :response' : 'Votre appel a été rejeté : :response',
            ['response' => $data['response']], route('settings.sanctions'), $appeal->sanction);

        return back()->with('status', __('Décision enregistrée.'));
    }
}
