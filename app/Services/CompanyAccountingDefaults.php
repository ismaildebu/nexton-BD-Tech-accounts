<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Company;
use App\Models\VoucherType;
use Illuminate\Support\Facades\DB;

/**
 * CompanyAccountingDefaults
 *
 * Guarantees that every company owns the accounts and voucher types the
 * automatic accounting services depend on, no matter which template
 * accounts were selected when the company was created.
 *
 * Accounts (found by NAME + TYPE, never by code):
 *   - Sales Revenue (Income)    used by Sales Orders
 *   - VAT Payable   (Liability) used by Sales Orders with tax
 *   - Sales Discount (Expense)  used by Sales Orders with discount
 *
 * Voucher types (found by code):
 *   - SV  Sales Voucher        (Sales Orders)
 *   - PUV Purchase Voucher     (Purchase Bills)
 *   - PV  Payment Voucher      (Purchase Bill payments)
 *   - JV, RV, CV, OV           (standard defaults)
 *
 * The operation is idempotent: running it again never creates duplicates
 * and never changes anything that already exists.
 */
final class CompanyAccountingDefaults
{
    /**
     * `start` is only the preferred account code. If it is already used
     * in the company, the next free code is taken. Codes are cosmetic
     * because accounts are located by name and type.
     */
    private const REQUIRED_ACCOUNTS = [
        [
            'name' => 'Sales Revenue',
            'type' => 'Income',
            'nature' => 'Income',
            'balance_type' => 'Credit',
            'start' => 5001,
        ],

         [
            'name' => 'Purchase Clearing',
            'type' => 'Liability',
            'nature' => 'General',
            'balance_type' => 'Credit',
            'start' => 2007,
        ],
        [
            'name' => 'VAT Payable',
            'type' => 'Liability',
            'nature' => 'General',
            'balance_type' => 'Credit',
            'start' => 3005,
        ],
        [
            'name' => 'Sales Discount',
            'type' => 'Expense',
            'nature' => 'Expense',
            'balance_type' => 'Debit',
            'start' => 2121,
        ],

       
    ];

    private const REQUIRED_VOUCHER_TYPES = [
        ['code' => 'JV', 'name' => 'Journal Voucher', 'nature' => VoucherType::NATURE_JOURNAL],
        ['code' => 'PV', 'name' => 'Payment Voucher', 'nature' => VoucherType::NATURE_PAYMENT],
        ['code' => 'RV', 'name' => 'Receipt Voucher', 'nature' => VoucherType::NATURE_RECEIPT],
        ['code' => 'CV', 'name' => 'Contra Voucher', 'nature' => VoucherType::NATURE_CONTRA],
        ['code' => 'OV', 'name' => 'Opening Voucher', 'nature' => VoucherType::NATURE_OPENING],
        ['code' => 'SV', 'name' => 'Sales Voucher', 'nature' => VoucherType::NATURE_JOURNAL],
        ['code' => 'PUV', 'name' => 'Purchase Voucher', 'nature' => VoucherType::NATURE_JOURNAL],
    ];

    public function ensure(Company $company): void
    {
        DB::transaction(function () use ($company): void {
            $this->ensureAccounts((int) $company->id);
            $this->ensureVoucherTypes((int) $company->id);
        });
    }

    private function ensureAccounts(int $companyId): void
    {
        foreach (self::REQUIRED_ACCOUNTS as $required) {
            $exists = Account::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('account_name', $required['name'])
                ->where('account_type', $required['type'])
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            Account::create([
                'company_id' => $companyId,
                'account_code' => $this->nextFreeCode(
                    $companyId,
                    $required['start']
                ),
                'account_name' => $required['name'],
                'account_type' => $required['type'],
                'nature' => $required['nature'],
                'parent_id' => null,
                'level' => 1,
                'color' => null,
                'is_system' => true,
                'is_active' => true,
                'opening_balance' => '0.0000',
                'balance_type' => $required['balance_type'],
            ]);
        }
    }

    private function ensureVoucherTypes(int $companyId): void
    {
        foreach (self::REQUIRED_VOUCHER_TYPES as $required) {
            $exists = VoucherType::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('code', $required['code'])
                ->exists();

            if ($exists) {
                continue;
            }

            VoucherType::create([
                'company_id' => $companyId,
                'name' => $required['name'],
                'code' => $required['code'],
                'prefix' => $required['code'],
                'nature' => $required['nature'],
                'last_number' => 0,
                'description' => $required['name'],
                'is_active' => true,
                'status' => 1,
            ]);
        }
    }

    /**
     * First unused account code at or above $start.
     *
     * Deleted accounts are included, so a code that is still held by a
     * soft-deleted row is never reused.
     */
    private function nextFreeCode(int $companyId, int $start): string
    {
        $code = $start;

        while (
            Account::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('account_code', (string) $code)
                ->exists()
        ) {
            $code++;
        }

        return (string) $code;
    }
}