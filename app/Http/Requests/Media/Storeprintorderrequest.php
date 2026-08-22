<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ad-hoc Print Order (no Print Plan behind it) — e.g. a reprint.
 * Order-linked-to-plan creation uses StorePrintOrderFromPlanRequest
 * instead, where ordered_quantity is never hand-entered.
 */
class StorePrintOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-print-orders.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $companyId = session('company_id');

        return [
            'publication_id' => [
                'required',
                'integer',
                Rule::exists('publications', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            // Printing press — reuses the existing Vendor table.
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'order_date'         => ['required', 'date'],
            'print_date'         => ['nullable', 'date', 'after_or_equal:order_date'],
            'ordered_quantity'   => ['required', 'integer', 'min:1'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ];
    }
}
