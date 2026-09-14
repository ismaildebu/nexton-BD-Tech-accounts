// app/Services/JournalEntryService.php — bKash receipt method

public function createBkashReceiptEntry(
    ArInvoice    $invoice,
    float        $amount,
    string       $trxId,
    BkashPayment $bkashPayment,
): void {
    /*
     * ডাবল এন্ট্রি:
     *   DR  bKash Account (Asset)      ← টাকা bKash-এ আসলো
     *   CR  Accounts Receivable        ← দেনাদার পরিশোধ করলো
     */
    $journalVoucher = JournalVoucher::create([
        'company_id'   => $invoice->company_id,
        'voucher_type' => 'receipt',
        'voucher_no'   => $this->generateVoucherNo($invoice->company_id, 'RV'),
        'date'         => now()->toDateString(),
        'narration'    => "bKash payment received | Invoice #{$invoice->invoice_no} | TrxID: {$trxId}",
        'reference'    => $trxId,
        'created_by'   => null,  // System-generated
        'is_auto'      => true,
    ]);

    // DR: bKash Account
    $journalVoucher->entries()->create([
        'account_id'  => $this->getBkashAccountId($invoice->company_id),
        'debit'       => $amount,
        'credit'      => 0,
        'description' => "bKash received - {$trxId}",
    ]);

    // CR: Accounts Receivable
    $journalVoucher->entries()->create([
        'account_id'  => $invoice->customer->ar_account_id,
        'debit'       => 0,
        'credit'      => $amount,
        'description' => "Invoice #{$invoice->invoice_no} settled",
    ]);
}