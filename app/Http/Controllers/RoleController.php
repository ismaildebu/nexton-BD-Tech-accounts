<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Role management — CRUD for roles and role↔permission assignment.
 * Only users with `roles.manage` permission may reach this controller.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount('permissions')->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        $groups = Permission::select('group')
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->all();

        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        return view('roles.create', compact('groups', 'permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:125',
                'regex:/^[a-z][a-z0-9_\-]+$/',
                Rule::unique('roles', 'name'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => strtolower($validated['name']),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(
            $validated['permissions'] ?? [],
        );

        return redirect()
            ->route('system.roles.show', $role)
            ->with('success', 'Role created successfully.');
    }

    public function show(Role $role): View
    {
        $role->load('permissions');

        $members = User::role($role->name)->count();

        $groups = Permission::select('group')
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->all();

        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        return view('roles.show', compact('role', 'members', 'groups', 'permissions'));
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        $groups = Permission::select('group')
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->all();

        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        return view('roles.edit', compact('role', 'groups', 'permissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:125',
                'regex:/^[a-z][a-z0-9_\-]+$/',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => strtolower($validated['name'])]);

        $role->syncPermissions(
            $validated['permissions'] ?? [],
        );

        return redirect()
            ->route('system.roles.show', $role)
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        // Protect seed roles — they can be edited but not removed.
        $seedRoles = ['admin', 'manager', 'accountant', 'cashier', 'sales', 'auditor', 'viewer'];

        if (in_array($role->name, $seedRoles, true)) {
            return redirect()
                ->route('system.roles.index')
                ->with('error', 'System seed roles cannot be deleted.');
        }

        $role->delete();

        return redirect()
            ->route('system.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
