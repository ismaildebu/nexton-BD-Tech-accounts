<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Foundation-level validation only. Deliberately permissive on array
 * size (no artificial cap) since headers must support 100+ party
 * lines per day.
 *
 * NOTE: there is intentionally NO items.*.free_percentage input here.
 * Free percentage is never client-supplied — DistributionService always
 * resolves it server-side via FreePercentageResolver's Party -> Publication
 * -> System priority chain, so a client can no longer send an arbitrary
 * override and bypass the configured defaults.
 */
class StoreMediaDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-distributions.create') ?? false;
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
            'distribution_date' => ['required', 'date'],
            'notes'              => ['nullable', 'string', 'max:1000'],

            'items'                  => ['required', 'array', 'min:1'],
            'items.*.media_party_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('media_parties', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'items.*.paid_quantity' => ['required', 'integer', 'min:0'],
            'items.*.rate'          => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * A distribution where every line is 0 paid (and therefore 0 free,
     * 0 total) is not a meaningful document — reject it explicitly with
     * a clear message instead of letting it fall through to
     * DistributionService's own InvalidArgumentException.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = collect($this->input('items', []));

            if ($items->isNotEmpty() && $items->sum(fn ($item) => (int) ($item['paid_quantity'] ?? 0)) <= 0) {
                $validator->errors()->add(
                    'items',
                    'At least one item must have a paid quantity greater than zero.'
                );
            }
        });
    }
}
