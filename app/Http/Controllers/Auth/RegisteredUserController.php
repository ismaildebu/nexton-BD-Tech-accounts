<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Bootstrap: the very first person to ever register on a fresh
        // install has no one to grant them access, so they become the
        // Super Admin automatically. Everyone after that gets the
        // ordinary default role and must be promoted by an admin.
        $isFirstUser = User::query()->doesntExist();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $isFirstUser ? 'Admin' : 'Accountant',
        ]);

        if ($isFirstUser) {
            $user->assignRoleIfExists('super-admin');
        }

        // Always sync the spatie role from the legacy `role` column so
        // new users are never left with zero permissions.
        $user->syncLegacyRole();

        event(new Registered($user));

        Auth::login($user);

        return redirect('/dashboard');
    }
}