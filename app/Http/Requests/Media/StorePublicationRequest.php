<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-publications.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $companyId = session('company_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('publications', 'code')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'publication_type'         => ['nullable', 'string', 'max:100'],
            'selling_price'            => ['required', 'numeric', 'min:0'],
            'sales_account_id'         => ['nullable', Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('company_id', $companyId)->where('account_type', 'Income'))],
            'sales_return_account_id'  => ['nullable', Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('company_id', $companyId)->where('account_type', 'Income'))],
            'default_free_percentage'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active'                => ['sometimes', 'boolean'],
        ];
    }
}
