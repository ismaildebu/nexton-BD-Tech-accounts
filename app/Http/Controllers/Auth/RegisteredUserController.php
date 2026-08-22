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
        | On a fresh installation the first registered user becomes the
        | Super Admin automatically.
        |
        */
        $isFirstUser = User::query()->doesntExist();

        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->lower()->toString(),
            'password' => Hash::make($request->string('password')->toString()),
            'role' => $isFirstUser ? 'Admin' : 'Accountant',
            'status' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Assign Spatie Role
        |--------------------------------------------------------------------------
        |
        | The first user becomes Super Admin.
        | Other users receive the role mapped from the legacy role column.
        |
        */
        if ($isFirstUser) {
            $user->syncRoles(['super-admin']);
        } else {
            $user->syncLegacyRole();
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect('/dashboard');
    }
}