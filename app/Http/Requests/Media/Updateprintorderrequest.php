<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Editing is only meaningful while the order is still Draft — the
 * controller enforces that status check before calling this; this
 * request only validates the shape of the data.
 */
class UpdatePrintOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-print-orders.edit') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $companyId = session('company_id');

        return [
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'order_date'       => ['required', 'date'],
            'print_date'       => ['nullable', 'date', 'after_or_equal:order_date'],
            'ordered_quantity' => ['required', 'integer', 'min:1'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}
