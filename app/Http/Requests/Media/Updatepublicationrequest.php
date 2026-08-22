<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media-publications.edit') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $companyId = session('company_id');
        $publicationId = $this->route('publication')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('publications', 'code')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($publicationId),
            ],
            'publication_type'         => ['nullable', 'string', 'max:100'],
            'selling_price'            => ['required', 'numeric', 'min:0'],
            'default_free_percentage'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active'                => ['sometimes', 'boolean'],
        ];
    }
}
