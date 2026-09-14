<?php

namespace App\Http\Controllers;

use App\Models\ArInvoice;
use App\Services\BkashPaymentProcessorService;
use Illuminate\Http\Request;

class BkashPaymentController extends Controller
{
    public function __construct(
        private BkashPaymentProcessorService $processor
    ) {}

    // Invoice থেকে Payment শুরু করো
    public function initiate(ArInvoice $invoice)
    {
        abort_unless(
            $invoice->company_id === auth()->user()->company_id,
            403
        );

        $data = $this->processor->initiatePayment($invoice);

        return response()->json([
            'payment_url' => $data['bkash_url'],
            'qr_code'     => $data['qr_code_url'],
        ]);
    }

    // bKash callback (signature verify করতে হবে)
    public function callback(Request $request)
    {
        $result = $this->processor->handleCallback($request->all());

        return match ($result) {
            'success' => redirect()->route('invoices.success', ['trx' => $request->trxID]),
            default   => redirect()->route('invoices.failed'),
        };
    }
}