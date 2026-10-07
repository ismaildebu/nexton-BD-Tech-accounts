<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Models\MediaCollection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-collections.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $companyId = session('company_id');

        return [
            'media_party_id' => [
                'required',
                'integer',
                Rule::exists('media_parties', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            // The receiving Account (Cash/Bank/...) — reuses the existing chart of accounts.
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_method'   => ['required', Rule::in([
                MediaCollection::METHOD_CASH,
                MediaCollection::METHOD_BANK,
                MediaCollection::METHOD_MOBILE_BANKING,
                MediaCollection::METHOD_CHEQUE,
                MediaCollection::METHOD_OTHER,
            ])],
            'collection_date' => ['required', 'date'],
            'reference'        => ['nullable', 'string', 'max:150'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}
