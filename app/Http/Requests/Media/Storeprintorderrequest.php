<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                Rule::exists('publications', 'id')
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id')
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'order_date' => [
                'required',
                'date',
            ],

            'print_date' => [
                'nullable',
                'date',
                'after_or_equal:order_date',
            ],

            'buffer_percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'unit_printing_cost' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999.9999',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}