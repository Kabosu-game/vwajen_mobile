<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Gestion des rôles et des permissions. */
class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles', [
            'roles' => Role::with('permissions')->withCount('users')->orderBy('id')->get(),
            'permissions' => Permission::orderBy('group')->orderBy('name')->get()->groupBy('group'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['label' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'], 'permissions.*' => ['exists:permissions,id']]);
        $role = Role::create(['name' => Str::slug($data['label'], '_'), 'label' => $data['label'], 'description' => $data['description'] ?? null]);
        $role->permissions()->sync($data['permissions'] ?? []);
        AuditLogger::log('role.create', $role, ['permissions' => $role->permissions()->pluck('name')]);

        return back()->with('status', __('Rôle créé.'));
    }

    public function update(Request $request, Role $role)
    {
        abort_if($role->name === 'superadmin', 403, __('Le rôle super-administrateur possède toutes les permissions.'));
        $data = $request->validate(['label' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'], 'permissions.*' => ['exists:permissions,id']]);
        $before = $role->permissions()->pluck('name')->all();
        $role->update(['label' => $data['label'], 'description' => $data['description'] ?? null]);
        $role->permissions()->sync($data['permissions'] ?? []);
        AuditLogger::log('role.update', $role, ['before' => $before, 'after' => $role->permissions()->pluck('name')->all()]);

        return back()->with('status', __('Rôle mis à jour.'));
    }

    public function destroy(Role $role)
    {
        abort_if($role->is_system, 422, __('Les rôles système ne peuvent pas être supprimés.'));
        AuditLogger::log('role.delete', null, ['role' => $role->name]);
        $role->delete();

        return back()->with('status', __('Rôle supprimé.'));
    }

    public function storePermission(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'regex:/^[a-z_]+\.[a-z_]+$/', 'unique:permissions,name'],
            'label' => ['required', 'string', 'max:150'], 'group' => ['required', 'string', 'max:40']]);
        Permission::create($data);
        AuditLogger::log('permission.create', null, $data);

        return back()->with('status', __('Permission créée.'));
    }
}
