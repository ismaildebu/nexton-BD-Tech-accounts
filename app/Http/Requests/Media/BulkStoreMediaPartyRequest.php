<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates bulk creation of Agents and Hawkers together.
 * Journalists use BulkStoreJournalistRequest instead.
 */
class BulkStoreMediaPartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-parties.create') ?? false;
    }

    public function rules(): array
    {
        $companyId = session('company_id');

        $partyRules = [
            '*.name'             => ['required', 'string', 'max:255'],
            '*.code'             => ['required', 'string', 'max:50', 'distinct'],
            '*.phone'            => ['nullable', 'string', 'max:30'],
            '*.alternate_phone'  => ['nullable', 'string', 'max:30'],
            '*.address'          => ['nullable', 'string', 'max:1000'],
            '*.area'             => ['nullable', 'string', 'max:150'],
            '*.free_percentage'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            '*.opening_balance'  => ['nullable', 'numeric'],
            '*.balance_type'     => ['nullable', Rule::in(['Receivable', 'Payable', 'Advance'])],
            '*.account_id'       => [
                'nullable',
                Rule::exists('accounts', 'id')->where(
                    fn($q) => $q->where('company_id', $companyId)
                                ->where('account_type', 'Asset')
                                ->where('nature', 'Customer')
                ),
            ],
            '*.is_active'        => ['sometimes', 'boolean'],
        ];

        return array_merge(
            ['agents'  => ['array'], 'hawkers' => ['array']],
            collect($partyRules)->mapWithKeys(fn($v, $k) => ["agents.$k"  => $v])->toArray(),
            collect($partyRules)->mapWithKeys(fn($v, $k) => ["hawkers.$k" => $v])->toArray(),
        );
    }

    public function messages(): array
    {
        return [
            'agents.*.name.required'   => 'Agent-এর নাম আবশ্যক।',
            'agents.*.code.required'   => 'Agent-এর কোড আবশ্যক।',
            'agents.*.code.distinct'   => 'Agent কোড list-এ পুনরাবৃত্তি হয়েছে।',
            'hawkers.*.name.required'  => 'Hawker-এর নাম আবশ্যক।',
            'hawkers.*.code.required'  => 'Hawker-এর কোড আবশ্যক।',
            'hawkers.*.code.distinct'  => 'Hawker কোড list-এ পুনরাবৃত্তি হয়েছে।',
        ];
    }
}
