<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates bulk creation of Journalists.
 */
class BulkStoreJournalistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-parties.create') ?? false;
    }

    public function rules(): array
    {
        $companyId = session('company_id');

        return [
            'journalists'                      => ['required', 'array', 'min:1'],
            'journalists.*.name'               => ['required', 'string', 'max:255'],
            'journalists.*.code'               => ['required', 'string', 'max:50', 'distinct'],
            'journalists.*.beat'               => ['nullable', 'string', 'max:100'],
            'journalists.*.phone'              => ['nullable', 'string', 'max:30'],
            'journalists.*.email'              => ['nullable', 'email', 'max:255'],
            'journalists.*.alternate_phone'    => ['nullable', 'string', 'max:30'],
            'journalists.*.area'               => ['nullable', 'string', 'max:150'],
            'journalists.*.district'           => ['nullable', 'string', 'max:100'],
            'journalists.*.media_outlet'       => ['nullable', 'string', 'max:255'],
            'journalists.*.free_percentage'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'journalists.*.commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'journalists.*.opening_balance'    => ['nullable', 'numeric'],
            'journalists.*.balance_type'       => ['nullable', Rule::in(['Receivable', 'Payable', 'Advance'])],
            'journalists.*.account_id'         => [
                'nullable',
                Rule::exists('accounts', 'id')->where(
                    fn($q) => $q->where('company_id', $companyId)
                                ->where('account_type', 'Asset')
                                ->where('nature', 'Customer')
                ),
            ],
            'journalists.*.is_active'          => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'journalists.required'              => 'কমপক্ষে একজন সাংবাদিক দিন।',
            'journalists.*.name.required'       => 'সাংবাদিকের নাম আবশ্যক।',
            'journalists.*.code.required'       => 'সাংবাদিকের কোড আবশ্যক।',
            'journalists.*.code.distinct'       => 'কোড পুনরাবৃত্তি হয়েছে।',
            'journalists.*.email.email'         => 'সঠিক ইমেইল ঠিকানা দিন।',
            'journalists.*.commission_percent.numeric' => 'কমিশন % সংখ্যা হতে হবে।',
        ];
    }
}
