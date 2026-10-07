<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Services\CustomerPaymentService;
use App\Services\SSLCommerzPaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            'amount' => 'required|numeric|min:0.01|decimal:0,2',
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
     *
     * Our gateway transaction id (tran_id) is stored in transaction_reference.
     * The IPN resolves the payment from it, never from a session.
     */
    private function initiateSslcommerzPayment(
        CustomerPayment $payment
    ) {
        $this->assertPaymentCompany($payment);

        try {
            $sslService = app(SSLCommerzPaymentService::class);

            $response = $sslService->initiateCustomerPayment(
                customerId: $payment->customer_id,
                amount: bcadd((string) $payment->amount, '0', 2),
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
                'transaction_reference' => $response['transaction_id'],
                'metadata' => array_merge($payment->metadata ?? [], [
                    'gateway' => 'sslcommerz',
                    'session_id' => $response['sessionId'] ?? null,
                ]),
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
                'notes' => 'SSLCommerz initiation failed.',
            ]);

            Log::error('SSLCommerz payment initiation failed.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Could not start the online payment. Please try again.',
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
     * Customer-submitted details only; voucher is created on admin verification.
     */
    public function submitBkashPayment(
        Request $request,
        CustomerPayment $payment
    ) {
        $this->assertPaymentCompany($payment);

        $validated = $request->validate([
            'transaction_id' => ['required', 'string', 'regex:/^[A-Za-z0-9]{8,20}$/'],
            'sender_number' => ['required', 'string', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
        ]);

        try {
            /*
             * Submitting a bKash TrxID is a claim, not a confirmation.
             * The payment stays "pending" and NO voucher is created here;
             * the receipt voucher is posted when an admin verifies the payment.
             */
            $payment = $this->paymentService->submitManualBkashPayment(
                $payment,
                $validated['transaction_id'],
                $validated['sender_number']
            );

            return redirect()
                ->route('customer-payment.success', $payment->id)
                ->with(
                    'success',
                    'Payment details submitted. The payment will be confirmed after verification.'
                );
        } catch (\RuntimeException $e) {
            return back()
                ->withInput()
                ->withErrors(['transaction_id' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('bKash payment submission failed.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'transaction_id' => 'Could not submit the payment details. Please try again.',
                ]);
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
     * SSLCommerz success redirect (browser GET or POST).
     *
     * This is NOT a payment confirmation; only the validated IPN confirms.
     * It resolves the payment from our tran_id and sends the browser to the
     * normal (session-protected) success page.
     */
    public function sslcommerzSuccess(Request $request)
    {
        $tranId = trim((string) $request->input('tran_id'));

        if ($tranId === '') {
            return view('customer.payment.error', [
                'message' => 'Payment reference is missing.',
            ]);
        }

        $payment = CustomerPayment::query()
            ->where('payment_method', 'sslcommerz')
            ->where('transaction_reference', $tranId)
            ->firstOrFail();

        return redirect()->route('customer-payment.success', $payment->id);
    }

    public function sslcommerzFail(Request $request)
    {
        return view('customer.payment.error', [
            'message' => 'SSLCommerz payment failed.',
        ]);
    }

    public function sslcommerzCancel(Request $request)
    {
        return view('customer.payment.error', [
            'message' => 'SSLCommerz payment was cancelled.',
        ]);
    }

    /**
     * SSLCommerz IPN webhook (server-to-server, no session, no auth).
     *
     * - Only success notifications are processed, and only after the
     *   gateway's validation API confirms them (see
     *   CustomerPaymentService::confirmSslcommerzPayment()).
     * - FAILED/CANCELLED notifications are acknowledged but never change
     *   state: the customer may retry inside the same gateway session and
     *   succeed later, and an unauthenticated call must not be able to
     *   fail a payment.
     */
    public function ipnWebhook(Request $request)
    {
        $tranId = trim((string) $request->input('tran_id'));
        $valId = trim((string) $request->input('val_id'));
        $status = strtoupper((string) $request->input('status'));

        if ($tranId === '') {
            return response()->json(['error' => 'Payment reference not found.'], 400);
        }

        if (!in_array($status, ['VALID', 'VALIDATED'], true) || $valId === '') {
            Log::info('SSLCommerz IPN ignored (non-success notification).', [
                'tran_id' => $tranId,
                'status' => $status,
            ]);

            return response()->json(['success' => false, 'ignored' => true]);
        }

        try {
            $this->paymentService->confirmSslcommerzPayment(
                $request->only([
                    'tran_id',
                    'val_id',
                    'status',
                    'bank_tran_id',
                    'card_type',
                ]),
                app(SSLCommerzPaymentService::class)
            );

            return response()->json(['success' => true]);
        } catch (ModelNotFoundException) {
            return response()->json(['error' => 'Payment not found.'], 404);
        } catch (\DomainException $e) {
            Log::warning('SSLCommerz IPN rejected.', [
                'tran_id' => $tranId,
                'reason' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Payment could not be confirmed.'], 422);
        } catch (\Throwable $e) {
            Log::error('SSLCommerz IPN processing failed.', [
                'tran_id' => $tranId,
                'error' => $e->getMessage(),
            ]);

            // 5xx so the gateway retries later.
            return response()->json(['error' => 'Temporary processing error.'], 500);
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