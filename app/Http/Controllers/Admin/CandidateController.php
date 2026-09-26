<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\OfficialProfile;
use App\Models\OrganizationProfile;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ChangeLogger;
use Illuminate\Http\Request;

/** Gestion des candidats, organisations et responsables élus. */
class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $profiles = CandidateProfile::with('user')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('department'), fn ($q, $d) => $q->where('department', $d))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('full_name', 'like', "%$s%")->orWhere('party', 'like', "%$s%")))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.candidates.index', compact('profiles'));
    }

    public function update(Request $request, CandidateProfile $profile)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,verified,rejected'],
            'full_name' => ['required', 'string', 'max:150'],
            'party' => ['nullable', 'string', 'max:150'],
            'position_sought' => ['required', 'string'],
            'constituency' => ['nullable', 'string', 'max:150'],
            'department' => ['nullable', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $note = $data['note'] ?? __('Correction par l\'administration');
        unset($data['note']);
        $profile->update($data + ['verified_at' => $data['status'] === 'verified' ? ($profile->verified_at ?? now()) : null]);
        ChangeLogger::diff($profile, CandidateProfile::TRACKED, $note);

        $user = $profile->user;
        if ($data['status'] === 'verified' && ! $user->is_verified) {
            $user->forceFill(['is_verified' => true, 'verified_type' => 'candidate', 'verified_at' => now(),
                'verified_until' => now()->addMonths(config('vwajen.verification_months'))])->save();
        } elseif ($data['status'] !== 'verified' && $user->verified_type === 'candidate') {
            $user->forceFill(['is_verified' => false, 'verified_type' => null])->save();
        }
        AuditLogger::log('admin.candidate.update', $profile, $profile->getChanges());

        return back()->with('status', __('Profil candidat mis à jour.'));
    }

    public function organizations(Request $request)
    {
        $orgs = OrganizationProfile::with('user')
            ->when($request->query('type'), fn ($q, $t) => $q->where('org_type', $t))
            ->when($request->query('q'), fn ($q, $s) => $q->where('legal_name', 'like', "%$s%"))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.candidates.organizations', compact('orgs'));
    }

    /** Comptes professionnels (outils avancés pour organisations). */
    public function toggleProfessional(OrganizationProfile $organization)
    {
        $organization->update(['is_professional' => ! $organization->is_professional]);
        AuditLogger::log('admin.organization.professional', $organization->user, ['professional' => $organization->is_professional]);

        return back()->with('status', __('Organisation mise à jour.'));
    }

    public function officials(Request $request)
    {
        $officials = OfficialProfile::with('user')->when($request->query('q'), fn ($q, $s) => $q->where('full_name', 'like', "%$s%"))->latest()->paginate(30)->withQueryString();

        return view('admin.candidates.officials', compact('officials'));
    }

    /** Création / rattachement d'un profil d'élu à un compte existant. */
    public function storeOfficial(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'exists:users,username'],
            'full_name' => ['required', 'string', 'max:150'],
            'office' => ['required', 'string', 'max:200'],
            'institution' => ['nullable', 'string', 'max:200'],
            'constituency' => ['nullable', 'string', 'max:200'],
            'department' => ['nullable', 'string'],
            'party' => ['nullable', 'string', 'max:150'],
            'mandate_start' => ['nullable', 'date'],
            'mandate_end' => ['nullable', 'date'],
        ]);
        $user = User::where('username', $data['username'])->firstOrFail();
        unset($data['username']);
        $profile = OfficialProfile::updateOrCreate(['user_id' => $user->id], $data);
        $user->forceFill(['account_type' => 'official', 'is_verified' => true, 'verified_type' => 'official', 'verified_at' => now(),
            'verified_until' => $profile->mandate_end ?? now()->addMonths(config('vwajen.verification_months'))])->save();
        AuditLogger::log('admin.official.store', $profile);

        return back()->with('status', __('Profil d\'élu enregistré.'));
    }
}
