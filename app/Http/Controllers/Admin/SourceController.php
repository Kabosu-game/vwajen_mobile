<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Source;
use App\Models\SourceVerification;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/** Vérification des sources (statut, historique). */
class SourceController extends Controller
{
    public function index(Request $request)
    {
        $sources = Source::with(['user', 'verifier'])
            ->when($request->query('status', 'unverified'), fn ($q, $s) => $s === 'all' ? $q : ($s === 'pending' ? $q->whereIn('status', ['provided', 'unverified']) : $q->where('status', $s)))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('url', 'like', "%$s%")))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.sources.index', compact('sources'));
    }

    public function update(Request $request, Source $source)
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', Source::STATUSES)], 'note' => ['nullable', 'string', 'max:1000']]);
        $from = $source->status;
        $source->update(['status' => $data['status'], 'note' => $data['note'] ?? $source->note,
            'verified_by' => $request->user()->id, 'verified_at' => $data['status'] === 'verified' ? now() : null]);
        SourceVerification::create(['source_id' => $source->id, 'user_id' => $request->user()->id, 'from_status' => $from, 'to_status' => $data['status'], 'note' => $data['note'] ?? null]);
        AuditLogger::log('source.status', $source, ['from' => $from, 'to' => $data['status']]);

        return back()->with('status', __('Statut de la source mis à jour.'));
    }
}
