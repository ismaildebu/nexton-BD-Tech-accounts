<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Super Admin can assign any of these roles.
     * 'Admin' is intentionally excluded — Admins are only created
     * automatically when a new Company is created by Super Admin.
     *
     * @var array<int, string>
     */
    private const SUPER_ADMIN_ROLES = ['Manager', 'Accountant', 'Cashier', 'Sales', 'Auditor', 'Viewer'];

    /**
     * Company Admin can create users for their own company with these roles.
     *
     * @var array<int, string>
     */
    private const ADMIN_ROLES = ['Manager', 'Accountant', 'Cashier', 'Sales', 'Auditor', 'Viewer'];

    /**
     * Display a listing of the users.
     * Super Admin sees everyone; Admin only sees users in their own company.
     */
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $users = User::query()
            ->when(! $authUser->isSuperAdmin(), function ($query) use ($authUser) {
                $query->where('company_id', $authUser->company_id);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('system.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     * Admin's company is fixed; Super Admin picks from a list.
     */
    public function create(Request $request): View
    {
        $authUser = $request->user();
        $roles    = $authUser->isSuperAdmin() ? self::SUPER_ADMIN_ROLES : self::ADMIN_ROLES;

        $companies = $authUser->isSuperAdmin()
            ? Company::orderBy('company_name')->get()
            : collect();

        return view('system.users.create', compact('roles', 'companies'));
    }

    /**
     * Store a newly created user in storage.
     * Admin can only create users for their OWN company — the submitted
     * company_id (if any) is ignored for non-super-admins.
     */
    public function store(Request $request): RedirectResponse
    {
        $authUser     = $request->user();
        $allowedRoles = $authUser->isSuperAdmin() ? self::SUPER_ADMIN_ROLES : self::ADMIN_ROLES;

        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role'     => ['required', 'string', Rule::in($allowedRoles)],
            'status'   => ['nullable', 'boolean'],
        ];

        if ($authUser->isSuperAdmin()) {
            $rules['company_id'] = ['nullable', 'exists:companies,id'];
        }

        $validated = $request->validate($rules);

       $companyId = $authUser->isSuperAdmin()
    ? ($validated['company_id'] ?? null)
    : $authUser->company_id;

                // Plan user limit check
                if ($companyId) {
                    $company = Company::find($companyId);
                    if ($company && $company->owner_id) {
                        $owner       = User::find($company->owner_id);
                        $currentCount = User::where('company_id', $companyId)->count();
                        if ($owner && ! app(\App\Services\PlanLimitService::class)->canUse($owner, 'users', $currentCount)) {
                            abort(403, 'User limit reached for the current plan.');
                        }
                    }
                }

                $user = User::create([

                    'name'       => $validated['name'],
                    'email'      => $validated['email'],
                    'password'   => Hash::make($validated['password']),
                    'role'       => $validated['role'],
                    'status'     => $request->boolean('status'),
                    'company_id' => $companyId,
                ]);

        // Sync Spatie role from the legacy role column so permission
        // checks work immediately for the new user.
        $user->syncLegacyRole();

        return redirect()
            ->route('system.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show role & permission assignment page for a user.
     */
    public function roleAssignment(Request $request, User $user): View
    {
        $this->authorizeUserAccess($request, $user);

        $availableRoles = \App\Models\Role::with('permissions')
            ->when(! $request->user()->isSuperAdmin(), function ($query) {
                // Admin cannot hand out the super-admin role to anyone.
                $query->where('name', '!=', 'super-admin');
            })
            ->orderBy('name')
            ->get();

        $userRoles         = $user->roles;
        $directPermissions = $user->permissions;
        $userPermissions   = $user->getAllPermissions();

        $permissions = \App\Models\Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        $groups = $permissions
            ->pluck('group')
            ->filter()
            ->unique()
            ->values();

        return view('users.roles', compact(
            'user',
            'availableRoles',
            'userRoles',
            'groups',
            'permissions',
            'directPermissions',
            'userPermissions',
        ));
    }

    /**
     * Update the Spatie role for a user.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUserAccess($request, $user);

        $validated = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        // Admin can never grant the super-admin role.
        if (! $request->user()->isSuperAdmin() && $validated['role'] === 'super-admin') {
            abort(403, 'Only Super Admin can grant the super-admin role.');
        }

        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('system.users.roles', $user)
            ->with('success', 'User role updated successfully.');
    }

    /**
     * Display a single user (redirects to edit — no dedicated show view).
     */
    public function show(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUserAccess($request, $user);

        return redirect()->route('system.users.edit', $user);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(Request $request, User $user): View
    {
        $this->authorizeUserAccess($request, $user);

        $authUser = $request->user();
        $roles    = $authUser->isSuperAdmin() ? self::SUPER_ADMIN_ROLES : self::ADMIN_ROLES;

        $companies = $authUser->isSuperAdmin()
            ? Company::orderBy('company_name')->get()
            : collect();

        return view('system.users.edit', compact('user', 'roles', 'companies'));
    }

    /**
     * Update the specified user in storage.
     * Admin cannot move a user to a different company (field is ignored).
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $authUser = $request->user();

        $this->authorizeUserAccess($request, $user);

        $allowedRoles = $authUser->isSuperAdmin() ? self::SUPER_ADMIN_ROLES : self::ADMIN_ROLES;

        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role'     => ['required', 'string', Rule::in($allowedRoles)],
            'status'   => ['nullable', 'boolean'],
        ];

        if ($authUser->isSuperAdmin()) {
            $rules['company_id'] = ['nullable', 'exists:companies,id'];
        }

        $validated = $request->validate($rules);

        $user->name   = $validated['name'];
        $user->email  = $validated['email'];
        $user->role   = $validated['role'];
        $user->status = $request->boolean('status');

        if ($authUser->isSuperAdmin() && array_key_exists('company_id', $validated)) {
            $user->company_id = $validated['company_id'];
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Keep Spatie role in sync after update.
        $user->syncLegacyRole();

        return redirect()
            ->route('system.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUserAccess($request, $user);

        if ($user->id === auth()->id()) {
            return redirect()
                ->route('system.users.index')
                ->with('error', 'You cannot delete your own account while logged in.');
        }

        $user->delete();

        return redirect()
            ->route('system.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Super Admin can manage every user. Admin can only manage users that
     * belong to their own company (never other companies, never Super Admin).
     */
    private function authorizeUserAccess(Request $request, User $user): void
    {
        $authUser = $request->user();

        if ($authUser->isSuperAdmin()) {
            return;
        }

        abort_unless(
            $user->company_id !== null && $user->company_id === $authUser->company_id,
            403,
            'You do not have access to this user.'
        );
    }
}