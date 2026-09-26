<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withTrashed()->with('roles')
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('username', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")))
            ->when($request->query('status'), fn ($q, $s) => $s === 'deleted' ? $q->onlyTrashed() : $q->where('status', $s))
            ->when($request->query('type'), fn ($q, $t) => $q->where('account_type', $t))
            ->when($request->query('verified') !== null && $request->query('verified') !== '', fn ($q) => $q->where('is_verified', $request->boolean('verified')))
            ->when($request->query('role'), fn ($q, $r) => $q->whereHas('roles', fn ($x) => $x->where('name', $r)))
            ->when($request->boolean('unverified_email'), fn ($q) => $q->whereNull('email_verified_at'))
            ->when($request->boolean('deletion'), fn ($q) => $q->whereNotNull('deletion_requested_at'))
            ->orderBy($request->query('sort') === 'followers' ? 'followers_count' : 'created_at', 'desc')
            ->paginate(30)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::orderBy('name')->get()]);
    }

    public function show(int $id)
    {
        $user = User::withTrashed()->with(['roles', 'candidateProfile', 'organizationProfile', 'officialProfile', 'devices', 'verificationRequests'])->findOrFail($id);
        $sanctions = $user->sanctions()->with('moderator')->latest()->get();
        $reportsAgainst = Report::where('reported_user_id', $user->id)->latest()->limit(20)->get();
        $sessions = DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')->get();
        $audit = AuditLog::where(fn ($q) => $q->where('subject_type', 'user')->where('subject_id', $user->id))->orWhere('user_id', $user->id)
            ->with('user')->latest('id')->limit(30)->get();
        $counts = [
            'posts' => $user->posts()->withTrashed()->count(), 'videos' => $user->videos()->count(), 'comments' => Comment::where('user_id', $user->id)->count(),
            'reports_made' => Report::where('reporter_id', $user->id)->count(),
        ];

        return view('admin.users.show', ['user' => $user, 'sanctions' => $sanctions, 'reportsAgainst' => $reportsAgainst, 'sessions' => $sessions,
            'audit' => $audit, 'counts' => $counts, 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(Request $request, int $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'username' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_]+$/', 'unique:users,username,'.$user->id],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'account_type' => ['required', 'in:'.implode(',', User::ACCOUNT_TYPES)],
            'email_verified' => ['nullable', 'boolean'],
            'phone_verified' => ['nullable', 'boolean'],
        ]);
        $user->forceFill([
            'name' => $data['name'], 'username' => strtolower($data['username']), 'email' => strtolower($data['email']), 'account_type' => $data['account_type'],
            'email_verified_at' => $request->boolean('email_verified') ? ($user->email_verified_at ?? now()) : null,
            'phone_verified_at' => $request->boolean('phone_verified') ? ($user->phone_verified_at ?? now()) : null,
        ])->save();
        AuditLogger::log('admin.user.update', $user, $user->getChanges());

        return back()->with('status', __('Utilisateur mis à jour.'));
    }

    public function roles(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $roleIds = $request->validate(['roles' => ['nullable', 'array'], 'roles.*' => ['exists:roles,id']])['roles'] ?? [];
        $superadmin = Role::where('name', 'superadmin')->value('id');
        if (in_array($superadmin, $roleIds) || $user->hasRole('superadmin')) {
            abort_unless($request->user()->hasRole('superadmin'), 403, __('Seul un super-administrateur peut gérer ce rôle.'));
        }
        abort_if($user->id === $request->user()->id && $user->hasRole('superadmin') && ! in_array($superadmin, $roleIds), 422, __('Vous ne pouvez pas retirer votre propre rôle de super-administrateur.'));
        $before = $user->roles()->pluck('name')->all();
        $user->roles()->sync($roleIds);
        AuditLogger::log('admin.user.roles', $user, ['before' => $before, 'after' => $user->roles()->pluck('name')->all()]);

        return back()->with('status', __('Rôles mis à jour.'));
    }

    public function logoutEverywhere(int $id)
    {
        $user = User::findOrFail($id);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->forceFill(['remember_token' => null])->save();
        AuditLogger::log('admin.user.logout', $user);

        return back()->with('status', __('Toutes les sessions de l\'utilisateur ont été fermées.'));
    }

    public function destroy(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        abort_if($user->hasRole('superadmin') || $user->id === $request->user()->id, 403);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->delete();
        AuditLogger::log('admin.user.delete', $user);

        return redirect()->route('admin.users.index')->with('status', __('Compte supprimé (restaurable).'));
    }

    public function restore(int $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        AuditLogger::log('admin.user.restore', $user);

        return back()->with('status', __('Compte restauré.'));
    }
}
