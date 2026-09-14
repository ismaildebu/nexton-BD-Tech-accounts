<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BkashPayment;
use App\Notifications\BkashPaymentReceivedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SendBkashPaymentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 15;

    public function __construct(public readonly BkashPayment $payment) {}

    public function handle(): void
    {
        $customer = $this->payment->invoice->customer;

        if ($customer->email) {
            $customer->notify(new BkashPaymentReceivedNotification($this->payment));
        }

        if ($customer->phone) {
            $msg = "প্রিয় {$customer->name}, বিকাশ পেমেন্ট সফল। "
                 . "Amount: {$this->payment->amount} BDT | TrxID: {$this->payment->trx_id}";
            // TODO: SMS gateway যোগ করুন
            Log::info('SMS pending', ['phone' => $customer->phone, 'msg' => $msg]);
        }
    }
}