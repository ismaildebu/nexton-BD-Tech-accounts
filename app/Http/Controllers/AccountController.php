<?php

namespace App\Http\Controllers;

use App\Exceptions\AccountCodeRangeExceededException;
use App\Exceptions\CannotDeleteAccountException;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
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

        $companies = auth()->user()->companies ?? Company::where('id', $companyId)->get();
        
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

        $companyId = $request->company_id;
        $accountType = $request->account_type;
        $level = 1;

        if ($request->filled('parent_id')) {
            $parent = Account::forCompany($companyId)->find($request->parent_id);

            if (!$parent) {
                return back()->withErrors(['parent_id' => 'প্যারেন্ট অ্যাকাউন্টটি একই কোম্পানির হতে হবে!'])->withInput();
            }

            if ($parent->account_type !== $accountType) {
                return back()->withErrors(['account_type' => 'প্যারেন্ট ও চাইল্ড অ্যাকাউন্টের Type একই হতে হবে!'])->withInput();
            }

            $level = $parent->level + 1;

            if ($level > 5) {
                return back()->withErrors(['parent_id' => 'সর্বোচ্চ ৫ লেভেল পর্যন্ত সাব-অ্যাকাউন্ট তৈরি করা যাবে!'])->withInput();
            }
        }

        try {
            return DB::transaction(function () use ($request, $companyId, $accountType, $level) {
                $accountCode = Account::generateNextCode($accountType, $companyId);
                $balanceType = Account::defaultBalanceType($accountType);

                $account = new Account([
                    'company_id'      => $companyId,
                    'account_name'    => $request->account_name,
                    'account_type'    => $accountType,
                    'parent_id'       => $request->parent_id,
                    'nature'          => $request->nature,
                    'level'           => $level,
                    'color'           => $request->color,
                    'is_system'       => false,
                    'is_active'       => true,
                    'opening_balance' => $request->opening_balance ?? 0,
                    'balance_type'    => $balanceType,
                ]);

                $account->account_code = $accountCode;
                $account->save();

                return redirect()->route('accounts.index')->with('success', "অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে (কোড: {$accountCode})");
            });
        } catch (AccountCodeRangeExceededException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'অ্যাকাউন্ট তৈরি করতে সমস্যা হয়েছে: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show form for editing an account
     */
    
    /**
 * Show form for editing an account
 */
public function edit(string $id)
{
    $companyId = session('company_id', auth()->user()->company_id ?? null);

    $account = Account::forCompany($companyId)->findOrFail($id);

    // Company List
    $companies = auth()->user()->companies ?? Company::where('id', $companyId)->get();

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

            return redirect()->route('accounts.index')->with('success', 'অ্যাকাউন্ট সফলভাবে আপডেট হয়েছে!');
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
            return redirect()->route('accounts.index')->with('success', 'অ্যাকাউন্ট সফলভাবে ডিলিট হয়েছে!');
        } catch (CannotDeleteAccountException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'অ্যাকাউন্ট ডিলিট করা যায়নি।');
        }
    }
}