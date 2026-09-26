<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Vérification des candidats, organisations, comptes publics et élus : validation, rejet, expiration, historique. */
class VerificationController extends Controller
{
    public function __construct(private Notifier $notifier) {}

    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $requests = VerificationRequest::with(['user', 'reviewer'])->withCount('documents')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->latest()->paginate(30)->withQueryString();
        $expiring = User::where('is_verified', true)->whereNotNull('verified_until')->where('verified_until', '<=', now()->addDays(30))->orderBy('verified_until')->limit(20)->get();

        return view('admin.verifications.index', compact('requests', 'status', 'expiring'));
    }

    public function show(VerificationRequest $verification)
    {
        $verification->load(['user.candidateProfile', 'user.organizationProfile', 'user.officialProfile', 'documents', 'reviewer']);
        $history = VerificationRequest::where('user_id', $verification->user_id)->with('reviewer')->latest()->get();

        return view('admin.verifications.show', compact('verification', 'history'));
    }

    public function approve(Request $request, VerificationRequest $verification)
    {
        abort_unless($verification->status === 'pending', 422);
        $data = $request->validate(['months' => ['required', 'integer', 'min:1', 'max:60'], 'note' => ['nullable', 'string', 'max:1000']]);
        $expires = now()->addMonths((int) $data['months']);

        $verification->update(['status' => 'approved', 'reviewer_id' => $request->user()->id, 'review_note' => $data['note'] ?? null,
            'reviewed_at' => now(), 'expires_at' => $expires]);
        $user = $verification->user;
        $user->forceFill(['is_verified' => true, 'verified_type' => $verification->type, 'verified_at' => now(), 'verified_until' => $expires])->save();

        match ($verification->type) {
            'candidate' => $user->candidateProfile?->update(['status' => 'verified', 'verified_at' => now()]),
            'organization' => $user->organizationProfile?->update(['status' => 'verified']),
            default => null,
        };

        AuditLogger::log('verification.approve', $verification, ['user_id' => $user->id, 'expires_at' => $expires->toIso8601String()]);
        $this->notifier->send($user, 'system', null, 'Votre compte est vérifié ! Badge valable jusqu\'au :date', ['date' => $expires->format('d/m/Y')], $user->profileUrl(), $verification);

        return redirect()->route('admin.verifications.index')->with('status', __('Vérification validée.'));
    }

    public function reject(Request $request, VerificationRequest $verification)
    {
        abort_unless($verification->status === 'pending', 422);
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $verification->update(['status' => 'rejected', 'reviewer_id' => $request->user()->id, 'review_note' => $data['note'], 'reviewed_at' => now()]);
        if ($verification->type === 'candidate') {
            $verification->user->candidateProfile?->update(['status' => 'rejected']);
        }
        AuditLogger::log('verification.reject', $verification, ['note' => $data['note']]);
        $this->notifier->send($verification->user, 'system', null, 'Votre demande de vérification a été refusée : :reason', ['reason' => $data['note']], route('verification.index'), $verification);

        return redirect()->route('admin.verifications.index')->with('status', __('Demande rejetée.'));
    }

    /** Téléchargement sécurisé d'un document justificatif (disque privé). */
    public function document(VerificationDocument $document)
    {
        AuditLogger::log('verification.document_view', $document->request, ['document' => $document->id]);

        return Storage::disk('local')->download($document->path, $document->label.'.'.pathinfo($document->path, PATHINFO_EXTENSION));
    }

    public function revoke(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate(['note' => ['required', 'string', 'max:500']]);
        $user->forceFill(['is_verified' => false, 'verified_type' => null, 'verified_until' => null])->save();
        VerificationRequest::where('user_id', $user->id)->where('status', 'approved')->update(['status' => 'expired', 'review_note' => $data['note']]);
        AuditLogger::log('verification.revoke', $user, ['note' => $data['note']]);
        $this->notifier->send($user, 'system', null, 'Votre badge vérifié a été retiré : :reason', ['reason' => $data['note']], route('verification.index'));

        return back()->with('status', __('Badge retiré.'));
    }
}
