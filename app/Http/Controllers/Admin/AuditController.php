<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/** Journal d'audit des actions administratives. */
class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', 'like', $a.'%'))
            ->when($request->query('user'), fn ($q, $u) => $q->whereHas('user', fn ($x) => $x->where('username', ltrim($u, '@'))))
            ->when($request->query('from'), fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->where('created_at', '<=', $d.' 23:59:59'))
            ->latest('id')->paginate(50)->withQueryString();
        $actions = AuditLog::selectRaw("SUBSTRING_INDEX(action, '.', 1) a")->distinct()->pluck('a');

        return view('admin.audit', compact('logs', 'actions'));
    }
}
