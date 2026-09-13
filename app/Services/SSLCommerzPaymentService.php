<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * SSLCommerz Payment Gateway Integration
 * Used for collecting subscription payments in Bangladesh
 */
class SSLCommerzPaymentService
{
    private string $storeId;
    private string $storePassword;
    private string $apiUrl;
    private bool $sandboxMode;

    public function __construct()
    {
        $this->storeId = config('sslcommerz.store_id');
        $this->storePassword = config('sslcommerz.store_password');
        $this->sandboxMode = config('sslcommerz.sandbox_mode', true);
        
        $this->apiUrl = $this->sandboxMode 
            ? config('sslcommerz.sandbox_url')
            : config('sslcommerz.live_url');

        if (!$this->storeId || !$this->storePassword) {
            throw new RuntimeException('SSLCommerz credentials not configured in .env');
        }
    }

    /**
     * Initiate payment with SSLCommerz
     * Returns gateway URL for user to complete payment
     */
    public function initiatePayment(
        int $subscriptionId,
        string $amount,
        string $currency = 'BDT',
        string $userEmail = '',
        string $userName = '',
        string $userPhone = '',
    ): string {
        $transactionId = "SUB-{$subscriptionId}-" . time();

        $paymentData = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'total_amount' => $amount,
            'currency' => $currency,
            'tran_id' => $transactionId,
            'success_url' => route('billing.payment-success'),
            'fail_url' => route('billing.payment-failed'),
            'cancel_url' => route('billing.payment-cancelled'),
            'ipn_url' => route('billing.payment-ipn'),
            'emi_option' => 0,
            'cus_name' => $userName,
            'cus_email' => $userEmail,
            'cus_phone' => $userPhone,
            'cus_add1' => 'Bangladesh',
            'ship_name' => $userName,
            'ship_email' => $userEmail,
            'ship_phone' => $userPhone,
            'ship_add1' => 'Bangladesh',
            'product_name' => 'Subscription Payment',
            'product_category' => 'Service',
            'product_profile' => 'service',
        ];

        try {
            $response = Http::asForm()
                ->post($this->apiUrl, $paymentData)
                ->throw()
                ->body();

            // Parse response
            if (strpos($response, 'sessionkey') !== false) {
                // Extract session key from response
                preg_match('/sessionkey=([a-zA-Z0-9]+)/', $response, $matches);
                if (!empty($matches[1])) {
                    $sessionKey = $matches[1];
                    
                    // Store transaction reference in database
                    $subscription = \App\Models\Subscription::findOrFail($subscriptionId);
                    $subscription->update([
                        'metadata' => array_merge(
                            $subscription->metadata ?? [],
                            ['transaction_id' => $transactionId, 'session_key' => $sessionKey]
                        ),
                    ]);

                    // Redirect to gateway
                    return $this->sandboxMode
                        ? "https://sandbox.sslcommerz.com/customer/pay/{$sessionKey}"
                        : "https://securepay.sslcommerz.com/customer/pay/{$sessionKey}";
                }
            }

            throw new RuntimeException('Invalid response from SSLCommerz: ' . substr($response, 0, 100));
        } catch (\Exception $e) {
            throw new RuntimeException('SSLCommerz payment initiation failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify payment status with SSLCommerz
     */
    public function verifyPayment(string $transactionId, string $amount): bool
    {
        try {
            $response = Http::asForm()
                ->post("{$this->apiUrl}?ref=","")
                ->post(
                    str_replace('api.php', 'api.php', $this->apiUrl),
                    [
                        'store_id' => $this->storeId,
                        'store_passwd' => $this->storePassword,
                        'ref' => $transactionId,
                    ]
                )
                ->throw()
                ->body();

            return strpos($response, 'VALID') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Handle payment success callback
     */
    public function handlePaymentSuccess(array $data): ?SubscriptionPayment
    {
        $transactionId = $data['tran_id'] ?? null;
        $status = $data['status'] ?? null;

        if (!$transactionId || $status !== 'VALID') {
            return null;
        }

        // Find subscription by transaction ID in metadata
        $subscription = \App\Models\Subscription::whereJsonContains(
            'metadata->transaction_id',
            $transactionId
        )->first();

        if (!$subscription) {
            return null;
        }

        // Update or create payment record
        $payment = SubscriptionPayment::updateOrCreate(
            ['subscription_id' => $subscription->id],
            [
                'amount' => (string) ($data['amount'] ?? '0.00'),
                'currency' => $data['currency'] ?? 'BDT',
                'status' => SubscriptionPayment::STATUS_PAID,
                'payment_method' => 'sslcommerz',
                'transaction_reference' => $transactionId,
                'paid_at' => now(),
                'metadata' => [
                    'bank_tran_id' => $data['bank_tran_id'] ?? null,
                    'card_type' => $data['card_type'] ?? null,
                    'card_number' => substr($data['card_number'] ?? '', -4),
                ],
            ]
        );

        return $payment;
    }

    /**
     * Handle payment failure/cancellation
     */
    public function handlePaymentFailure(array $data): void
    {
        $transactionId = $data['tran_id'] ?? null;

        if (!$transactionId) {
            return;
        }

        $subscription = \App\Models\Subscription::whereJsonContains(
            'metadata->transaction_id',
            $transactionId
        )->first();

        if ($subscription) {
            SubscriptionPayment::updateOrCreate(
                ['subscription_id' => $subscription->id],
                [
                    'amount' => (string) ($data['amount'] ?? '0.00'),
                    'currency' => $data['currency'] ?? 'BDT',
                    'status' => SubscriptionPayment::STATUS_FAILED,
                    'payment_method' => 'sslcommerz',
                    'transaction_reference' => $transactionId,
                    'metadata' => ['reason' => $data['status'] ?? 'unknown'],
                ]
            );
        }
    }
}