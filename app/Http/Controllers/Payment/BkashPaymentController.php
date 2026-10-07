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
        // The active company is stored in the "company_id" session key.
        $companyId = (int) session('company_id');

        abort_unless(
            $companyId > 0 && (int) $invoice->company_id === $companyId,
            403
        );

        abort_if(
            bccomp((string) $invoice->total_amount, (string) $invoice->paid_amount, 2) <= 0,
            422,
            'No outstanding balance.'
        );

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
            'review'    => redirect()->route('invoices.index')
                               ->with('warning', 'আপনার বিকাশ পেমেন্ট গ্রহণ করা হয়েছে, কিন্তু রেকর্ড সম্পন্ন হয়নি। অনুগ্রহ করে আবার পেমেন্ট করবেন না, আমরা যাচাই করছি।'),
            'cancelled' => redirect()->route('invoices.index')
                               ->with('warning', 'পেমেন্ট বাতিল করা হয়েছে।'),
            default     => redirect()->route('invoices.index')
                               ->with('error', 'পেমেন্ট ব্যর্থ হয়েছে। আবার চেষ্টা করুন।'),
        };
    }
}
