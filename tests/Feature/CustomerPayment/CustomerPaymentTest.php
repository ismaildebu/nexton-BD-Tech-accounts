<?php

declare(strict_types=1);

namespace Tests\Feature\CustomerPayment;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\CustomerPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class CustomerPaymentTest extends TestCase
{
    use RefreshDatabase;

    private CustomerPaymentService $service;

    private Company $company;

    private Customer $customer;

    private User $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->service = app(CustomerPaymentService::class);

        $this->company = Company::factory()->create();

        $this->customer = Customer::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'phone' => '01700000000',
            'email' => 'customer@example.com',
            'customer_type' => 'Individual',
            'credit_limit' => 0,
            'opening_balance' => 0,
            'balance_type' => 'Receivable',
            'is_active' => true,
        ]);

        $this->verifier = User::factory()->create();
    }

    private function createInvoice(
        ?Company $company = null,
        ?Customer $customer = null,
        string $invoiceNumber = 'INV-2026-000001',
        string $totalAmount = '100.0000',
        string $paidAmount = '0.0000',
    ): Invoice {
        $company ??= $this->company;
        $customer ??= $this->customer;

        return Invoice::query()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'unpaid',
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'paid_at' => null,
        ]);
    }

    public function test_it_creates_pending_payment_for_correct_company_invoice(): void
    {
        $invoice = $this->createInvoice();

        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: $invoice->invoice_number,
            amount: 40.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-001',
        );

        $this->assertDatabaseHas('customer_payments', [
            'id' => $payment->id,
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'reference_id' => $invoice->invoice_number,
            'invoice_id' => $invoice->id,
            'amount' => '40.0000',
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'TXN-001',
            'status' => 'pending',
        ]);

        $this->assertSame(
            $invoice->id,
            $payment->invoice_id
        );

        $this->assertSame(
            'pending',
            $payment->status
        );
    }

    public function test_it_verifies_payment_and_updates_invoice_paid_amount(): void
    {
        $invoice = $this->createInvoice(
            totalAmount: '100.0000',
            paidAmount: '20.0000',
        );

        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: $invoice->invoice_number,
            amount: 30.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-002',
        );

        $verifiedPayment = $this->service->verifyPayment(
            $payment,
            $this->verifier->id,
        );

        $invoice->refresh();
        $verifiedPayment->refresh();

        $this->assertSame(
            'verified',
            $verifiedPayment->status
        );

        $this->assertSame(
            $this->verifier->id,
            $verifiedPayment->verified_by
        );

        $this->assertNotNull(
            $verifiedPayment->verified_at
        );

        $this->assertSame(
            '50.00',
            number_format((float) $invoice->paid_amount, 2, '.', '')
        );

        $this->assertSame(
            'partial',
            $invoice->status
        );

        $this->assertNull(
            $verifiedPayment->voucher_id
        );
    }

    public function test_it_marks_invoice_paid_when_payment_covers_remaining_amount(): void
    {
        $invoice = $this->createInvoice(
            totalAmount: '100.0000',
            paidAmount: '40.0000',
        );

        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: $invoice->invoice_number,
            amount: 60.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-003',
        );

        $verifiedPayment = $this->service->verifyPayment(
            $payment,
            $this->verifier->id,
        );

        $invoice->refresh();
        $verifiedPayment->refresh();

        $this->assertSame(
            'verified',
            $verifiedPayment->status
        );

        $this->assertSame(
            '100.00',
            number_format((float) $invoice->paid_amount, 2, '.', '')
        );

        $this->assertSame(
            'paid',
            $invoice->status
        );

        $this->assertNotNull(
            $invoice->paid_at
        );
    }

    public function test_it_rejects_overpayment(): void
    {
        $invoice = $this->createInvoice(
            totalAmount: '100.0000',
            paidAmount: '70.0000',
        );

        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: $invoice->invoice_number,
            amount: 40.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-004',
        );

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Payment amount exceeds the invoice outstanding amount.'
        );

        $this->service->verifyPayment(
            $payment,
            $this->verifier->id,
        );

        $invoice->refresh();

        $this->assertSame(
            '70.00',
            number_format((float) $invoice->paid_amount, 2, '.', '')
        );
    }

    public function test_it_rejects_customer_mismatch(): void
    {
        $invoice = $this->createInvoice();

        $otherCustomer = Customer::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Other Customer',
            'phone' => '01800000000',
            'email' => 'other@example.com',
            'customer_type' => 'Individual',
            'credit_limit' => 0,
            'opening_balance' => 0,
            'balance_type' => 'Receivable',
            'is_active' => true,
        ]);

        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $otherCustomer->id,
            referenceId: $invoice->invoice_number,
            amount: 20.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-005',
        );

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Payment customer does not match the invoice customer.'
        );

        $this->service->verifyPayment(
            $payment,
            $this->verifier->id,
        );
    }

    public function test_it_is_idempotent_when_verifying_an_already_verified_payment(): void
    {
        $invoice = $this->createInvoice(
            totalAmount: '100.0000',
            paidAmount: '0.0000',
        );

        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: $invoice->invoice_number,
            amount: 25.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-006',
        );

        $firstVerification = $this->service->verifyPayment(
            $payment,
            $this->verifier->id,
        );

        $invoice->refresh();

        $paidAmountAfterFirstVerification = $invoice->paid_amount;
        $verifiedAtAfterFirstVerification = $firstVerification->verified_at;

        $secondVerification = $this->service->verifyPayment(
            $firstVerification,
            $this->verifier->id,
        );

        $invoice->refresh();
        $secondVerification->refresh();

        $this->assertSame(
            '25.00',
            number_format(
                (float) $paidAmountAfterFirstVerification,
                2,
                '.',
                ''
            )
        );

        $this->assertSame(
            '25.00',
            number_format(
                (float) $invoice->paid_amount,
                2,
                '.',
                ''
            )
        );

        $this->assertSame(
            'verified',
            $secondVerification->status
        );

        $this->assertSame(
            $firstVerification->id,
            $secondVerification->id
        );

        $this->assertEquals(
            $verifiedAtAfterFirstVerification,
            $secondVerification->verified_at
        );
    }

    public function test_it_does_not_resolve_invoice_from_another_company(): void
    {
        $companyB = Company::factory()->create();

        $customerB = Customer::query()->create([
            'company_id' => $companyB->id,
            'name' => 'Company B Customer',
            'phone' => '01900000000',
            'email' => 'companyb@example.com',
            'customer_type' => 'Individual',
            'credit_limit' => 0,
            'opening_balance' => 0,
            'balance_type' => 'Receivable',
            'is_active' => true,
        ]);

        $invoiceB = $this->createInvoice(
            company: $companyB,
            customer: $customerB,
            invoiceNumber: 'INV-2026-000002',
            totalAmount: '200.0000',
            paidAmount: '0.0000',
        );

        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: $invoiceB->invoice_number,
            amount: 50.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-007',
        );

        $payment->refresh();

        $this->assertNull(
            $payment->invoice_id
        );

        $this->assertSame(
            $this->company->id,
            $payment->company_id
        );

        $this->assertSame(
            $this->customer->id,
            $payment->customer_id
        );

        $this->assertDatabaseMissing('customer_payments', [
            'id' => $payment->id,
            'invoice_id' => $invoiceB->id,
        ]);
    }
}