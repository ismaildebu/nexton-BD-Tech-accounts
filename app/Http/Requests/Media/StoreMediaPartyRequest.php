<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Models\MediaParty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Covers both Agent and Hawker creation — `type` decides which.
 * There is deliberately no field here that links one party to another.
 */
class StoreMediaPartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-parties.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $companyId = session('company_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(MediaParty::TYPES)],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('media_parties', 'code')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'phone'            => ['nullable', 'string', 'max:30'],
            'alternate_phone'  => ['nullable', 'string', 'max:30'],
            'address'          => ['nullable', 'string', 'max:1000'],
            'area'             => ['nullable', 'string', 'max:150'],
            'account_id'       => ['nullable', Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('company_id', $companyId)->where('account_type', 'Asset')->where('nature', 'Customer'))],
            'opening_balance'  => ['nullable', 'numeric'],
            'balance_type'     => ['nullable', Rule::in([
                MediaParty::BALANCE_RECEIVABLE,
                MediaParty::BALANCE_PAYABLE,
                MediaParty::BALANCE_ADVANCE,
            ])],
            'free_percentage'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active'        => ['sometimes', 'boolean'],
        ];
    }
}
