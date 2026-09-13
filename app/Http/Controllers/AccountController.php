<?php

namespace App\Http\Controllers;

use App\Exceptions\AccountCodeRangeExceededException;
use App\Exceptions\CannotDeleteAccountException;
use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Models\Account;
use App\Models\Company;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\FinancialYear;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\VoucherType;

class AccountController extends Controller
{
    use EnforcesPlanLimits;

    public function __construct(
        private readonly PlanLimitService $planLimitService,
    ) {
    }

    /**
     * Display accounts list with search and filters
     */
    public function index(Request $request)
    {
        $companyId = session('company_id', auth()->user()->company_id ?? null);

        $query = Account::forCompany($companyId)->with(['parent', 'company']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                  ->orWhere('account_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status == 'active');
        }

        $accounts = $query->orderBy('account_code', 'asc')
            ->paginate(20)
            ->withQueryString();

        return view('accounts.index', compact('accounts'));
    }

    /**
     * Show form for creating a new account
     */
    public function create()
    {
        $companyId = session('company_id', auth()->user()->company_id ?? null);

        // ✅ Fix: auth()->user()->companies রিলেশন User model-এ নেই (dead code,
        // সবসময় null হয়ে ?? fallback-এ পড়ত)। Super Admin সব company দেখবে,
        // বাকিরা শুধু নিজের company-টাই দেখবে — store()-এর canAccessCompany()
        // check-এর সাথে সামঞ্জস্যপূর্ণ।
        $companies = auth()->user()->isSuperAdmin()
            ? Company::orderBy('company_name')->get()
            : Company::where('id', $companyId)->get();

        $parentAccounts = Account::forCompany($companyId)
            ->active()
            ->orderBy('account_code', 'asc')
            ->get();

        return view('accounts.create', compact('companies', 'parentAccounts'));
    }

    /**
     * Store a newly created account
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_id'      => 'required|exists:companies,id',
            'account_name'    => 'required|string|max:255',
            'account_type'    => ['required', Rule::in(Account::accountTypes())],
            'nature'          => ['required', Rule::in(Account::accountNatures())],
            'parent_id'       => 'nullable|exists:accounts,id',
            'color'           => 'nullable|string|max:20',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $authUser = $request->user();
        $requestedCompanyId = (int) $request->company_id;

        if (! $authUser->canAccessCompany($requestedCompanyId)) {
            abort(403, 'You do not have access to this company.');
        }

        $companyId = $requestedCompanyId;
        $accountType = $request->account_type;
        $accountNature = $request->nature;
        $level = 1;

        $this->enforcePlanLimit(
            $this->planLimitService,
            $companyId,
            'accounts',
            Account::where('company_id', $companyId)->count(),
        );

        if ($request->filled('parent_id')) {
            $parent = Account::forCompany($companyId)->find($request->parent_id);

            if (! $parent) {
                return back()
                    ->withErrors([
                        'parent_id' => 'প্যারেন্ট অ্যাকাউন্টটি একই কোম্পানির হতে হবে!',
                    ])
                    ->withInput();
            }

            if ($parent->account_type !== $accountType) {
                return back()
                    ->withErrors([
                        'account_type' => 'প্যারেন্ট ও চাইল্ড অ্যাকাউন্টের Type একই হতে হবে!',
                    ])
                    ->withInput();
            }

            $level = $parent->level + 1;

            if ($level > 5) {
                return back()
                    ->withErrors([
                        'parent_id' => 'সর্বোচ্চ ৫ লেভেল পর্যন্ত সাব-অ্যাকাউন্ট তৈরি করা যাবে!',
                    ])
                    ->withInput();
            }
        }

        try {
            return DB::transaction(function () use (
                $request,
                $companyId,
                $accountType,
                $accountNature,
                $level,
                $authUser
            ) {
                $accountCode = Account::generateNextCode($accountType, $companyId);
                $balanceType = Account::defaultBalanceType($accountType);

                $openingBalance = (string) ($request->opening_balance ?? '0.00');

                /*
                * Opening balance is posted through the ledger.
                * Therefore Account.opening_balance must remain zero
                * to prevent double counting in reports.
                */
                $account = new Account([
                    'company_id'      => $companyId,
                    'account_name'    => $request->account_name,
                    'account_type'    => $accountType,
                    'parent_id'       => $request->parent_id,
                    'nature'          => $accountNature,
                    'level'           => $level,
                    'color'           => $request->color,
                    'is_system'       => false,
                    'is_active'       => true,
                    'opening_balance' => '0.00',
                    'balance_type'    => $balanceType,
                ]);

                $account->account_code = $accountCode;
                $account->save();

                /*
                * Opening Balance Voucher is created for ALL account types.
                * ✅ FIXED: Previously only Bank accounts got vouchers.
                * Now all accounts (Cash, Inventory, Equipment, etc.) 
                * get opening balance vouchers for proper double-entry bookkeeping.
                */
                $hasOpeningBalance = bccomp($openingBalance, '0.00', 2) > 0;

                if ($hasOpeningBalance) {
                    $capitalAccount = Account::query()
                        ->where('company_id', $companyId)
                        ->where('account_type', Account::TYPE_EQUITY)
                        ->where('account_name', "Owner's Capital")
                        ->where('is_active', true)
                        ->first();

                    if (! $capitalAccount) {
                        throw new \RuntimeException(
                            "Owner's Capital account was not found for this company."
                        );
                    }

                    $financialYear = FinancialYear::query()
                        ->where('company_id', $companyId)
                        ->where('is_active', true)
                        ->first();

                    if (! $financialYear) {
                        throw new \RuntimeException(
                            'No active financial year found for this company.'
                        );
                    }

                    $voucherType = VoucherType::query()
                        ->where('company_id', $companyId)
                        ->where('nature', VoucherType::NATURE_OPENING)
                        ->where('is_active', true)
                        ->first();

                    if (! $voucherType) {
                        $voucherType = VoucherType::create([
                            'company_id' => $companyId,
                            'name'       => 'Opening Voucher',
                            'code'       => 'OPENING',
                            'nature'     => VoucherType::NATURE_OPENING,
                            'prefix'     => 'OB',
                            'last_number'=> 0,
                            'is_active'  => true,
                            'description'=> 'Opening balance voucher',
                        ]);
                    }

                    $voucherNumber = $voucherType->generateNextVoucherNumber();

                    $transaction = Transaction::create([
                        'company_id'       => $companyId,
                        'financial_year_id' => $financialYear->id,
                        'voucher_type_id'  => $voucherType->id,
                        'voucher_number'   => $voucherNumber,
                        'voucher_date'     => now()->toDateString(),
                        'narration'        => "Opening Balance - {$account->account_name}",
                        'total_debit'      => $openingBalance,
                        'total_credit'     => $openingBalance,
                        'status'           => Transaction::STATUS_APPROVED,
                        'created_by'       => $authUser->id,
                        'approved_by'      => $authUser->id,
                        'approved_at'      => now(),
                    ]);

                    TransactionDetail::create([
                        'transaction_id' => $transaction->id,
                        'account_id'     => $account->id,
                        'debit_amount'   => $openingBalance,
                        'credit_amount'  => '0.00',
                        'description'    => "Opening Balance - {$account->account_name}",
                        'sort_order'     => 1,
                    ]);

                    TransactionDetail::create([
                        'transaction_id' => $transaction->id,
                        'account_id'     => $capitalAccount->id,
                        'debit_amount'   => '0.00',
                        'credit_amount'  => $openingBalance,
                        'description'    => "Opening Capital - {$account->account_name}",
                        'sort_order'     => 2,
                    ]);

                    app(\App\Services\LedgerPostingService::class)->post($transaction);
                }

                return redirect()
                    ->route('accounts.index')
                    ->with(
                        'success',
                        "অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে (কোড: {$accountCode})"
                    );
            });
        } catch (AccountCodeRangeExceededException $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        } catch (\Exception $e) {
            return back()
                ->with('error', 'অ্যাকাউন্ট তৈরি করতে সমস্যা হয়েছে: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing an account
     */
    public function edit(string $id)
    {
        $companyId = session('company_id', auth()->user()->company_id ?? null);

        $account = Account::forCompany($companyId)->findOrFail($id);

        // Company List
        $companies = auth()->user()->isSuperAdmin()
            ? Company::orderBy('company_name')->get()
            : Company::where('id', $companyId)->get();

        // Parent Accounts
        $parentAccounts = Account::forCompany($companyId)
            ->where('id', '!=', $id)
            ->orderBy('account_code', 'asc')
            ->get();

        $hasTransactions = $account->hasTransactions();

        return view('accounts.edit', compact(
            'account',
            'companies',
            'parentAccounts',
            'hasTransactions'
        ));
    }

    /**
     * Display Account Details
     */
    public function show(string $id)
    {
        $companyId = session('company_id', auth()->user()->company_id ?? null);

        $account = Account::forCompany($companyId)
            ->with(['company', 'parent'])
            ->findOrFail($id);

        return view('accounts.show', compact('account'));
    }

    /**
     * Update account details
     */
    public function update(Request $request, string $id)
    {
        $companyId = session('company_id', auth()->user()->company_id ?? null);
        $account = Account::forCompany($companyId)->findOrFail($id);

        $request->validate([
            'account_name' => 'required|string|max:255',
            'nature'       => ['required', Rule::in(Account::accountNatures())],
            'color'        => 'nullable|string|max:20',
            'is_active'    => 'required|boolean',
        ]);

        return DB::transaction(function () use ($request, $account) {
            $data = [
                'account_name' => $request->account_name,
                'nature'       => $request->nature,
                'color'        => $request->color,
                'is_active'    => $request->is_active,
            ];

            if (!$account->hasTransactions() && $request->has('opening_balance')) {
                $data['opening_balance'] = $request->opening_balance ?? 0;
            }

            $account->update($data);

            return redirect()->route('accounts.index')->with('success', 'অ্যাকাউন্ট সফলভাবে আপডেট হয়েছে!');
        });
    }

    /**
     * Delete account
     */
    public function destroy(string $id)
    {
        $companyId = session('company_id', auth()->user()->company_id ?? null);
        $account = Account::forCompany($companyId)->findOrFail($id);

        try {
            $account->delete();
            return redirect()->route('accounts.index')->with('success', 'অ্যাকাউন্ট সফলভাবে ডিলিট হয়েছে!');
        } catch (CannotDeleteAccountException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'অ্যাকাউন্ট ডিলিট করা যায়নি।');
        }
    }
}