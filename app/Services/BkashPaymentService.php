<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class BkashPaymentService
{
    private string $baseUrl;
    private string $appKey;
    private string $appSecret;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->baseUrl   = config('bkash.base_url');
        $this->appKey    = config('bkash.app_key');
        $this->appSecret = config('bkash.app_secret');
        $this->username  = config('bkash.username');
        $this->password  = config('bkash.password');
    }

    // ─── Token Management ────────────────────────────────────────────────────

    public function getToken(): string
    {
        return Cache::remember('bkash_token', 3500, function () {
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'username'      => $this->username,
                'password'      => $this->password,
            ])->post("{$this->baseUrl}/tokenized/checkout/token/grant", [
                'app_key'    => $this->appKey,
                'app_secret' => $this->appSecret,
            ]);

            if (! $response->successful()) {
                throw new Exception('bKash token grant failed: ' . $response->body());
            }

            return $response->json('id_token');
        });
    }

    private function refreshToken(): string
    {
        Cache::forget('bkash_token');
        return $this->getToken();
    }

    // ─── Create Payment ───────────────────────────────────────────────────────

    public function createPayment(array $data): array
    {
        $token = $this->getToken();

        $response = Http::withHeaders($this->headers($token))
            ->post("{$this->baseUrl}/tokenized/checkout/create", [
                'mode'                  => '0011',   // Checkout URL mode
                'payerReference'        => $data['customer_phone'],
                'callbackURL'           => route('bkash.callback'),
                'amount'                => number_format($data['amount'], 2, '.', ''),
                'currency'              => 'BDT',
                'intent'                => 'sale',
                'merchantInvoiceNumber' => $data['merchant_invoice_number'],
            ]);

        if (! $response->successful()) {
            Log::error('bKash create payment failed', ['response' => $response->body()]);
            throw new Exception('bKash payment creation failed');
        }

        return $response->json();
    }

    // ─── Execute Payment ─────────────────────────────────────────────────────

    public function executePayment(string $paymentId): array
    {
        $token = $this->getToken();

        $response = Http::withHeaders($this->headers($token))
            ->post("{$this->baseUrl}/tokenized/checkout/execute", [
                'paymentID' => $paymentId,
            ]);

        if (! $response->successful()) {
            throw new Exception('bKash execute payment failed: ' . $response->body());
        }

        return $response->json();
    }

    // ─── Query Payment ────────────────────────────────────────────────────────

    public function queryPayment(string $paymentId): array
    {
        $token = $this->getToken();

        $response = Http::withHeaders($this->headers($token))
            ->post("{$this->baseUrl}/tokenized/checkout/payment/status", [
                'paymentID' => $paymentId,
            ]);

        return $response->json();
    }

    // ─── Refund Payment ───────────────────────────────────────────────────────

    public function refundPayment(string $paymentId, string $trxId, float $amount, string $reason): array
    {
        $token = $this->getToken();

        $response = Http::withHeaders($this->headers($token))
            ->post("{$this->baseUrl}/tokenized/checkout/payment/refund", [
                'paymentID'  => $paymentId,
                'trxID'      => $trxId,
                'amount'     => number_format($amount, 2, '.', ''),
                'currency'   => 'BDT',
                'reason'     => $reason,
            ]);

        return $response->json();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function headers(string $token): array
    {
        return [
            'Authorization' => $token,
            'X-APP-Key'     => $this->appKey,
            'Content-Type'  => 'application/json',
        ];
    }
}