<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLegalDocumentRequest extends FormRequest
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
        return [
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:Trade License,TIN,VAT,Agreement,Tax,Insurance,Permit,Certificate,Other',
            'document_number' => 'required|string|unique:legal_documents,document_number',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,xls,zip|max:10240', // 10MB
            'description' => 'nullable|string|max:1000',
            'company_id' => 'nullable|exists:companies,id',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Document title is required.',
            'title.max' => 'Document title cannot exceed 255 characters.',
            'category.required' => 'Please select a document category.',
            'category.in' => 'The selected category is invalid.',
            'document_number.required' => 'Document number is required.',
            'document_number.unique' => 'This document number already exists.',
            'issue_date.date' => 'Issue date must be a valid date.',
            'expiry_date.date' => 'Expiry date must be a valid date.',
            'expiry_date.after_or_equal' => 'Expiry date must be the same as or after issue date.',
            'file.required' => 'Please upload a document file.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'Only PDF, DOC, DOCX, JPG, PNG, XLSX, XLS, and ZIP files are allowed.',
            'file.max' => 'File size cannot exceed 10MB.',
            'company_id.exists' => 'The selected company does not exist.',
        ];
    }
}
