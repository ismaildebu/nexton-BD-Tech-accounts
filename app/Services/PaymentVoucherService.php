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
     * Customer Payment থেকে Receipt Voucher তৈরি করা।
     *
     * Voucher structure:
     *
     * Debit  : Bank/Cash Account
     * Credit : Accounts Receivable
     *
     * কোনো আলাদা ledger posting এখানে করা হবে না।
     */
    public function createReceiptVoucher(
        CustomerPayment $payment
    ): Transaction {
        return DB::transaction(function () use ($payment) {
            $payment = CustomerPayment::query()
                ->lockForUpdate()
                ->with('customer')
                ->findOrFail($payment->id);

            /*
             * Duplicate protection.
             */
            if ($payment->voucher_id !== null) {
                return Transaction::query()
                    ->findOrFail($payment->voucher_id);
            }

            /*
             * Voucher only allowed after actual receipt/verification.
             */
            if (!in_array(
                $payment->status,
                ['received', 'verified'],
                true
            )) {
                throw new \RuntimeException(
                    'Payment must be received or verified before creating a voucher.'
                );
            }

            if ($payment->amount <= 0) {
                throw new \RuntimeException(
                    'Payment amount must be greater than zero.'
                );
            }

            /*
             * Payment method অনুযায়ী configured
             * Cash/Bank account resolve করা।
             */
            $bankAccount = $this->resolveBankAccount($payment);

            if (!$bankAccount) {
                throw new \RuntimeException(
                    'Bank or cash account not found for payment method: '
                    . $payment->payment_method
                );
            }

            /*
             * Accounts Receivable account resolve করা।
             */
            $receivableAccount = $this->getReceivableAccount(
                $payment->company_id
            );

            if (!$receivableAccount) {
                throw new \RuntimeException(
                    'Accounts Receivable account not configured.'
                );
            }

            /*
             * Voucher type resolve করা।
             */
            $voucherTypeId = $this->getJournalVoucherTypeId(
                $payment->company_id
            );

            /*
             * Customer name safely resolve করা।
             */
            $customerName =
                $payment->customer?->customer_name
                ?? $payment->customer?->name
                ?? 'Customer';

            /*
             * Receipt Voucher তৈরি করা।
             */
            $voucher = Transaction::create([
                'company_id' => $payment->company_id,
                'voucher_type_id' => $voucherTypeId,
                'voucher_number' => $this->generateVoucherNumber(
                    $payment->company_id
                ),
                'voucher_date' => $payment->payment_date,
                'reference_type' => 'CustomerPayment',
                'reference_id' => $payment->id,
                'description' => 'Payment received from customer: '
                    . $customerName
                    . ' (Customer Code: '
                    . $payment->reference_id
                    . ')',
                'is_balanced' => false,
                'status' => 'Draft',
                'created_by' => auth()->id() ?? 1,
            ]);

            /*
             * Debit: Cash/Bank.
             */
            TransactionDetail::create([
                'transaction_id' => $voucher->id,
                'account_id' => $bankAccount->id,
                'debit_amount' => $payment->amount,
                'credit_amount' => 0,
                'description' => 'Payment received from '
                    . $customerName,
            ]);

            /*
             * Credit: Accounts Receivable.
             */
            TransactionDetail::create([
                'transaction_id' => $voucher->id,
                'account_id' => $receivableAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $payment->amount,
                'description' => 'Payment received from '
                    . $customerName,
            ]);

            /*
             * Voucher balance validate করা।
             *
             * এখানে কোনো LedgerPostingService call নেই।
             */
            $this->validateVoucher($voucher);

            /*
             * Payment-এর সাথে Voucher link করা।
             */
            $payment->update([
                'voucher_id' => $voucher->id,
            ]);

            return $voucher->refresh();
        });
    }

    /**
     * Payment method অনুযায়ী Cash/Bank Account resolve করা।
     */
    private function resolveBankAccount(
        CustomerPayment $payment
    ): ?Account {
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
     * Accounts Receivable account resolve করা।
     */
    private function getReceivableAccount(
        int $companyId
    ): ?Account {
        return Account::query()
            ->where('company_id', $companyId)
            ->where(function ($query) {
                $query
                    ->where('account_code', 1003)
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
     * Journal Voucher Type ID resolve করা।
     */
    private function getJournalVoucherTypeId(
        int $companyId
    ): int {
        $query = VoucherType::query()
            ->where(function ($query) {
                $query
                    ->where('name', 'Journal Voucher')
                    ->orWhere('name', 'Journal');
            });

        /*
         * যদি VoucherType company-specific হয়,
         * তাহলে company isolation বজায় থাকবে।
         */
        if (
            in_array(
                'company_id',
                (new VoucherType())->getFillable(),
                true
            )
        ) {
            $query->where('company_id', $companyId);
        }

        $voucherType = $query->first();

        if ($voucherType === null) {
            throw new \RuntimeException(
                'Journal voucher type is not configured.'
            );
        }

        return $voucherType->id;
    }

    /**
     * Receipt Voucher number generate করা।
     *
     * Example:
     * RCP-0001
     * RCP-0002
     */
    private function generateVoucherNumber(
        int $companyId
    ): string {
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
     * Voucher balanced কিনা validate করা।
     *
     * এখানে ledger posting করা হয় না।
     */
    private function validateVoucher(
        Transaction $voucher
    ): void {
        $details = $voucher->details()->get();

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
                'Voucher is not balanced. '
                . 'Debit: ' . $totalDebit
                . ', Credit: ' . $totalCredit
            );
        }

        /*
         * Voucher approved/validated হিসেবে save করা হচ্ছে।
         * আলাদা ledger posting এখানে করা হচ্ছে না।
         */
        $voucher->update([
            'is_balanced' => true,
            'status' => Transaction::STATUS_APPROVED,
        ]);
    }
}