<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\BkashPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BkashPaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly BkashPayment $payment) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->payment->invoice;

        return (new MailMessage())
            ->subject("পেমেন্ট নিশ্চিতকরণ — Invoice #{$invoice->invoice_number}")
            ->greeting("প্রিয় {$notifiable->name},")
            ->line("আপনার বিকাশ পেমেন্ট সফলভাবে গ্রহণ করা হয়েছে।")
            ->line("**পরিমাণ:** {$this->payment->amount} BDT")
            ->line("**TrxID:** {$this->payment->trx_id}")
            ->line("**Invoice:** #{$invoice->invoice_number}")
            ->line("**তারিখ:** {$this->payment->paid_at?->format('d M Y, h:i A')}")
            ->action('Invoice দেখুন', route('invoices.show', $invoice));
    }
}