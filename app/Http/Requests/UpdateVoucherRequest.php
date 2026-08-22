<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        /** @var Transaction|null $transaction */
        $transaction = $this->route('transaction');

        if ($transaction === null) {
            return false;
        }

        return $transaction->isDraft();
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'financial_year_id'             => ['required', 'integer', 'exists:financial_years,id'],
            'voucher_type_id'               => ['required', 'integer', 'exists:voucher_types,id'],
            'voucher_date'                  => ['required', 'date'],
            'reference_number'              => ['nullable', 'string', 'max:100'],
            'narration'                     => ['nullable', 'string', 'max:1000'],

            'details'                       => ['required', 'array', 'min:2'],
            'details.*.account_id'          => ['required', 'integer', 'exists:accounts,id'],
            'details.*.description'         => ['nullable', 'string', 'max:500'],
            'details.*.debit_amount'        => ['required', 'numeric', 'min:0'],
            'details.*.credit_amount'       => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'financial_year_id.required'        => 'Financial year is required.',
            'financial_year_id.exists'          => 'Selected financial year is invalid.',
            'voucher_type_id.required'          => 'Voucher type is required.',
            'voucher_type_id.exists'            => 'Selected voucher type is invalid.',
            'voucher_date.required'             => 'Voucher date is required.',
            'voucher_date.date'                 => 'Voucher date must be a valid date.',
            'details.required'                  => 'At least two ledger lines are required.',
            'details.min'                       => 'A voucher must have at least two ledger lines.',
            'details.*.account_id.required'     => 'Account is required for each ledger line.',
            'details.*.account_id.exists'       => 'Selected account is invalid.',
            'details.*.debit_amount.required'   => 'Debit amount is required.',
            'details.*.debit_amount.numeric'    => 'Debit amount must be a number.',
            'details.*.debit_amount.min'        => 'Debit amount cannot be negative.',
            'details.*.credit_amount.required'  => 'Credit amount is required.',
            'details.*.credit_amount.numeric'   => 'Credit amount must be a number.',
            'details.*.credit_amount.min'       => 'Credit amount cannot be negative.',
        ];
    }
}