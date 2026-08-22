<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLegalDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $document = $this->route('legal_document');

        $documentId = $document?->id;

        $companyId = session(
            'company_id',
            auth()->user()->company_id ?? null
        );

        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'category' => [
                'required',
                'string',
                Rule::in([
                    'Trade License',
                    'TIN',
                    'VAT',
                    'Agreement',
                    'Tax',
                    'Insurance',
                    'Permit',
                    'Certificate',
                    'Other',
                ]),
            ],

            'document_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('legal_documents', 'document_number')
                    ->ignore($documentId)
                    ->where(function ($query) use ($companyId) {
                        return $query->where('company_id', $companyId);
                    }),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'issue_date' => [
                'nullable',
                'date',
            ],

            'expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:issue_date',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'file' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,xls,zip',
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}