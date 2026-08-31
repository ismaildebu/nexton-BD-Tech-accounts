<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

/**
 * Validates the public "create your own company" self-signup form.
 * Anyone may submit this (route is in the 'guest' middleware group).
 */
class StoreCompanySignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
    return [
        'company_name' => ['required', 'string', 'max:255'],
        'business_type' => ['required', 'string'],

        'admin_name' => ['required', 'string', 'max:255'],
        'admin_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class . ',email'],
        'admin_password' => ['required', 'confirmed', Rules\Password::defaults()],

        // নতুন: accounts selection
            'accounts' => ['nullable', 'array'],
            'accounts.*' => ['integer', 'exists:account_templates,id'],
    ];
    }
}