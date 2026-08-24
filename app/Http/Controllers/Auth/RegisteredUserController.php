<?php

declare(strict_types=1);

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
     *
     * Registration is available only during initial system bootstrap.
     */
    public function create(): View
    {
        $this->ensureRegistrationAllowed();

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * The first registered user becomes Super Admin.
     * Public registration is permanently blocked once a user exists.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureRegistrationAllowed();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:' . User::class,
            ],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Bootstrap First User
        |--------------------------------------------------------------------------
        |
        | This check is intentionally performed again immediately before
        | creating the user. The registration endpoint must never rely only
        | on the GET request being protected.
        |
        */
        $isFirstUser = User::query()->doesntExist();

        if (! $isFirstUser) {
            abort(403, 'Public registration is disabled.');
        }

        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->lower()->toString(),
            'password' => Hash::make(
                $request->string('password')->toString()
            ),
            'role' => 'Admin',
            'status' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Assign Super Admin Role
        |--------------------------------------------------------------------------
        */
        $user->syncRoles(['super-admin']);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard.index');
    }

    /**
     * Ensure public registration is still available.
     *
     * Registration is allowed only when the database contains no users.
     */
    private function ensureRegistrationAllowed(): void
    {
        if (User::query()->exists()) {
            abort(403, 'Public registration is disabled.');
        }
    }
}