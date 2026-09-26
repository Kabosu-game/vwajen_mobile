<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Models\SourceVerification;
use App\Support\Morph;
use Illuminate\Http\Request;

/** Système de sources : ajout, affichage, historique de vérification. */
class SourceController extends Controller
{
    public const SOURCEABLE = ['candidate', 'program', 'proposal', 'commitment', 'record', 'official'];

    public function show(Source $source)
    {
        $source->load(['verifications.user', 'user', 'verifier']);

        return view('sources.show', ['source' => $source, 'subject' => $source->sourceable]);
    }

    public function store(Request $request, string $type, int $id)
    {
        $subject = Morph::findOrFail($type, $id, self::SOURCEABLE);
        $user = $request->user();
        $ownerId = $subject->user_id ?? $subject->version?->program?->user_id;
        $isOwner = $ownerId === $user->id;
        abort_unless($isOwner || $user->hasPermission('sources.verify'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'url' => ['nullable', 'url', 'max:2048'],
            'published_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $source = $subject->sources()->create($data + [
            'user_id' => $user->id,
            'status' => $isOwner ? 'provided' : 'unverified',
            'provided_by_candidate' => $isOwner,
        ]);
        SourceVerification::create(['source_id' => $source->id, 'user_id' => $user->id, 'from_status' => null, 'to_status' => $source->status, 'note' => __('Source ajoutée')]);

        return back()->with('status', __('Source ajoutée.'));
    }

    public function destroy(Request $request, Source $source)
    {
        $user = $request->user();
        abort_unless($source->user_id === $user->id && $source->status !== 'verified' || $user->hasPermission('sources.verify'), 403);
        $source->delete();

        return back()->with('status', __('Source supprimée.'));
    }
}
