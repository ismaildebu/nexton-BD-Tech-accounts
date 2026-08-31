<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMediaReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-returns.create') ?? false;
    }

    public function rules(): array
    {
        $companyId = session('company_id');

        return [
            'publication_id' => [
                'required',
                'integer',
                Rule::exists('publications', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'media_distribution_id' => [
                'nullable',
                'integer',
                Rule::exists('media_distributions', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'return_date' => ['required', 'date'],
            'notes'       => ['nullable', 'string', 'max:1000'],

            'items'                        => ['required', 'array', 'min:1'],
            'items.*.media_party_id'       => [
                'required',
                'integer',
                'distinct',
                Rule::exists('media_parties', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'items.*.paid_return_quantity' => ['required', 'integer', 'min:0'],
            'items.*.free_return_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $items = collect($this->input('items', []));
            $total = $items->sum(
                fn ($i) => (int) ($i['paid_return_quantity'] ?? 0) + (int) ($i['free_return_quantity'] ?? 0)
            );

            if ($items->isNotEmpty() && $total <= 0) {
                $v->errors()->add('items', 'At least one item must have a return quantity greater than zero.');
            }
        });
    }
}
