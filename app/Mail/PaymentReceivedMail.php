<?php

namespace App\Mail;

use App\Models\CustomerPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomerPayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Received - {$this->payment->reference_id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-received',
            with: [
                'payment' => $this->payment,
                'invoice' => $this->payment->invoice,
                'customer' => $this->payment->customer,
            ]
        );
    }
}