<?php

namespace App\Services;

use App\Models\ArInvoice;
use App\Models\BkashPayment;
use App\Models\JournalVoucher;
use App\Jobs\SendPaymentNotificationJob;
use App\Jobs\GenerateReceiptJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BkashPaymentProcessorService
{
    public function __construct(
        private BkashPaymentService  $bkash,
        private JournalEntryService  $journalService,
    ) {}

    // ─── Step 1: Initiate ─────────────────────────────────────────────────────

    public function initiatePayment(ArInvoice $invoice): array
    {
        $merchantInvoiceNumber = 'INV-' . $invoice->id . '-' . Str::random(6);

        $response = $this->bkash->createPayment([
            'amount'                  => $invoice->due_amount,
            'customer_phone'          => $invoice->customer->phone,
            'merchant_invoice_number' => $merchantInvoiceNumber,
        ]);

        // Payment record সেভ করি
        BkashPayment::create([
            'company_id'              => $invoice->company_id,
            'ar_invoice_id'           => $invoice->id,
            'customer_id'             => $invoice->customer_id,
            'payment_id'              => $response['paymentID'],
            'amount'                  => $invoice->due_amount,
            'merchant_invoice_number' => $merchantInvoiceNumber,
            'status'                  => 'initiated',
        ]);

        return [
            'payment_id'  => $response['paymentID'],
            'bkash_url'   => $response['bkashURL'],   // Customer এখানে redirect হবে
            'qr_code_url' => $response['qrCodeURL'] ?? null,
        ];
    }

    // ─── Step 2: Handle Callback (bKash redirect করে আসে) ────────────────────

    public function handleCallback(array $callbackData): string
    {
        $paymentId = $callbackData['paymentID'];
        $status    = $callbackData['status'];

        $bkashPayment = BkashPayment::where('payment_id', $paymentId)->firstOrFail();
        $bkashPayment->update(['callback_response' => $callbackData]);

        if ($status !== 'success') {
            $bkashPayment->update(['status' => 'failed']);
            return 'failed';
        }

        // Execute করি
        return $this->executePayment($bkashPayment);
    }

    // ─── Step 3: Execute & Process ────────────────────────────────────────────

    private function executePayment(BkashPayment $bkashPayment): string
    {
        $executeResponse = $this->bkash->executePayment($bkashPayment->payment_id);

        $bkashPayment->update(['execute_response' => $executeResponse]);

        if (($executeResponse['statusCode'] ?? '') !== '0000') {
            $bkashPayment->update(['status' => 'failed']);
            Log::error('bKash execute failed', $executeResponse);
            return 'failed';
        }

        // সব কিছু DB transaction-এ করবো
        DB::transaction(function () use ($bkashPayment, $executeResponse) {

            // 1. Payment record আপডেট
            $bkashPayment->update([
                'trx_id'   => $executeResponse['trxID'],
                'status'   => 'completed',
                'paid_at'  => now(),
            ]);

            $invoice = $bkashPayment->arInvoice;

            // 2. AR Invoice আপডেট
            $invoice->increment('paid_amount', $bkashPayment->amount);
            $invoice->updateStatus();  // paid/partial নির্ধারণ করবে

            // 3. Double-Entry Journal Entry তৈরি
            $this->journalService->createBkashReceiptEntry(
                invoice:       $invoice,
                amount:        $bkashPayment->amount,
                trxId:         $executeResponse['trxID'],
                bkashPayment:  $bkashPayment,
            );

            // 4. Receipt + Notification (queue-তে পাঠাই)
            GenerateReceiptJob::dispatch($bkashPayment);
            SendPaymentNotificationJob::dispatch($bkashPayment);
        });

        return 'success';
    }
}