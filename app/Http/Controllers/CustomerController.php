<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\Customer;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerController extends Controller
{
    use EnforcesPlanLimits;

    public function __construct(
        private readonly PlanLimitService $planLimitService,
    ) {
    }

    public function index()
    {
        $company_id = session('company_id');

        $customers = Customer::where('company_id', $company_id)
            ->withCount('invoices')
            ->orderBy('name')
            ->get();

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function bulkCreate()
    {
        return view('customers.bulk-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $companyId = (int) session('company_id');

        $this->enforcePlanLimit(
            $this->planLimitService,
            $companyId,
            'customers',
            Customer::where('company_id', $companyId)->count(),
        );

        DB::transaction(function () use ($request, $companyId) {
            $customerCode = $this->generateCustomerCode($companyId);

            Customer::create([
                'company_id'      => $companyId,
                'customer_code'   => $customerCode,
                'name'            => $request->name,
                'phone'           => $request->phone,
                'email'           => $request->email,
                'address'         => $request->address,
                'trade_license'   => $request->trade_license,
                'tin'              => $request->tin,
                'customer_type'   => $request->customer_type ?? 'Individual',
                'credit_limit'    => $request->credit_limit ?? 0,
                'opening_balance' => $request->opening_balance ?? 0,
                'balance_type'    => $request->balance_type ?? 'Receivable',
                'notes'           => $request->notes,
                'is_active'       => true,
            ]);
        });

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully!');
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'customers' => 'required|array|min:1',

            'customers.*.name' => 'required|string|max:255',
            'customers.*.phone' => 'nullable|string|max:20',
            'customers.*.email' => 'nullable|email|max:255',
            'customers.*.customer_type' => 'nullable|in:Individual,Business',
            'customers.*.credit_limit' => 'nullable|numeric|min:0',
            'customers.*.address' => 'nullable|string|max:1000',
            'customers.*.trade_license' => 'nullable|string|max:255',
            'customers.*.tin' => 'nullable|string|max:255',
            'customers.*.opening_balance' => 'nullable|numeric|min:0',
            'customers.*.balance_type' => 'nullable|in:Receivable,Advance',
            'customers.*.notes' => 'nullable|string|max:2000',
        ]);

        $companyId = (int) session('company_id');
        $customers = $request->input('customers', []);
        $customerCount = count($customers);

        $this->enforcePlanLimit(
            $this->planLimitService,
            $companyId,
            'customers',
            Customer::where('company_id', $companyId)->count() + $customerCount,
        );

        DB::transaction(function () use ($customers, $companyId) {
            foreach ($customers as $customer) {
                $customerCode = $this->generateCustomerCode($companyId);

                Customer::create([
                    'company_id'      => $companyId,
                    'customer_code'   => $customerCode,
                    'name'            => $customer['name'],
                    'phone'           => $customer['phone'] ?? null,
                    'email'           => $customer['email'] ?? null,
                    'address'         => $customer['address'] ?? null,
                    'trade_license'   => $customer['trade_license'] ?? null,
                    'tin'              => $customer['tin'] ?? null,
                    'customer_type'   => $customer['customer_type'] ?? 'Individual',
                    'credit_limit'    => $customer['credit_limit'] ?? 0,
                    'opening_balance' => $customer['opening_balance'] ?? 0,
                    'balance_type'    => $customer['balance_type'] ?? 'Receivable',
                    'notes'           => $customer['notes'] ?? null,
                    'is_active'       => true,
                ]);
            }
        });

        return redirect()->route('customers.index')
            ->with('success', $customerCount . ' customers created successfully!');
    }

    public function show(Customer $customer)
    {
        $this->authorizeCompany($customer);

        $customer->load('invoices', 'salesOrders');

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $this->authorizeCompany($customer);

        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeCompany($customer);

        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $customer->update($request->only([
            'name',
            'phone',
            'email',
            'address',
            'trade_license',
            'tin',
            'customer_type',
            'credit_limit',
            'opening_balance',
            'balance_type',
            'notes',
        ]));

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated!');
    }

    public function destroy(Customer $customer)
    {
        $this->authorizeCompany($customer);

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted!');
    }

    /**
     * Generate a unique customer code from the company name.
     *
     * Example:
     * Shadhan Alo => SA-00001
     */
    private function generateCustomerCode(int $companyId): string
    {
        $companyName = DB::table('companies')
            ->where('id', $companyId)
            ->value('company_name');

        if (!$companyName) {
            throw new \RuntimeException('Company not found.');
        }

        $prefix = $this->generateCompanyPrefix($companyName);

        $lastCode = Customer::query()
            ->where('customer_code', 'like', $prefix . '-%')
            ->orderByDesc('id')
            ->value('customer_code');

        $nextNumber = 1;

        if ($lastCode) {
            $lastNumber = (int) substr($lastCode, strrpos($lastCode, '-') + 1);
            $nextNumber = $lastNumber + 1;
        }

        return $prefix . '-' . str_pad(
            (string) $nextNumber,
            5,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Generate company initials.
     *
     * Example:
     * Shadhan Alo => SA
     * Nexton BD Tech => NBT
     */
    private function generateCompanyPrefix(string $companyName): string
    {
        $words = preg_split('/\s+/', trim($companyName), -1, PREG_SPLIT_NO_EMPTY);

        $prefix = '';

        foreach ($words as $word) {
            $word = preg_replace('/[^A-Za-z]/', '', $word);

            if ($word !== '') {
                $prefix .= strtoupper($word[0]);
            }
        }

        if ($prefix === '') {
            throw new \RuntimeException(
                'Unable to generate customer code prefix from company name.'
            );
        }

        return substr($prefix, 0, 10);
    }

    /**
     * Guard against IDOR: a customer from another company must never
     * be viewable/editable/deletable via the currently selected company.
     */
    private function authorizeCompany(Customer $customer): void
    {
        if ((int) $customer->company_id !== (int) session('company_id')) {
            throw new NotFoundHttpException();
        }
    }
}