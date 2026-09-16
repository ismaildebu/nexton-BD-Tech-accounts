<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BkashPayment;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BkashPaymentProcessorService
{
    public function __construct(
        private readonly BkashPaymentService $bkash,
        private readonly CustomerPaymentService $customerPaymentService,
    ) {}

    // ─── Step 1: Initiate ─────────────────────────────────────────────────────

    public function initiatePayment(Invoice $invoice): array
    {
        $merchantInvoiceNumber = 'INV-' . $invoice->id . '-' . Str::random(6);

        $response = $this->bkash->createPayment([
            'amount'                  => $invoice->due_amount,
            'customer_phone'          => $invoice->customer->phone,
            'merchant_invoice_number' => $merchantInvoiceNumber,
        ]);

        BkashPayment::create([
            'company_id'              => $invoice->company_id,
            'invoice_id'              => $invoice->id,
            'customer_id'             => $invoice->customer_id,
            'payment_id'              => $response['paymentID'],
            'amount'                  => $invoice->due_amount,
            'currency'                => 'BDT',
            'merchant_invoice_number' => $merchantInvoiceNumber,
            'status'                  => 'initiated',
        ]);

        return [
            'payment_id'  => $response['paymentID'],
            'bkash_url'   => $response['bkashURL'],
            'qr_code_url' => $response['qrCodeURL'] ?? null,
        ];
    }

    // ─── Step 2: Handle Callback ─────────────────────────────────────────────

    public function handleCallback(array $callbackData): string
    {
        $paymentId = $callbackData['paymentID'] ?? null;
        $status    = $callbackData['status'] ?? null;

        if (! $paymentId) {
            Log::warning('bKash callback received without paymentID.', [
                'callback' => $callbackData,
            ]);

            return 'failed';
        }

        $bkashPayment = BkashPayment::query()
            ->where('payment_id', $paymentId)
            ->first();

        if (! $bkashPayment) {
            Log::warning('bKash payment not found for callback.', [
                'payment_id' => $paymentId,
            ]);

            return 'failed';
        }

        $bkashPayment->update([
            'callback_response' => $callbackData,
        ]);

        if ($status !== 'success') {
            $bkashPayment->update([
                'status' => $status === 'cancel'
                    ? 'cancelled'
                    : 'failed',
            ]);

            return $status === 'cancel'
                ? 'cancelled'
                : 'failed';
        }

        return $this->executePayment($bkashPayment);
    }

    // ─── Step 3: Execute & Process ───────────────────────────────────────────

    private function executePayment(BkashPayment $bkashPayment): string
    {
        /*
         * Prevent duplicate processing when bKash callback
         * is received more than once.
         */
        if ($bkashPayment->isCompleted()) {
            return 'success';
        }

        $executeResponse = $this->bkash->executePayment(
            $bkashPayment->payment_id
        );

        $bkashPayment->update([
            'execute_response' => $executeResponse,
        ]);

        if (($executeResponse['statusCode'] ?? '') !== '0000') {
            $bkashPayment->update([
                'status' => 'failed',
            ]);

            Log::error('bKash execute failed.', [
                'payment_id' => $bkashPayment->payment_id,
                'response'   => $executeResponse,
            ]);

            return 'failed';
        }

        $trxId = $executeResponse['trxID'] ?? null;

        if (! $trxId) {
            $bkashPayment->update([
                'status' => 'failed',
            ]);

            Log::error('bKash execute succeeded without trxID.', [
                'payment_id' => $bkashPayment->payment_id,
                'response'   => $executeResponse,
            ]);

            return 'failed';
        }

        DB::transaction(function () use ($bkashPayment, $trxId): void {

            $payment = BkashPayment::query()
                ->lockForUpdate()
                ->findOrFail($bkashPayment->id);

            if ($payment->isCompleted()) {
                return;
            }

            /*
             * 1. Mark bKash payment as completed.
             */
            $payment->update([
                'trx_id'  => $trxId,
                'status'  => 'completed',
                'paid_at' => now(),
            ]);

            /*
             * 2. Update Invoice paid amount.
             */
            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($payment->invoice_id);

            $invoice->increment(
                'paid_amount',
                (float) $payment->amount
            );

            /*
             * 3. Create/reuse CustomerPayment.
             *
             * Reference ID = Customer Code.
             */
            $customerPayment = CustomerPayment::query()
                ->where('transaction_reference', $trxId)
                ->lockForUpdate()
                ->first();

            if (! $customerPayment) {
                $customerPayment = CustomerPayment::create([
                    'company_id'            => $payment->company_id,
                    'customer_id'           => $payment->customer_id,
                    'reference_id'          => $payment->customer->customer_code,
                    'invoice_id'            => $payment->invoice_id,
                    'amount'                => $payment->amount,
                    'payment_date'          => $payment->paid_at ?? now(),
                    'payment_method'        => 'bkash',
                    'transaction_reference' => $trxId,
                    'status'                => 'pending',
                    'metadata'              => [
                        'source'           => 'bkash_merchant',
                        'bkash_payment_id' => $payment->id,
                        'payment_id'       => $payment->payment_id,
                        'merchant_invoice' => $payment->merchant_invoice_number,
                    ],
                ]);
            }

            /*
             * 4. Mark CustomerPayment as received.
             *
             * This automatically creates the Receipt Voucher
             * through the existing CustomerPayment flow.
             */
            $this->customerPaymentService->markAsReceived(
                $customerPayment->fresh()
            );
        });

        return 'success';
    }
}