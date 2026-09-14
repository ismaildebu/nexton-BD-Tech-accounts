<?php
namespace App\Jobs;

use App\Models\BkashPayment;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendPaymentNotificationJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public BkashPayment $payment) {}

    public function handle(): void
    {
        $customer = $this->payment->arInvoice->customer;

        // Email
        if ($customer->email) {
            $customer->notify(new PaymentReceivedNotification($this->payment));
        }

        // SMS (যেকোনো SMS gateway)
        if ($customer->phone) {
            app(\App\Services\SmsService::class)->send(
                $customer->phone,
                "আপনার পেমেন্ট পাওয়া গেছে। Amount: {$this->payment->amount} BDT | TrxID: {$this->payment->trx_id}"
            );
        }
    }
}