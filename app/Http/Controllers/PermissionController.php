<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Permission registry — read-only list grouped by module, plus
 * creation of new module permissions. Only users with
 * `permissions.manage` may reach this controller.
 */
class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Permission::query();

        if ($request->filled('group')) {
            $query->where('group', $request->input('group'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        $permissions = $query->orderBy('group')->orderBy('name')->get();

        $groups = Permission::select('group')
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->all();

        $groupsWithCounts = array_map(
            static fn (string $g) => [
                'name' => $g,
                'count' => Permission::where('group', $g)->count(),
            ],
            $groups,
        );

        return view('permissions.index', compact(
            'permissions',
            'groups',
            'groupsWithCounts',
        ));
    }

    public function create(): View
    {
        $groups = Permission::select('group')
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->all();

        return view('permissions.create', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:125',
                'regex:/^[a-z][a-z0-9\-\.]+$/',
                'unique:permissions,name',
            ],
            'group' => ['nullable', 'string', 'max:100'],
        ]);

        Permission::create([
            'name' => strtolower($validated['name']),
            'guard_name' => 'web',
            'group' => $validated['group'] ?: null,
        ]);

        return redirect()
            ->route('system.permissions.index')
            ->with('success', 'Permission created successfully.');
    }
}
