<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Services\CustomerPaymentService;
use App\Services\PaymentVoucherService;
use Illuminate\Http\Request;

class CustomerPaymentController extends Controller
{
    public function __construct(
        private CustomerPaymentService $paymentService,
        private PaymentVoucherService $voucherService,
    ) {}

    /**
     * Payment form দেখা (Customer Code এর জন্য)
     * GET /customer/pay/{customerCode}
     */
    public function show($customerCode)
    {
        $companyId = session('company_id') ?? 16;
        
        // Customer খুঁজা by customer_code
        $customer = Customer::where('company_id', $companyId)
            ->where('customer_code', $customerCode)
            ->firstOrFail();

        return view('customer.payment.show', [
            'customer' => $customer,
            'customerCode' => $customerCode,
        ]);
    }

    /**
     * Payment initiate করা
     * POST /customer/pay/{customerCode}
     */
    public function initiatePayment(Request $request, $customerCode)
    {
        $companyId = session('company_id') ?? 16;
        
        // Customer খুঁজা
        $customer = Customer::where('company_id', $companyId)
            ->where('customer_code', $customerCode)
            ->firstOrFail();

        // Validate করা
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:bkash,sslcommerz,bank_transfer',
        ]);

        try {
            // Payment record তৈরি করা
            $payment = $this->paymentService->createPayment(
                companyId: $companyId,
                customerId: $customer->id,
                referenceId: $customerCode,
                amount: $validated['amount'],
                paymentMethod: $validated['payment_method'],
            );

            // Auto voucher তৈরি করি immediately!
            try {
                $this->voucherService->createReceiptVoucher($payment);
                $payment->update(['status' => 'received']);
            } catch (\Exception $e) {
                \Log::error('Voucher creation failed: ' . $e->getMessage());
                throw $e;
            }

            // Payment method অনুযায়ী handle করা
            if ($validated['payment_method'] === 'sslcommerz') {
                return $this->initiateSslcommerzPayment($payment);
            } elseif ($validated['payment_method'] === 'bkash') {
                return $this->initiateBkashPayment($payment);
            }

            return response()->json(['success' => true, 'payment_id' => $payment->id]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * SSLCommerz payment initiate করা
     */
    private function initiateSslcommerzPayment(CustomerPayment $payment)
    {
        $sslService = app(\App\Services\SSLCommerzPaymentService::class);

        try {
            $response = $sslService->initiatePayment(
                customerId: $payment->customer_id,
                amount: $payment->amount,
                description: "Payment - {$payment->reference_id}",
                successUrl: route('customer-payment.sslcommerz.success'),
                failUrl: route('customer-payment.sslcommerz.fail'),
                cancelUrl: route('customer-payment.sslcommerz.cancel'),
                ipnUrl: route('customer-payment.sslcommerz.ipn'),
                externalData: [
                    'payment_id' => $payment->id,
                    'reference_id' => $payment->reference_id,
                ]
            );

            $payment->update([
                'metadata' => $response,
            ]);

            return response()->json([
                'success' => true,
                'redirect_url' => $response['redirect_url'] ?? null,
                'session_id' => $response['sessionId'] ?? null,
            ]);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Bkash manual payment
     */
    private function initiateBkashPayment(CustomerPayment $payment)
    {
        return response()->json([
            'success' => true,
            'payment_id' => $payment->id,
            'redirect_url' => route('customer-payment.bkash.form', $payment->id),
        ]);
    }

    /**
     * Bkash payment form
     * GET /customer/pay/bkash/{payment_id}
     */
    public function bkashForm(CustomerPayment $payment)
    {
        return view('customer.payment.bkash-form', [
            'payment' => $payment,
        ]);
    }

    /**
     * Bkash payment submit করা
     * POST /customer/pay/bkash/{payment_id}
     */
    public function submitBkashPayment(Request $request, CustomerPayment $payment)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string|max:50',
            'sender_number' => 'required|string|max:20',
        ]);

        try {
            $payment->update([
                'transaction_reference' => $validated['transaction_id'],
                'status' => 'received',
                'metadata' => [
                    'sender_number' => $validated['sender_number'],
                    'receiver_number' => config('bkash.number'),
                ],
            ]);

            // Email পাঠানো
            $this->paymentService->sendPaymentReceivedEmail($payment);
            
            // Admin কে notify করা
            $this->paymentService->notifyAdminOfPayment($payment);

            return redirect()->route('customer-payment.success', $payment->id)
                ->with('success', 'Payment received. Voucher created!');
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    /**
     * Payment success page
     */
    public function success(CustomerPayment $payment)
    {
        return view('customer.payment.success', [
            'payment' => $payment,
        ]);
    }

    /**
     * SSLCommerz success callback
     */
    public function sslcommerzSuccess(Request $request)
    {
        $paymentId = $request->query('payment_id');
        $payment = CustomerPayment::findOrFail($paymentId);

        try {
            $this->paymentService->sendPaymentReceivedEmail($payment);
            $this->paymentService->notifyAdminOfPayment($payment);

            return view('customer.payment.success', [
                'payment' => $payment,
                'message' => 'Payment received successfully! Voucher has been created.',
            ]);
        } catch (\Exception $e) {
            return view('customer.payment.error', [
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * SSLCommerz IPN webhook
     */
    public function ipnWebhook(Request $request)
    {
        $paymentId = $request->input('val_id');
        $status = $request->input('status');

        $payment = CustomerPayment::where('transaction_reference', $paymentId)->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        try {
            if ($status === 'VALID') {
                $this->paymentService->sendPaymentReceivedEmail($payment);
                return response()->json(['success' => true]);
            } else {
                $this->paymentService->rejectPayment($payment, 'SSLCommerz validation failed');
                return response()->json(['success' => false]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Download payment receipt PDF
     */
    public function downloadReceipt(CustomerPayment $payment)
    {
        try {
            $pdfService = app(\App\Services\PaymentReceiptPdfService::class);
            return $pdfService->download($payment);
        } catch (\Exception $e) {
            return back()->withError('Failed to generate receipt: ' . $e->getMessage());
        }
    }

    /**
     * Admin Routes (Optional - for verification later)
     */
    
    public function adminList()
    {
        $payments = CustomerPayment::with('customer', 'voucher')
            ->where('company_id', session('company_id'))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.customer-payments.index', [
            'payments' => $payments,
        ]);
    }

    public function adminShow(CustomerPayment $payment)
    {
        return view('admin.customer-payments.show', [
            'payment' => $payment,
        ]);
    }

    public function verify(CustomerPayment $payment)
    {
        try {
            $this->paymentService->verifyPayment($payment, auth()->id() ?? 1);
            return back()->with('success', 'Payment verified!');
        } catch (\Exception $e) {
            return back()->withError('Verification failed: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, CustomerPayment $payment)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->paymentService->rejectPayment($payment, $validated['reason']);
            return back()->with('success', 'Payment rejected.');
        } catch (\Exception $e) {
            return back()->withError('Rejection failed: ' . $e->getMessage());
        }
    }
}