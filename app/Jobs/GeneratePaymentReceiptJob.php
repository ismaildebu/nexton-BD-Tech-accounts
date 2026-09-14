<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BkashPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class GeneratePaymentReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public readonly BkashPayment $payment) {}

    public function handle(): void
    {
        // TODO: PDF receipt generate করুন
        Log::info('Receipt queued', [
            'trx_id'     => $this->payment->trx_id,
            'amount'     => $this->payment->amount,
            'invoice_id' => $this->payment->invoice_id,
        ]);
    }
}