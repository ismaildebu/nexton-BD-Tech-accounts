<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BkashPayment;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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

    /**
     * Result codes: success | failed | cancelled | review
     *
     * "review" means bKash confirmed the payment (money was taken) but it
     * could not be recorded locally (or its details do not match). The
     * bKash payment stays "pending" with the stored execute response, and a
     * later callback replay retries ONLY the local posting. It never
     * executes the payment at bKash a second time.
     */
    private function executePayment(BkashPayment $bkashPayment): string
    {
        /*
         * Prevent duplicate processing when bKash callback
         * is received more than once.
         */
        if ($bkashPayment->isCompleted()) {
            return 'success';
        }

        $stored = $bkashPayment->execute_response;

        if (
            $bkashPayment->status === 'pending'
            && is_array($stored)
            && ($stored['statusCode'] ?? '') === '0000'
            && ! empty($stored['trxID'])
        ) {
            // bKash already confirmed it earlier; only the local posting is retried.
            $executeResponse = $stored;
        } else {
            $executeResponse = $this->bkash->executePayment(
                $bkashPayment->payment_id
            );

            $bkashPayment->update([
                'execute_response' => $executeResponse,
            ]);
        }

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

        /*
         * Defence in depth: what bKash confirmed must be what we asked it
         * to charge. A mismatch is never posted automatically.
         */
        if (! $this->confirmationMatches($bkashPayment, $executeResponse)) {
            $bkashPayment->update(['status' => 'pending']);

            Log::critical('bKash confirmation does not match the stored payment.', [
                'payment_id' => $bkashPayment->payment_id,
                'trx_id'     => $trxId,
                'expected'   => (string) $bkashPayment->amount,
                'response'   => $executeResponse,
            ]);

            return 'review';
        }

        try {
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
                 * 2. Create/reuse CustomerPayment.
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
                 * 3. Apply the payment to the invoice exactly once.
                 *
                 * This is the single place that increases the invoice
                 * paid_amount. It is recorded on the CustomerPayment, so the
                 * later admin verification does not add it a second time.
                 */
                $this->customerPaymentService->applyPaymentToInvoice(
                    $customerPayment->fresh()
                );

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
        } catch (Throwable $e) {
            // The transaction rolled back, but bKash already took the money.
            $bkashPayment->update(['status' => 'pending']);

            Log::critical('bKash payment confirmed but could not be recorded.', [
                'payment_id' => $bkashPayment->payment_id,
                'trx_id'     => $trxId,
                'error'      => $e->getMessage(),
            ]);

            return 'review';
        }

        return 'success';
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function confirmationMatches(BkashPayment $bkashPayment, array $response): bool
    {
        if (
            isset($response['transactionStatus'])
            && $response['transactionStatus'] !== 'Completed'
        ) {
            return false;
        }

        if (
            isset($response['amount'])
            && bccomp((string) $response['amount'], (string) $bkashPayment->amount, 2) !== 0
        ) {
            return false;
        }

        if (
            isset($response['merchantInvoiceNumber'])
            && $response['merchantInvoiceNumber'] !== $bkashPayment->merchant_invoice_number
        ) {
            return false;
        }

        return true;
    }
}
