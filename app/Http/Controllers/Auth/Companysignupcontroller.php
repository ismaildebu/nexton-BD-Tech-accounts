<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreCompanySignupRequest;
use App\Models\Account;
use App\Models\AccountTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CompanySignupController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function create(): View
    {
        $templates = AccountTemplate::where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('auth.company-signup', compact('templates'));
    }

    public function store(StoreCompanySignupRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $admin = DB::transaction(function () use ($validated) {

            $company = Company::create([
                'company_name'  => $validated['company_name'],
                'business_type' => $validated['business_type'],
            ]);

            if (!empty($validated['accounts'])) {
                $templates = AccountTemplate::whereIn('id', $validated['accounts'])->get();

                foreach ($templates as $template) {
                    Account::create([
                        'company_id'      => $company->id,
                        'account_code'    => $template->account_code,
                        'account_name'    => $template->account_name,
                        'account_type'    => $template->account_type,
                        'parent_id'       => null,
                        'nature'          => $template->nature ?? Account::NATURE_GENERAL,
                        'level'           => $template->level ?? 1,
                        'is_system'       => false,
                        'is_active'       => true,
                        'opening_balance' => 0.00,
                        'balance_type'    => Account::defaultBalanceType($template->account_type),
                    ]);
                }
            }

            $admin = User::create([
                'name'       => $validated['admin_name'],
                'email'      => $validated['admin_email'],
                'password'   => Hash::make($validated['admin_password']),
                'role'       => 'Admin',
                'status'     => true,
                'company_id' => $company->id,
            ]);

            // ✅ Email verify করুন যাতে dashboard-এ redirect হয়
            $admin->markEmailAsVerified();

            $admin->syncRoles(['admin']);
            $company->update(['owner_id' => $admin->id]);

            $this->subscriptionService->subscribeToFreePlan($admin);

            return $admin;
        });

        Auth::login($admin);

        return redirect()->route('dashboard.index')
            ->with('success', 'Your company has been created successfully!');
    }
}