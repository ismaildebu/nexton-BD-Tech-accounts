<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SSLCommerzPaymentService
{
    public function __construct(private readonly SubscriptionService $subscriptionService)
    {
        $this->storeId = (string) config('sslcommerz.store_id');
        $this->storePassword = (string) config('sslcommerz.store_password');
        $this->sandboxMode = (bool) config('sslcommerz.sandbox_mode', true);
        $this->apiUrl = $this->sandboxMode
            ? (string) config('sslcommerz.sandbox_url')
            : (string) config('sslcommerz.live_url');
        $this->validationUrl = $this->sandboxMode
            ? (string) config('sslcommerz.sandbox_validation_url')
            : (string) config('sslcommerz.live_validation_url');

        if ($this->storeId === '' || $this->storePassword === '') {
            throw new RuntimeException('SSLCommerz credentials not configured in .env');
        }
    }

    private string $storeId;
    private string $storePassword;
    private string $apiUrl;
    private string $validationUrl;
    private bool $sandboxMode;



    /**
     * Create a gateway session.
     *
     * The gateway answers with JSON (sessionkey + GatewayPageURL). The old
     * "sessionkey=..." text format is still accepted as a fallback.
     *
     * @return array{0: string, 1: string} [sessionKey, redirectUrl]
     */
    private function createSession(array $paymentData): array
    {
        $response = Http::asForm()->timeout(15)->post($this->apiUrl, $paymentData)->throw();
        $json = $response->json();

        $sessionKey = is_array($json) ? (string) ($json['sessionkey'] ?? '') : '';

        if ($sessionKey === '' && preg_match('/sessionkey=([a-zA-Z0-9]+)/', $response->body(), $m) === 1) {
            $sessionKey = $m[1];
        }

        if ($sessionKey === '') {
            throw new RuntimeException('Invalid response from SSLCommerz.');
        }

        $fallback = ($this->sandboxMode
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com') . "/customer/pay/{$sessionKey}";

        $gatewayUrl = is_array($json) ? (string) ($json['GatewayPageURL'] ?? '') : '';
        $host = (string) parse_url($gatewayUrl, PHP_URL_HOST);
        $trusted = str_starts_with($gatewayUrl, 'https://')
            && ($host === 'sslcommerz.com' || str_ends_with($host, '.sslcommerz.com'));

        return [$sessionKey, $trusted ? $gatewayUrl : $fallback];
    }

    public function initiateCustomerPayment(
    int $customerId,
    string $amount,
    string $description,
    string $successUrl,
    string $failUrl,
    string $cancelUrl,
    string $ipnUrl,
    array $externalData = [],
): array {
    $transactionId = 'CUS-' . $customerId . '-' . bin2hex(random_bytes(8));

    $paymentData = [
        'store_id' => $this->storeId,
        'store_passwd' => $this->storePassword,
        'total_amount' => $amount,
        'currency' => 'BDT',
        'tran_id' => $transactionId,
        'success_url' => $successUrl,
        'fail_url' => $failUrl,
        'cancel_url' => $cancelUrl,
        'ipn_url' => $ipnUrl,
        'emi_option' => 0,

        'cus_name' => 'Customer ' . $customerId,
        'cus_email' => '',
        'cus_phone' => '',
        'cus_add1' => 'Bangladesh',

        'ship_name' => 'Customer ' . $customerId,
        'ship_email' => '',
        'ship_phone' => '',
        'ship_add1' => 'Bangladesh',

        'product_name' => $description,
        'product_category' => 'Payment',
        'product_profile' => 'service',
    ];

    [$sessionKey, $redirectUrl] = $this->createSession($paymentData);

    return [
        'redirect_url' => $redirectUrl,
        'sessionId' => $sessionKey,
        'transaction_id' => $transactionId,
        'external_data' => $externalData,
    ];
}

    public function initiatePayment(
        int $subscriptionId,
        string $amount,
        string $currency = 'BDT',
        string $userEmail = '',
        string $userName = '',
        string $userPhone = '',
    ): string {
        $transactionId = 'SUB-' . $subscriptionId . '-' . bin2hex(random_bytes(8));
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

        [$sessionKey, $redirectUrl] = $this->createSession($paymentData);

        $subscription = Subscription::query()->findOrFail($subscriptionId);
        $subscription->update([
            'metadata' => array_merge($subscription->metadata ?? [], [
                'transaction_id' => $transactionId,
                'session_key' => $sessionKey,
            ]),
        ]);

        return $redirectUrl;
    }

    /** Verify the callback against SSLCommerz, never against the callback alone. */
    public function verifyPayment(array $data, string $expectedAmount, string $expectedCurrency = 'BDT'): bool
    {
        $valId = (string) ($data['val_id'] ?? '');
        $transactionId = (string) ($data['tran_id'] ?? '');
        if ($valId === '' || $transactionId === '') {
            return false;
        }

        try {
            $remote = Http::asForm()->timeout(15)->post($this->validationUrl, [
                'val_id' => $valId,
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'format' => 'json',
            ])->throw()->json();
        } catch (\Throwable) {
            return false;
        }

        // VALID = first validation, VALIDATED = already validated before (retry-safe).
        if (!in_array($remote['status'] ?? null, ['VALID', 'VALIDATED'], true)) {
            return false;
        }

        return hash_equals($transactionId, (string) ($remote['tran_id'] ?? ''))
            && strcasecmp($expectedCurrency, (string) ($remote['currency'] ?? '')) === 0
            && bccomp($expectedAmount, (string) ($remote['amount'] ?? '0'), 2) === 0;
    }

    /** Mark one verified transaction paid exactly once and activate its pending subscription. */
    public function handlePaymentSuccess(array $data): ?SubscriptionPayment
    {
        $transactionId = (string) ($data['tran_id'] ?? '');
        if ($transactionId === '') {
            return null;
        }

        return DB::transaction(function () use ($data, $transactionId): ?SubscriptionPayment {
            $subscription = Subscription::query()
                ->whereJsonContains('metadata->transaction_id', $transactionId)
                ->lockForUpdate()
                ->first();

            if ($subscription === null) {
                return null;
            }

            $payment = $subscription->payments()
                ->where('transaction_reference', $transactionId)
                ->lockForUpdate()
                ->first();
            if ($payment?->status === SubscriptionPayment::STATUS_PAID) {
                return $payment;
            }

            $expectedAmount = number_format((float) $subscription->plan->price, 2, '.', '');
            $currency = (string) ($data['currency'] ?? 'BDT');
            if (!$this->verifyPayment($data, $expectedAmount, $currency)) {
                return null;
            }

            $payment ??= $subscription->payments()->where('status', SubscriptionPayment::STATUS_PENDING)->latest('id')->lockForUpdate()->first();
            if ($payment === null || bccomp((string) $payment->amount, $expectedAmount, 2) !== 0) {
                return null;
            }

            $payment->update([
                'amount' => $expectedAmount,
                'currency' => $currency,
                'status' => SubscriptionPayment::STATUS_PAID,
                'payment_method' => 'sslcommerz',
                'transaction_reference' => $transactionId,
                'paid_at' => now(),
                'metadata' => [
                    'val_id' => $data['val_id'] ?? null,
                    'bank_tran_id' => $data['bank_tran_id'] ?? null,
                    'card_type' => $data['card_type'] ?? null,
                    'card_last4' => substr((string) ($data['card_number'] ?? ''), -4),
                ],
            ]);

            $this->subscriptionService->activatePendingSubscription($subscription);

            return $payment->refresh();
        });
    }

    public function handlePaymentFailure(array $data): void
    {
        $transactionId = (string) ($data['tran_id'] ?? '');
        if ($transactionId === '') {
            return;
        }

        $subscription = Subscription::query()
            ->whereJsonContains('metadata->transaction_id', $transactionId)
            ->first();
        if ($subscription === null) {
            return;
        }

        $payment = $subscription->payments()
            ->where(function ($query) use ($transactionId): void {
                $query->where('transaction_reference', $transactionId)
                    ->orWhere('status', SubscriptionPayment::STATUS_PENDING);
            })
            ->latest('id')
            ->first();

        if ($payment !== null && $payment->status !== SubscriptionPayment::STATUS_PAID) {
            $payment->update([
                'status' => SubscriptionPayment::STATUS_FAILED,
                'payment_method' => 'sslcommerz',
                'transaction_reference' => $transactionId,
                'metadata' => ['reason' => $data['status'] ?? 'unknown'],
            ]);
            if ($subscription?->status === Subscription::STATUS_PENDING) {
                $subscription->update(['status' => Subscription::STATUS_CANCELLED, 'cancelled_at' => now()]);
            }
        }
    }
}
