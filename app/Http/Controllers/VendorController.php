<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\Vendor;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
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
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $this->enforcePlanLimit(
            $this->planLimitService,
            session('company_id'),
            'vendors',
            Vendor::where('company_id', session('company_id'))->count(),
        );

        Vendor::create([
            'company_id'      => session('company_id'),
            'name'            => $request->name,
            'phone'           => $request->phone,
            'email'           => $request->email,
            'address'         => $request->address,
            'trade_license'   => $request->trade_license,
            'tin'             => $request->tin,
            'opening_balance' => $request->opening_balance ?? 0,
            'balance_type'    => $request->balance_type ?? 'Payable',
            'is_active'       => true,
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

        return view('vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $this->authorizeCompany($vendor);

        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $vendor->update($request->only([
            'name', 'phone', 'email', 'address',
            'trade_license', 'tin', 'opening_balance', 'balance_type'
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