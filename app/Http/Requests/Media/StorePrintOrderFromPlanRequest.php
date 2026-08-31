<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used when creating a Print Order from an Approved Print Plan.
 * No `ordered_quantity` field — that always comes from the plan's
 * final_quantity via PrintOrderService::createFromPlan(), never
 * re-entered by hand.
 */
class StorePrintOrderFromPlanRequest extends FormRequest
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
            // Printing press — reuses the existing Vendor table.
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'order_date' => ['required', 'date'],
            'print_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ];
    }
}
