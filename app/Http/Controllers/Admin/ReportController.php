<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ModerationService;
use App\Services\Notifier;
use Illuminate\Http\Request;

/** File de modération des signalements. */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $reports = Report::with(['reporter', 'reportedUser', 'handler'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->query('reason'), fn ($q, $r) => $q->where('reason', $r))
            ->when($request->query('type'), fn ($q, $t) => $q->where('reportable_type', $t))
            ->orderByRaw("FIELD(priority, 'high', 'normal', 'low')")->orderByDesc('ai_score')->oldest()
            ->paginate(30)->withQueryString();
        $counts = Report::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return view('admin.reports.index', compact('reports', 'status', 'counts'));
    }

    public function show(Report $report)
    {
        if ($report->status === 'pending') {
            $report->update(['status' => 'reviewing', 'handled_by' => auth()->id()]);
        }
        $target = $report->target();
        $related = Report::where('reportable_type', $report->reportable_type)->where('reportable_id', $report->reportable_id)
            ->where('id', '!=', $report->id)->with('reporter')->latest()->get();
        $owner = $target ? app(ModerationService::class)->ownerOf($target) : null;
        $history = $owner ? $owner->sanctions()->latest()->get() : collect();

        return view('admin.reports.show', compact('report', 'target', 'related', 'owner', 'history'));
    }

    /** Décision : classer sans suite, masquer, supprimer, avertir, suspendre, bannir. */
    public function resolve(Request $request, Report $report, ModerationService $moderation, Notifier $notifier)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:dismiss,hide,delete,warning,suspension,ban'],
            'reason' => ['required', 'string', 'max:1000'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'apply_all' => ['nullable', 'boolean'],
        ]);
        $mod = $request->user();
        $target = $report->target();
        $owner = $target ? $moderation->ownerOf($target) : null;

        switch ($data['decision']) {
            case 'hide':
                $target && $moderation->hide($target, $mod, $data['reason'], $report);
                break;
            case 'delete':
                $target && $moderation->remove($target, $mod, $data['reason'], $report);
                break;
            case 'warning':
                $moderation->sanction($owner, $mod, 'warning', $data['reason'], null, $target, $report);
                break;
            case 'suspension':
                $moderation->sanction($owner, $mod, 'suspension', $data['reason'], now()->addDays((int) ($data['days'] ?? 7)), $target, $report);
                break;
            case 'ban':
                $moderation->sanction($owner, $mod, 'ban', $data['reason'], null, $target, $report);
                break;
        }

        $status = $data['decision'] === 'dismiss' ? 'dismissed' : 'resolved';
        $query = $request->boolean('apply_all')
            ? Report::where('reportable_type', $report->reportable_type)->where('reportable_id', $report->reportable_id)->whereIn('status', ['pending', 'reviewing'])
            : Report::whereKey($report->id);
        $reporters = (clone $query)->pluck('reporter_id')->filter()->unique();
        $query->update(['status' => $status, 'handled_by' => $mod->id, 'handled_at' => now(), 'resolution' => $data['decision'].': '.$data['reason']]);
        AuditLogger::log('report.'.$status, $report, ['decision' => $data['decision']]);

        foreach (User::whereIn('id', $reporters)->get() as $r) {
            $notifier->send($r, 'moderation', null, $status === 'resolved' ? 'Merci : votre signalement a entraîné une action de modération.' : 'Votre signalement a été examiné. Aucune infraction n\'a été retenue.',
                [], route('home'), $report);
        }

        return redirect()->route('admin.reports.index')->with('status', __('Signalement traité.'));
    }
}
