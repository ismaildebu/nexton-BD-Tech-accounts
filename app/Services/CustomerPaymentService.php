<?php

namespace App\Services;

use App\Models\CustomerPayment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use App\Mail\PaymentReceivedMail;
use App\Mail\PaymentVerifiedMail;
use App\Mail\AdminPaymentNotification;
use Illuminate\Support\Facades\Mail;

class CustomerPaymentService
{
    /**
     * প্রথমে Reference ID দিয়ে Invoice খুঁজবে
     */
    public function resolveInvoice(string $referenceId, int $companyId): ?Invoice
    {
        return Invoice::where('company_id', $companyId)
            ->where('invoice_number', $referenceId)
            ->first();
    }

    /**
     * Payment রেকর্ড তৈরি করা (pending status)
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
            $invoice = $this->resolveInvoice($referenceId, $companyId);

            $payment = CustomerPayment::create([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'reference_id' => $referenceId,
                'invoice_id' => $invoice?->id,
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
         * Payment verify করা (admin দ্বারা)
         */
        public function verifyPayment(CustomerPayment $payment, int $verifiedById): CustomerPayment
        {
            return DB::transaction(function () use ($payment, $verifiedById) {
                $payment = CustomerPayment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                // Prevent duplicate verification and duplicate accounting impact.
                if ($payment->status === 'verified') {
                    return $payment;
                }

                if (!in_array($payment->status, ['pending', 'received'], true)) {
                    throw new \RuntimeException(
                        "Payment cannot be verified from status: {$payment->status}"
                    );
                }

                if ($payment->amount <= 0) {
                    throw new \RuntimeException('Payment amount must be greater than zero.');
                }

                $invoice = null;

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

                    $outstandingAmount = $invoice->total_amount - $invoice->paid_amount;

                    if ($payment->amount > $outstandingAmount) {
                        throw new \RuntimeException(
                            'Payment amount exceeds the invoice outstanding amount.'
                        );
                    }

                    $newPaidAmount = $invoice->paid_amount + $payment->amount;

                    $invoice->update([
                        'paid_amount' => $newPaidAmount,
                        'status' => $newPaidAmount >= $invoice->total_amount
                            ? 'paid'
                            : 'partial',
                        'paid_at' => now(),
                    ]);
                }

                $payment->update([
                    'status' => 'verified',
                    'verified_by' => $verifiedById,
                    'verified_at' => now(),
                ]);

                $payment->refresh();

                $this->sendPaymentVerifiedEmail($payment);

                return $payment;
            });
        }

        /**
         * Payment mark as received (gateway confirmed)
         */
        public function markAsReceived(CustomerPayment $payment): CustomerPayment
        {
            $payment->update(['status' => 'received']);
            
            // Send email to customer
            $this->sendPaymentReceivedEmail($payment);
            
            // Notify admin
            $this->notifyAdminOfPayment($payment);
            
            return $payment;
        }

    /**
     * Payment reject করা
     */
    public function rejectPayment(CustomerPayment $payment, ?string $reason = null): CustomerPayment
    {
        $payment->update([
            'status' => 'failed',
            'notes' => $reason ?? 'Payment rejected by admin',
        ]);

        return $payment;
    }


        /**
     * Send payment received email to customer
     */
    public function sendPaymentReceivedEmail(CustomerPayment $payment): void
    {
        try {
            if ($payment->customer->email) {
                Mail::to($payment->customer->email)
                    ->queue(new PaymentReceivedMail($payment));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send payment received email: ' . $e->getMessage());
        }
    }

    /**
     * Send payment verified email to customer
     */
    public function sendPaymentVerifiedEmail(CustomerPayment $payment): void
    {
        try {
            if ($payment->customer->email) {
                Mail::to($payment->customer->email)
                    ->queue(new PaymentVerifiedMail($payment));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send payment verified email: ' . $e->getMessage());
        }
    }

    /**
     * Notify admin of new payment
     */
    public function notifyAdminOfPayment(CustomerPayment $payment): void
    {
        try {
            $adminEmails = ['admin@example.com']; // আপনার admin email দিন
            
            foreach ($adminEmails as $email) {
                Mail::to($email)
                    ->queue(new AdminPaymentNotification($payment));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send admin notification: ' . $e->getMessage());
        }
    }
}