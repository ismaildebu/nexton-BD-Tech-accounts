<?php

namespace App\Services;

use App\Mail\AdminPaymentNotification;
use App\Mail\PaymentReceivedMail;
use App\Mail\PaymentVerifiedMail;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CustomerPaymentService
{
    public function __construct(
        private PaymentVoucherService $voucherService,
    ) {}

    /**
     * Reference ID থেকে Invoice resolve করা।
     *
     * Legacy invoice-based payment flow-এর জন্য রাখা হয়েছে।
     */
    public function resolveInvoice(
        string $referenceId,
        int $companyId
    ): ?Invoice {
        return Invoice::query()
            ->where('company_id', $companyId)
            ->where('invoice_number', $referenceId)
            ->first();
    }

    /**
     * Payment record তৈরি করা।
     *
     * Customer payment flow-এ reference_id হলো customer_code।
     * Customer code-কে invoice number হিসেবে ব্যবহার করা হবে না।
     */
    public function createPayment(
        int $companyId,
        int $customerId,
        string $referenceId,
        float $amount,
        string $paymentMethod,
        ?string $transactionReference = null,
        ?array $metadata = null
    ): CustomerPayment {
        return DB::transaction(function () use (
            $companyId,
            $customerId,
            $referenceId,
            $amount,
            $paymentMethod,
            $transactionReference,
            $metadata
        ) {
            if ($amount <= 0) {
                throw new \RuntimeException(
                    'Payment amount must be greater than zero.'
                );
            }

            /*
             * Verify that the customer code belongs to
             * the supplied customer and company.
             */
            $customer = Customer::query()
                ->where('company_id', $companyId)
                ->where('id', $customerId)
                ->where('customer_code', $referenceId)
                ->first();

            if (!$customer) {
                throw new \RuntimeException(
                    'Invalid customer code for the selected company.'
                );
            }

            /*
             * Customer Code based payment does not point to
             * an invoice.
             */
            $payment = CustomerPayment::create([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'reference_id' => $referenceId,
                'invoice_id' => null,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'payment_method' => $paymentMethod,
                'transaction_reference' => $transactionReference,
                'status' => 'pending',
                'metadata' => $metadata,
            ]);

            return $payment;
        });
    }

    /**
     * Payment verify করা।
     *
     * Verification successful হলে:
     * verified → Receive Voucher
     */
    public function verifyPayment(
        CustomerPayment $payment,
        int $verifiedById
    ): CustomerPayment {
        return DB::transaction(function () use (
            $payment,
            $verifiedById
        ) {
            $payment = CustomerPayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            /*
             * Already verified হলে কোনো নতুন voucher তৈরি করা হবে না।
             */
            if ($payment->status === 'verified') {
                return $payment;
            }

            if (!in_array(
                $payment->status,
                ['pending', 'received'],
                true
            )) {
                throw new \RuntimeException(
                    "Payment cannot be verified from status: {$payment->status}"
                );
            }

            if ($payment->amount <= 0) {
                throw new \RuntimeException(
                    'Payment amount must be greater than zero.'
                );
            }

            /*
             * Invoice-based payment থাকলে existing invoice
             * verification logic preserve করা হচ্ছে।
             *
             * Customer Code based payment-এর invoice_id হবে null,
             * তাই এই block execute হবে না।
             */
            if ($payment->invoice_id !== null) {
                $invoice = Invoice::query()
                    ->where('company_id', $payment->company_id)
                    ->lockForUpdate()
                    ->findOrFail($payment->invoice_id);

                if ($invoice->customer_id !== $payment->customer_id) {
                    throw new \RuntimeException(
                        'Payment customer does not match the invoice customer.'
                    );
                }

                $outstandingAmount =
                    $invoice->total_amount - $invoice->paid_amount;

                if ($payment->amount > $outstandingAmount) {
                    throw new \RuntimeException(
                        'Payment amount exceeds the invoice outstanding amount.'
                    );
                }

                $newPaidAmount =
                    $invoice->paid_amount + $payment->amount;

                $invoice->update([
                    'paid_amount' => $newPaidAmount,
                    'status' => $newPaidAmount >= $invoice->total_amount
                        ? 'paid'
                        : 'partial',
                    'paid_at' => now(),
                ]);
            }

            /*
             * Payment confirmation complete.
             */
            $payment->update([
                'status' => 'verified',
                'verified_by' => $verifiedById,
                'verified_at' => now(),
            ]);

            /*
             * Create the receipt voucher exactly once.
             *
             * PaymentVoucherService itself protects against
             * duplicate voucher creation.
             */
            $this->voucherService->createReceiptVoucher($payment);

            $payment->refresh();

            $this->sendPaymentVerifiedEmail($payment);

            return $payment;
        });
    }

    /**
     * Payment mark as received.
     *
     * Actual gateway/manual confirmation হলে এই method ব্যবহার হবে।
     */
    public function markAsReceived(
        CustomerPayment $payment
    ): CustomerPayment {
        return DB::transaction(function () use ($payment) {
            $payment = CustomerPayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            /*
             * Already received/verified হলে duplicate voucher
             * তৈরি করার চেষ্টা করা হবে না।
             */
            if (
                $payment->status === 'received'
                || $payment->status === 'verified'
            ) {
                if ($payment->voucher_id === null) {
                    $this->voucherService->createReceiptVoucher($payment);
                }

                return $payment->refresh();
            }

            if ($payment->status !== 'pending') {
                throw new \RuntimeException(
                    "Payment cannot be marked as received from status: "
                    . $payment->status
                );
            }

            if ($payment->amount <= 0) {
                throw new \RuntimeException(
                    'Payment amount must be greater than zero.'
                );
            }

            /*
             * Actual payment received.
             */
            $payment->update([
                'status' => 'received',
            ]);

            /*
             * Create Receive Voucher.
             */
            $this->voucherService->createReceiptVoucher($payment);

            $payment->refresh();

            /*
             * Notification is not part of the accounting state.
             * Mail failures are handled inside the notification methods.
             */
            $this->sendPaymentReceivedEmail($payment);
            $this->notifyAdminOfPayment($payment);

            return $payment;
        });
    }

    /**
     * Payment reject করা
     */
    public function rejectPayment(
        CustomerPayment $payment,
        ?string $reason = null
    ): CustomerPayment {
        return DB::transaction(function () use (
            $payment,
            $reason
        ) {
            $payment = CustomerPayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($payment->voucher_id !== null) {
                throw new \RuntimeException(
                    'Payment cannot be rejected after a voucher has been created.'
                );
            }

            if ($payment->status === 'failed') {
                return $payment;
            }

            if (in_array(
                $payment->status,
                ['received', 'verified'],
                true
            )) {
                throw new \RuntimeException(
                    'Received or verified payment cannot be rejected.'
                );
            }

            $payment->update([
                'status' => 'failed',
                'notes' => $reason ?? 'Payment rejected by admin',
            ]);

            return $payment;
        });
    }

    /**
     * Send payment received email to customer.
     */
    public function sendPaymentReceivedEmail(
        CustomerPayment $payment
    ): void {
        try {
            if ($payment->customer?->email) {
                Mail::to($payment->customer->email)
                    ->queue(
                        new PaymentReceivedMail($payment)
                    );
            }
        } catch (\Throwable $e) {
            Log::error(
                'Failed to send payment received email.',
                [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Send payment verified email to customer.
     */
    public function sendPaymentVerifiedEmail(
        CustomerPayment $payment
    ): void {
        try {
            if ($payment->customer?->email) {
                Mail::to($payment->customer->email)
                    ->queue(
                        new PaymentVerifiedMail($payment)
                    );
            }
        } catch (\Throwable $e) {
            Log::error(
                'Failed to send payment verified email.',
                [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Notify admin of new payment.
     */
    public function notifyAdminOfPayment(
        CustomerPayment $payment
    ): void {
        try {
            $adminEmails = [
                'admin@example.com',
            ];

            foreach ($adminEmails as $email) {
                Mail::to($email)
                    ->queue(
                        new AdminPaymentNotification($payment)
                    );
            }
        } catch (\Throwable $e) {
            Log::error(
                'Failed to send admin payment notification.',
                [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }
}