<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrintPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-print-planning.create') ?? false;
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
            'plan_date' => ['required', 'date'],

            'previous_distribution_quantity' => ['nullable', 'integer', 'min:0'],
            'average_distribution_quantity'  => ['nullable', 'integer', 'min:0'],
            'expected_paid_quantity'         => ['nullable', 'integer', 'min:0'],
            'expected_free_quantity'         => ['nullable', 'integer', 'min:0'],
            'expected_total_quantity'        => ['nullable', 'integer', 'min:0'],
            'buffer_quantity'                => ['nullable', 'integer', 'min:0'],
            'recommended_quantity'           => ['nullable', 'integer', 'min:0'],
            'adjusted_quantity'              => ['nullable', 'integer', 'min:0'],
            'adjustment_reason'              => ['nullable', 'string', 'max:1000', 'required_with:adjusted_quantity'],
        ];
    }
}
