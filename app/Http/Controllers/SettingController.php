<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Display company settings.
     */
    public function index(Request $request): View
    {
        $this->authorizeSettings($request, 'settings.view');

        $company = $this->currentCompany();

        return view('settings.index', [
            'company' => $company,
        ]);
    }

    /**
     * Update company settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorizeSettings($request, 'settings.manage');

        $company = $this->currentCompany();

        $validated = $request->validate([
            'company_name' => [
                'required',
                'string',
                'max:255',
            ],

            'owner_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'country' => [
                'nullable',
                'string',
                'max:100',
            ],

            'currency' => [
                'required',
                'string',
                'max:10',
            ],

            'currency_symbol' => [
                'required',
                'string',
                'max:10',
            ],
        ]);

        DB::transaction(function () use ($company, $validated): void {
            $company->update($validated);
        });

        return redirect()
            ->route('settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Get the active company from the existing company context.
     */
    private function currentCompany(): Company
    {
        $company = app()->bound('currentCompany')
            ? app('currentCompany')
            : null;

        abort_unless($company instanceof Company, 404);

        return $company;
    }

    /**
     * Check Settings permission.
     */
    private function authorizeSettings(
        Request $request,
        string $permission
    ): void {
        abort_unless(
            $request->user()?->can($permission),
            403,
            'You do not have permission to access settings.'
        );
    }
}