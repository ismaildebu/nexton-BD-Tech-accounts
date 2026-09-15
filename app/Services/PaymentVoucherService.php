<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CustomerPayment;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\VoucherType;
use Illuminate\Support\Facades\DB;

class PaymentVoucherService
{
    /**
     * Customer Payment থেকে Receipt Voucher তৈরি করা
     *
     * Voucher structure:
     * - Debit: Bank/Cash Account
     * - Credit: Accounts Receivable
     */
    public function createReceiptVoucher(CustomerPayment $payment): Transaction
    {
        return DB::transaction(function () use ($payment) {
            $payment = CustomerPayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($payment->voucher_id !== null) {
                return Transaction::query()->findOrFail($payment->voucher_id);
            }

            if (!in_array($payment->status, ['received', 'verified'], true)) {
                throw new \RuntimeException(
                    "Payment must be received or verified before creating a voucher."
                );
            }

            $bankAccount = $this->resolveBankAccount($payment);

            if (!$bankAccount) {
                throw new \RuntimeException(
                    'Bank account not found for payment method: '
                    . $payment->payment_method
                );
            }

            // Accounts Receivable খোঁজা
            $receivableAccount = $this->getReceivableAccount(
                $payment->company_id
            );

            if (!$receivableAccount) {
                throw new \RuntimeException(
                    'Accounts Receivable account not configured.'
                );
            }

            // নতুন Voucher তৈরি করা
            $voucher = Transaction::create([
                'company_id' => $payment->company_id,
                'voucher_type_id' => $this->getJournalVoucherTypeId(),
                'voucher_number' => $this->generateVoucherNumber(
                    $payment->company_id
                ),
                'voucher_date' => $payment->payment_date,
                'reference_type' => 'CustomerPayment',
                'reference_id' => $payment->id,
               'description' => "Payment received from customer: "
    .                   "{$payment->customer->name} "
                    . "(Ref: {$payment->reference_id})",
                'is_balanced' => false,
                'status' => 'Draft',
                'created_by' => auth()->id() ?? 1,
            ]);

            // Ledger Detail Line 1: Bank/Cash Account (Debit)
            TransactionDetail::create([
                'transaction_id' => $voucher->id,
                'account_id' => $bankAccount->id,
                'debit_amount' => $payment->amount,
                'credit_amount' => 0,
                'description' => "Payment received from "
                    . $payment->customer->customer_name,
            ]);

            // Ledger Detail Line 2: Accounts Receivable (Credit)
            TransactionDetail::create([
                'transaction_id' => $voucher->id,
                'account_id' => $receivableAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $payment->amount,
                'description' => "Payment received from "
                    . $payment->customer->customer_name,
            ]);

            // Voucher validate এবং post করা
            $this->validateAndPostVoucher($voucher);

            // Payment-এর সাথে Voucher link করা
            $payment->update([
                'voucher_id' => $voucher->id,
            ]);

            return $voucher;
        });
    }

    /**
     * Payment method অনুযায়ী Bank/Cash Account খোঁজা
     */
    private function resolveBankAccount(CustomerPayment $payment): ?Account
    {
        return Account::query()
            ->where('company_id', $payment->company_id)
            ->where('account_type', Account::TYPE_ASSET)
            ->whereIn('nature', [
                Account::NATURE_CASH,
                Account::NATURE_BANK,
            ])
            ->where('is_active', true)
            ->orderByRaw(
                'CASE
                    WHEN nature = ? THEN 0
                    WHEN nature = ? THEN 1
                    ELSE 2
                END',
                [
                    Account::NATURE_BANK,
                    Account::NATURE_CASH,
                ]
            )
            ->orderBy('account_code')
            ->first();
    }

    /**
     * Accounts Receivable account খোঁজা
     */
    private function getReceivableAccount(int $companyId): ?Account
    {
        return Account::query()
            ->where('company_id', $companyId)
            ->where(function ($query) {
                $query->where('account_code', 1003)
                    ->orWhere(
                        'account_name',
                        'like',
                        '%Receivable%'
                    );
            })
            ->where('is_active', true)
            ->first();
    }

    /**
     * Journal Voucher Type ID খোঁজা
     */
    private function getJournalVoucherTypeId(): int
        {
            $voucherType = VoucherType::query()
                ->where('name', 'Journal Voucher')
                ->orWhere('name', 'Journal')
                ->first();

            if ($voucherType === null) {
                throw new \RuntimeException(
                    'Journal voucher type is not configured.'
                );
            }

            return $voucherType->id;
        }

    /**
     * Voucher number generate করা
     */
    private function generateVoucherNumber(int $companyId): string
    {
        $lastVoucher = Transaction::query()
            ->where('company_id', $companyId)
            ->where('voucher_number', 'like', 'RCP-%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($lastVoucher !== null) {
            $lastNumber = (int) substr(
                $lastVoucher->voucher_number,
                -4
            );

            $nextNumber = $lastNumber + 1;
        }

        return 'RCP-' . str_pad(
            (string) $nextNumber,
            4,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Voucher validate এবং post করা
     */
    private function validateAndPostVoucher(Transaction $voucher): void
    {
        $details = $voucher->details;

        if ($details->count() < 2) {
            throw new \RuntimeException(
                'Voucher must have at least 2 lines.'
            );
        }

        $totalDebit = $details->sum('debit_amount');
        $totalCredit = $details->sum('credit_amount');

        if (
            bccomp(
                (string) $totalDebit,
                (string) $totalCredit,
                4
            ) !== 0
        ) {
            throw new \RuntimeException(
                "Voucher is not balanced. "
                . "Debit: {$totalDebit}, "
                . "Credit: {$totalCredit}"
            );
        }

        $voucher->update([
            'is_balanced' => true,
            'status' => Transaction::STATUS_APPROVED,
        ]);

        $ledgerPostingService = app(
            LedgerPostingService::class
        );

        $ledgerPostingService->post($voucher);
    }
}