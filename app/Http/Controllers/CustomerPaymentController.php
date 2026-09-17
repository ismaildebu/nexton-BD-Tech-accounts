<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Services\CustomerPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerPaymentController extends Controller
{
    public function __construct(
        private CustomerPaymentService $paymentService,
    ) {}

    /**
     * Payment form দেখা (Customer Code এর জন্য)
     *
     * GET /customer/pay/{customerCode}
     */
    public function show(string $customerCode)
    {
        $companyId = $this->companyId();

        $customer = Customer::query()
            ->where('company_id', $companyId)
            ->where('customer_code', $customerCode)
            ->firstOrFail();

        return view('customer.payment.show', [
            'customer' => $customer,
            'customerCode' => $customerCode,
        ]);
    }

    /**
     * Payment initiate করা
     *
     * Payment initiate করা মানেই টাকা received নয়।
     * এই পর্যায়ে কোনো voucher তৈরি হবে না।
     *
     * POST /customer/pay/{customerCode}
     */
    public function initiatePayment(
        Request $request,
        string $customerCode
    ) {
        $companyId = $this->companyId();

        $customer = Customer::query()
            ->where('company_id', $companyId)
            ->where('customer_code', $customerCode)
            ->firstOrFail();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:bkash,sslcommerz,bank_transfer',
        ]);

        try {
            $payment = $this->paymentService->createPayment(
                companyId: $companyId,
                customerId: $customer->id,
                referenceId: $customer->customer_code,
                amount: (float) $validated['amount'],
                paymentMethod: $validated['payment_method'],
            );

            /*
             * Payment is still pending here.
             *
             * No voucher is created until actual payment
             * confirmation is received.
             */

            if ($validated['payment_method'] === 'sslcommerz') {
                return $this->initiateSslcommerzPayment($payment);
            }

            if ($validated['payment_method'] === 'bkash') {
                return $this->initiateBkashPayment($payment);
            }

            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'message' => 'Payment initiated and is awaiting confirmation.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Customer payment initiation failed.', [
                'company_id' => $companyId,
                'customer_code' => $customerCode,
                'payment_method' => $validated['payment_method'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * SSLCommerz payment initiate করা
     */
    private function initiateSslcommerzPayment(
        CustomerPayment $payment
    ) {
        $this->assertPaymentCompany($payment);

        $sslService = app(
            \App\Services\SSLCommerzPaymentService::class
        );

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
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'redirect_url' => $response['redirect_url'] ?? null,
                'session_id' => $response['sessionId'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $payment->update([
                'status' => 'failed',
                'notes' => $e->getMessage(),
            ]);

            Log::error('SSLCommerz payment initiation failed.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * bKash manual payment
     */
    private function initiateBkashPayment(
        CustomerPayment $payment
    ) {
        $this->assertPaymentCompany($payment);

        return response()->json([
            'success' => true,
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'redirect_url' => route(
                'customer-payment.bkash.form',
                $payment->id
            ),
        ]);
    }

    /**
     * bKash payment form
     *
     * GET /customer/pay/bkash/{payment_id}
     */
    public function bkashForm(CustomerPayment $payment)
    {
        $this->assertPaymentCompany($payment);

        return view('customer.payment.bkash-form', [
            'payment' => $payment,
        ]);
    }

    /**
     * bKash payment submit করা
     *
     * POST /customer/pay/bkash/{payment_id}
     *
     * Actual payment received হলে:
     * received → Receive Voucher
     */
    public function submitBkashPayment(
        Request $request,
        CustomerPayment $payment
    ) {
        $this->assertPaymentCompany($payment);

        $validated = $request->validate([
            'transaction_id' => 'required|string|max:50',
            'sender_number' => 'required|string|max:20',
        ]);

        try {
            $payment->update([
                'transaction_reference' => $validated['transaction_id'],
                'metadata' => array_merge(
                    $payment->metadata ?? [],
                    [
                        'sender_number' => $validated['sender_number'],
                        'receiver_number' => Company::query()
                            ->whereKey($payment->company_id)
                            ->value('bkash_number'),
                    ]
                ),
            ]);

            /*
             * Actual payment confirmation.
             *
             * markAsReceived() is responsible for:
             * - marking the payment as received
             * - creating the receipt voucher once
             */
            $payment = $this->paymentService->markAsReceived($payment);

            return redirect()
                ->route('customer-payment.success', $payment->id)
                ->with(
                    'success',
                    'Payment received and voucher created.'
                );
        } catch (\Throwable $e) {
            Log::error('bKash payment processing failed.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withError($e->getMessage());
        }
    }

    /**
     * Payment success page
     */
    public function success(CustomerPayment $payment)
    {
        $this->assertPaymentCompany($payment);

        return view('customer.payment.success', [
            'payment' => $payment,
        ]);
    }

    /**
     * SSLCommerz success callback
     *
     * Browser redirect is not treated as payment confirmation.
     * IPN/webhook confirms the payment.
     */
    public function sslcommerzSuccess(Request $request)
    {
        $paymentId = $request->query('payment_id');

        if (!$paymentId) {
            return view('customer.payment.error', [
                'message' => 'Payment reference is missing.',
            ]);
        }

        $payment = CustomerPayment::query()
            ->where('id', $paymentId)
            ->where('company_id', $this->companyId())
            ->firstOrFail();

        return view('customer.payment.success', [
            'payment' => $payment,
            'message' => in_array(
                $payment->status,
                ['received', 'verified'],
                true
            )
                ? 'Payment received successfully. Voucher has been created.'
                : 'Payment confirmation is pending.',
        ]);
    }

    /**
     * SSLCommerz IPN webhook
     *
     * VALID = actual gateway confirmation.
     */
    public function ipnWebhook(Request $request)
    {
        $paymentId = $request->input('payment_id');
        $transactionReference = $request->input('val_id');
        $status = strtoupper(
            (string) $request->input('status')
        );

        $paymentQuery = CustomerPayment::query()
            ->where('company_id', $this->companyId());

        if ($paymentId) {
            $paymentQuery->where('id', $paymentId);
        } elseif ($transactionReference) {
            $paymentQuery->where(
                'transaction_reference',
                $transactionReference
            );
        } else {
            return response()->json([
                'error' => 'Payment reference not found.',
            ], 400);
        }

        $payment = $paymentQuery->first();

        if (!$payment) {
            return response()->json([
                'error' => 'Payment not found.',
            ], 404);
        }

        try {
            if ($status === 'VALID') {
                $payment->update([
                    'transaction_reference' => $transactionReference
                        ?: $payment->transaction_reference,
                ]);

                /*
                 * Actual payment confirmation.
                 *
                 * markAsReceived() handles:
                 * - payment status
                 * - receipt voucher creation
                 * - duplicate protection
                 */
                $this->paymentService->markAsReceived($payment);

                return response()->json([
                    'success' => true,
                ]);
            }

            $this->paymentService->rejectPayment(
                $payment,
                'SSLCommerz validation failed'
            );

            return response()->json([
                'success' => false,
            ]);
        } catch (\Throwable $e) {
            Log::error('SSLCommerz IPN processing failed.', [
                'payment_id' => $payment->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download payment receipt PDF
     */
    public function downloadReceipt(CustomerPayment $payment)
    {
        $this->assertPaymentCompany($payment);

        try {
            $pdfService = app(
                \App\Services\PaymentReceiptPdfService::class
            );

            return $pdfService->download($payment);
        } catch (\Throwable $e) {
            Log::error('Payment receipt PDF generation failed.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withError(
                'Failed to generate receipt: ' . $e->getMessage()
            );
        }
    }

    /**
     * Admin payment list
     */
    public function adminList()
    {
        $payments = CustomerPayment::with('customer', 'voucher')
            ->where('company_id', $this->companyId())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.customer-payments.index', [
            'payments' => $payments,
        ]);
    }

    /**
     * Admin payment details
     */
    public function adminShow(CustomerPayment $payment)
    {
        $this->assertPaymentCompany($payment);

        return view('admin.customer-payments.show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Admin verification
     *
     * Verification is a payment confirmation event.
     * The service creates the receipt voucher once.
     */
    public function verify(CustomerPayment $payment)
    {
        $this->assertPaymentCompany($payment);

        try {
            $this->paymentService->verifyPayment(
                $payment,
                auth()->id() ?? 1
            );

            return back()->with(
                'success',
                'Payment verified and voucher created.'
            );
        } catch (\Throwable $e) {
            Log::error('Customer payment verification failed.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withError(
                'Verification failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Reject payment
     */
    public function reject(
        Request $request,
        CustomerPayment $payment
    ) {
        $this->assertPaymentCompany($payment);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->paymentService->rejectPayment(
                $payment,
                $validated['reason'] ?? null
            );

            return back()->with(
                'success',
                'Payment rejected.'
            );
        } catch (\Throwable $e) {
            Log::error('Customer payment rejection failed.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withError(
                'Rejection failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Current company ID.
     */
    private function companyId(): int
    {
        $companyId = session('company_id');

        if (!$companyId) {
            abort(403, 'Company context is missing.');
        }

        return (int) $companyId;
    }

    /**
     * Prevent cross-company access to a payment.
     */
    private function assertPaymentCompany(
        CustomerPayment $payment
    ): void {
        if ((int) $payment->company_id !== $this->companyId()) {
            abort(404);
        }
    }
}