<?php

namespace App\Services;

use App\Models\CustomerPayment;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentReceiptPdfService
{
    /**
     * Generate PDF receipt for payment
     */
    public function generateReceipt(CustomerPayment $payment)
    {
        $data = [
            'payment' => $payment,
            'invoice' => $payment->invoice,
            'customer' => $payment->customer,
            'voucher' => $payment->voucher,
        ];

        $pdf = Pdf::loadView('receipts.payment-receipt', $data);
        $pdf->setPaper('A4');
        $pdf->setOption(['dpi' => 150, 'defaultFont' => 'sans-serif']);

        return $pdf;
    }

    /**
     * Save PDF to storage
     */
    public function savePdf(CustomerPayment $payment): string
    {
        $filename = "payment-receipt-{$payment->id}.pdf";
        $path = storage_path("app/receipts/{$filename}");

        // Create directory if not exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $this->generateReceipt($payment)->save($path);

        return $path;
    }

    /**
     * Download as stream
     */
    public function download(CustomerPayment $payment)
    {
        return $this->generateReceipt($payment)
            ->download("payment-receipt-{$payment->id}.pdf");
    }
}