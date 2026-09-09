<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\Account;
use App\Models\Vendor;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VendorController extends Controller
{
    use EnforcesPlanLimits;

    public function __construct(
        private readonly PlanLimitService $planLimitService,
    ) {
    }

    public function index()
    {
        $company_id = session('company_id');

        $vendors = Vendor::where('company_id', $company_id)
            ->orderBy('name')
            ->get();

        return view('vendors.index', compact('vendors'));
    }

    public function create()
    {
        $companyId = session('company_id');

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('account_name')
            ->get();

        return view('vendors.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ]);

        $this->enforcePlanLimit(
            $this->planLimitService,
            $companyId,
            'vendors',
            Vendor::where('company_id', $companyId)->count(),
        );

        Vendor::create([
            'company_id' => $companyId,
            'account_id' => $request->account_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'trade_license' => $request->trade_license,
            'tin' => $request->tin,
            'opening_balance' => $request->opening_balance ?? 0,
            'balance_type' => $request->balance_type ?? 'Payable',
            'is_active' => true,
        ]);

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor created successfully!');
    }

    public function show(Vendor $vendor)
    {
        $this->authorizeCompany($vendor);

        $vendor->load('purchaseOrders', 'purchaseBills');

        return view('vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor)
    {
        $this->authorizeCompany($vendor);

        $companyId = session('company_id');

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('account_name')
            ->get();

        return view('vendors.edit', compact('vendor', 'accounts'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $this->authorizeCompany($vendor);

        $companyId = session('company_id');

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ]);

        $vendor->update($request->only([
            'account_id',
            'name',
            'phone',
            'email',
            'address',
            'trade_license',
            'tin',
            'opening_balance',
            'balance_type',
        ]));

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor updated!');
    }

    public function destroy(Vendor $vendor)
    {
        $this->authorizeCompany($vendor);

        $vendor->delete();

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor deleted!');
    }

    /**
     * Guard against IDOR: a vendor from another company must never
     * be viewable/editable/deletable via the currently selected company.
     */
    private function authorizeCompany(Vendor $vendor): void
    {
        if ((int) $vendor->company_id !== (int) session('company_id')) {
            throw new NotFoundHttpException();
        }
    }
}