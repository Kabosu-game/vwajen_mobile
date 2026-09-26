<?php

namespace App\Http\Controllers;

use App\Models\VerificationRequest;
use Illuminate\Http\Request;

/** Demande de vérification (candidat, organisation, compte public, élu) avec documents justificatifs. */
class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $requests = $user->verificationRequests()->with('documents')->latest()->get();

        return view('verification.index', compact('user', 'requests'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_if($user->verificationRequests()->where('status', 'pending')->exists(), 422, __('Une demande est déjà en cours.'));

        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', VerificationRequest::TYPES)],
            'message' => ['required', 'string', 'max:2000'],
            'documents' => ['required', 'array', 'min:1', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'document_labels' => ['nullable', 'array'],
            'document_labels.*' => ['nullable', 'string', 'max:100'],
            // Organisation
            'legal_name' => ['required_if:type,organization', 'nullable', 'string', 'max:200'],
            'org_type' => ['required_if:type,organization', 'nullable', 'in:party,ngo,media,government,association,company,other'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            // Élu
            'office' => ['required_if:type,official', 'nullable', 'string', 'max:200'],
            'institution' => ['nullable', 'string', 'max:200'],
            'constituency' => ['nullable', 'string', 'max:200'],
        ]);

        if ($data['type'] === 'candidate' && ! $user->candidateProfile) {
            return redirect()->route('candidates.register');
        }
        if ($data['type'] === 'organization') {
            $user->organizationProfile()->updateOrCreate([], [
                'legal_name' => $data['legal_name'], 'org_type' => $data['org_type'],
                'registration_number' => $data['registration_number'] ?? null, 'website' => $user->website,
            ]);
            $user->forceFill(['account_type' => 'organization'])->save();
        }
        if ($data['type'] === 'official') {
            $user->officialProfile()->updateOrCreate([], [
                'full_name' => $user->name, 'office' => $data['office'], 'institution' => $data['institution'] ?? null,
                'constituency' => $data['constituency'] ?? null, 'department' => $user->department,
            ]);
            $user->forceFill(['account_type' => 'official'])->save();
        }

        $vr = VerificationRequest::create(['user_id' => $user->id, 'type' => $data['type'], 'message' => $data['message']]);
        foreach ($request->file('documents') as $i => $file) {
            $vr->documents()->create([
                'label' => $data['document_labels'][$i] ?? __('Document justificatif'),
                'path' => $file->store('verifications/'.$user->id, 'local'),
                'mime' => $file->getMimeType(),
            ]);
        }

        return back()->with('status', __('Demande envoyée. Vous serez notifié de la décision.'));
    }

    public function cancel(Request $request, VerificationRequest $verification)
    {
        abort_unless($verification->user_id === $request->user()->id && $verification->status === 'pending', 403);
        $verification->update(['status' => 'cancelled']);

        return back()->with('status', __('Demande annulée.'));
    }
}
