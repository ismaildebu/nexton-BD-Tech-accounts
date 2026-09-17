<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\FinancialYear;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Trial Balance Verification Controller
 * 
 * সমস্যা #4: Trial Balance verification endpoint
 * এটা নিশ্চিত করে যে মোট Debit = মোট Credit
 */
class TrialBalanceVerificationController extends Controller
{
    /**
     * Verify Trial Balance for a company and financial year
     * 
     * GET /trial-balance/verify?fy_id=1
     */
    public function verify(Request $request): JsonResponse
    {
        $companyId = (int) session('company_id');

        // যদি FY ID প্রদান করা না হয়, সক্রিয় FY ব্যবহার করুন
        $fyId = (int) ($request->query('fy_id') ?? FinancialYear::where('company_id', $companyId)
            ->where('is_active', true)
            ->first()?->id);

        if (!$fyId) {
            return response()->json([
                'error' => 'কোনো সক্রিয় আর্থিক বছর নেই',
            ], 400);
        }

        try {
            // Trial Balance ডেটা নিন
            $result = LedgerEntry::trialBalance($companyId, $fyId)->first();

            if (!$result) {
                return response()->json([
                    'total_debit' => '0.0000',
                    'total_credit' => '0.0000',
                    'is_balanced' => true,
                    'entry_count' => 0,
                    'status' => '✅ BALANCED (No entries)',
                    'difference' => '0.0000',
                ]);
            }

            $totalDebit = (string) ($result->total_debit ?? 0);
            $totalCredit = (string) ($result->total_credit ?? 0);

            // bccomp দিয়ে যাচাই করুন (4 দশমিক স্থান)
            $isBalanced = bccomp($totalDebit, $totalCredit, 4) === 0;
            $difference = bcsub($totalDebit, $totalCredit, 4);

            return response()->json([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'difference' => $difference,
                'is_balanced' => $isBalanced,
                'entry_count' => $result->entry_count,
                'fy_id' => $fyId,
                'company_id' => $companyId,
                'status' => $isBalanced ? '✅ BALANCED' : '❌ UNBALANCED',
                'message' => $isBalanced 
                    ? "আপনার হিসাব ভারসাম্যপূর্ণ। মোট ডেবিট = মোট ক্রেডিট = {$totalDebit}"
                    : "সতর্কতা! অসামঞ্জস্য পাওয়া গেছে। পার্থক্য: {$difference}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Trial balance গণনা ব্যর্থ',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Trial Balance for reporting
     * 
     * GET /trial-balance/data?fy_id=1
     */
    public function getData(Request $request): JsonResponse
    {
        $companyId = (int) session('company_id');
        $fyId = (int) ($request->query('fy_id') ?? FinancialYear::where('company_id', $companyId)
            ->where('is_active', true)
            ->first()?->id);

        if (!$fyId) {
            return response()->json(['error' => 'No FY'], 400);
        }

        // প্রতিটি অ্যাকাউন্টের জন্য ডেবিট/ক্রেডিট ব্যালেন্স
        $entries = LedgerEntry::select(
            'account_id',
            \Illuminate\Support\Facades\DB::raw('SUM(CAST(debit_amount AS DECIMAL(18,4))) as debit'),
            \Illuminate\Support\Facades\DB::raw('SUM(CAST(credit_amount AS DECIMAL(18,4))) as credit')
        )
            ->where('company_id', $companyId)
            ->where('financial_year_id', $fyId)
            ->where('is_reversed', false)
            ->groupBy('account_id')
            ->with('account')
            ->get()
            ->map(function ($entry) {
                return [
                    'account_id' => $entry->account_id,
                    'account_code' => $entry->account?->account_code ?? 'N/A',
                    'account_name' => $entry->account?->account_name ?? 'N/A',
                    'debit' => (string) ($entry->debit ?? 0),
                    'credit' => (string) ($entry->credit ?? 0),
                ];
            });

        return response()->json([
            'entries' => $entries,
            'fy_id' => $fyId,
            'company_id' => $companyId,
        ]);
    }
}