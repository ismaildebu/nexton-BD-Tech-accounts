<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\BkashPaymentProcessorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BkashPaymentController extends Controller
{
    public function __construct(
        private readonly BkashPaymentProcessorService $processor,
    ) {}

    public function initiate(Invoice $invoice): JsonResponse
    {
        abort_unless(
            $invoice->company_id === (int) session('active_company_id'),
            403
        );

        abort_if($invoice->due_amount <= 0, 422, 'No outstanding balance.');

        $data = $this->processor->initiatePayment($invoice);

        return response()->json([
            'success'     => true,
            'payment_url' => $data['bkash_url'],
            'qr_code_url' => $data['qr_code_url'],
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $result = $this->processor->handleCallback($request->all());

        return match ($result) {
            'success'   => redirect()->route('invoices.index')
                               ->with('success', 'বিকাশ পেমেন্ট সফল! TrxID: ' . $request->get('trxID')),
            'cancelled' => redirect()->route('invoices.index')
                               ->with('warning', 'পেমেন্ট বাতিল করা হয়েছে।'),
            default     => redirect()->route('invoices.index')
                               ->with('error', 'পেমেন্ট ব্যর্থ হয়েছে। আবার চেষ্টা করুন।'),
        };
    }
}
