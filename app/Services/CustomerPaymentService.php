<?php

namespace App\Services;

use App\Mail\AdminPaymentNotification;
use App\Mail\PaymentReceivedMail;
use App\Mail\PaymentVerifiedMail;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
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
     * Manual bKash: the customer submits the transaction ID and sender number.
     *
     * This is a CLAIM, not a confirmation. Money has not been checked against
     * the bKash statement yet, so this method must never change the status
     * and must never create a voucher / touch the ledger. The receipt voucher
     * is created only when an admin verifies the payment (verifyPayment()).
     */
    public function submitManualBkashPayment(
        CustomerPayment $payment,
        string $transactionId,
        string $senderNumber
    ): CustomerPayment {
        $transactionId = strtoupper(trim($transactionId));

        try {
            $payment = DB::transaction(function () use (
                $payment,
                $transactionId,
                $senderNumber
            ) {
                $locked = CustomerPayment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                if ($locked->payment_method !== 'bkash') {
                    throw new \RuntimeException(
                        'This payment is not a bKash payment.'
                    );
                }

                if ($locked->status !== 'pending' || $locked->voucher_id !== null) {
                    throw new \RuntimeException(
                        'Payment details can only be submitted while the payment is pending.'
                    );
                }

                $alreadyUsed = CustomerPayment::withTrashed()
                    ->where('transaction_reference', $transactionId)
                    ->where('id', '!=', $locked->id)
                    ->exists();

                if ($alreadyUsed) {
                    throw new \RuntimeException(
                        'This bKash transaction ID has already been submitted.'
                    );
                }

                $locked->update([
                    'transaction_reference' => $transactionId,
                    'metadata' => array_merge($locked->metadata ?? [], [
                        'sender_number' => $senderNumber,
                        'receiver_number' => Company::query()
                            ->whereKey($locked->company_id)
                            ->value('bkash_number'),
                        'submitted_at' => now()->toIso8601String(),
                        'awaiting_verification' => true,
                    ]),
                ]);

                return $locked->refresh();
            });
        } catch (UniqueConstraintViolationException) {
            // Concurrent submission of the same ID: the DB unique index won.
            throw new \RuntimeException(
                'This bKash transaction ID has already been submitted.'
            );
        }

        $this->notifyAdminOfPayment($payment);

        return $payment;
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
             * A manual bKash payment can only be confirmed against a
             * transaction ID the customer actually submitted.
             */
            if (
                $payment->payment_method === 'bkash'
                && blank($payment->transaction_reference)
            ) {
                throw new \RuntimeException(
                    'A bKash payment cannot be verified before the transaction ID is submitted.'
                );
            }

            /*
             * Invoice-based payment: apply it to the invoice exactly once.
             * (A merchant bKash payment has already applied itself when it
             * was received, so this is then a no-op instead of a second
             * increase of paid_amount.)
             */
            $this->applyPaymentToInvoice($payment);

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
     * Apply a payment to its invoice (paid_amount + status), exactly once.
     *
     * Must run inside a DB transaction (the invoice row is locked).
     * Idempotent: the first application is recorded in
     * metadata.invoice_applied_at and any later call does nothing, so
     * every route that confirms a payment (merchant bKash callback, admin
     * verification) can call it without double counting.
     *
     * @throws \RuntimeException customer mismatch or payment above outstanding
     */
    public function applyPaymentToInvoice(CustomerPayment $payment): void
    {
        if ($payment->invoice_id === null) {
            return;
        }

        if (! empty(($payment->metadata ?? [])['invoice_applied_at'])) {
            return;
        }

        $invoice = Invoice::query()
            ->where('company_id', $payment->company_id)
            ->lockForUpdate()
            ->findOrFail($payment->invoice_id);

        if ((int) $invoice->customer_id !== (int) $payment->customer_id) {
            throw new \RuntimeException(
                'Payment customer does not match the invoice customer.'
            );
        }

        $outstanding = bcsub(
            (string) $invoice->total_amount,
            (string) $invoice->paid_amount,
            4
        );

        if (bccomp((string) $payment->amount, $outstanding, 4) > 0) {
            throw new \RuntimeException(
                'Payment amount exceeds the invoice outstanding amount.'
            );
        }

        $newPaid = bcadd(
            (string) $invoice->paid_amount,
            (string) $payment->amount,
            4
        );

        $invoice->update([
            'paid_amount' => $newPaid,
            'status' => bccomp($newPaid, (string) $invoice->total_amount, 4) >= 0
                ? 'paid'
                : 'partial',
            'paid_at' => now(),
        ]);

        $payment->update([
            'metadata' => array_merge($payment->metadata ?? [], [
                'invoice_applied_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Confirm an SSLCommerz payment from a gateway callback (IPN).
     *
     * SECURITY: the callback body is NOT trusted. The payment is resolved from
     * OUR transaction id (no session / company from the caller), and the
     * gateway's own validation API must confirm val_id, tran_id, currency and
     * amount before anything is posted. Idempotent: a duplicate IPN for an
     * already received/verified payment changes nothing.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException unknown tran_id
     * @throws \DomainException callback invalid / not confirmable
     */
    public function confirmSslcommerzPayment(
        array $callback,
        SSLCommerzPaymentService $gateway
    ): CustomerPayment {
        $tranId = trim((string) ($callback['tran_id'] ?? ''));
        $valId = trim((string) ($callback['val_id'] ?? ''));

        if ($tranId === '' || $valId === '') {
            throw new \DomainException('Missing gateway reference.');
        }

        $payment = CustomerPayment::query()
            ->where('payment_method', 'sslcommerz')
            ->where('transaction_reference', $tranId)
            ->firstOrFail();

        if (in_array($payment->status, ['received', 'verified'], true)) {
            return $payment; // duplicate IPN
        }

        if ($payment->status !== 'pending') {
            throw new \DomainException(
                "Payment cannot be confirmed from status: {$payment->status}"
            );
        }

        // Remote HTTP call is deliberately made outside any DB transaction/lock.
        $expectedAmount = bcadd((string) $payment->amount, '0', 2);

        if (!$gateway->verifyPayment($callback, $expectedAmount, 'BDT')) {
            throw new \DomainException('Gateway validation failed.');
        }

        return DB::transaction(function () use ($payment, $callback, $valId) {
            $locked = CustomerPayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            $locked->update([
                'metadata' => array_merge($locked->metadata ?? [], [
                    'val_id' => $valId,
                    'bank_tran_id' => $callback['bank_tran_id'] ?? null,
                    'card_type' => $callback['card_type'] ?? null,
                    'confirmed_via' => 'sslcommerz_ipn',
                    'confirmed_at' => now()->toIso8601String(),
                ]),
            ]);

            return $this->markAsReceived($locked);
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
            // The payment is verified by the admins of the paying company,
            // plus any extra address configured in config/payments.php.
            $adminEmails = User::query()
                ->where('company_id', $payment->company_id)
                ->whereNotNull('email')
                ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
                ->pluck('email')
                ->merge((array) config('payments.admin_notification_emails', []))
                ->unique()
                ->values()
                ->all();

            if ($adminEmails === []) {
                Log::warning('No recipient for the admin payment notification.', [
                    'payment_id' => $payment->id,
                    'company_id' => $payment->company_id,
                ]);

                return;
            }

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