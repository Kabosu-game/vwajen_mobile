<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Follow;
use App\Models\Report;
use App\Services\ModerationService;
use App\Support\Morph;
use Illuminate\Http\Request;

/** Signaler un utilisateur, une publication, une vidéo, un commentaire, un live, une question, un événement… */
class ReportController extends Controller
{
    public function create(string $type, int $id)
    {
        $target = Morph::findOrFail($type, $id, Morph::REPORTABLE);

        return view('social.report', ['target' => $target, 'type' => $type, 'reasons' => Report::REASONS]);
    }

    public function store(Request $request, string $type, int $id, ModerationService $moderation)
    {
        $target = Morph::findOrFail($type, $id, Morph::REPORTABLE);
        $data = $request->validate([
            'reason' => ['required', 'in:'.implode(',', Report::REASONS)],
            'details' => ['nullable', 'string', 'max:1000'],
            'block' => ['nullable', 'boolean'],
        ]);
        $user = $request->user();

        $already = Report::where('reporter_id', $user->id)->where('reportable_type', $type)->where('reportable_id', $id)
            ->whereIn('status', ['pending', 'reviewing'])->exists();
        if (! $already) {
            $moderation->createReport($user, $target, $data['reason'], $data['details'] ?? null);
        }

        if (! empty($data['block'])) {
            $owner = $moderation->ownerOf($target);
            if ($owner && $owner->id !== $user->id) {
                Block::firstOrCreate(['blocker_id' => $user->id, 'blocked_id' => $owner->id]);
                Follow::where(fn ($q) => $q->where('follower_id', $user->id)->where('following_id', $owner->id))
                    ->orWhere(fn ($q) => $q->where('follower_id', $owner->id)->where('following_id', $user->id))->delete();
            }
        }

        $message = __('Merci. Notre équipe de modération va examiner ce signalement.');
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->to($request->input('return') ?: url('/'))->with('status', $message);
    }
}
