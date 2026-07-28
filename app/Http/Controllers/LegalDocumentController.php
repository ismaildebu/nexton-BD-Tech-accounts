<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLegalDocumentRequest;
use App\Http\Requests\UpdateLegalDocumentRequest;
use App\Models\LegalDocument;
use App\Models\Company;
use App\Services\LegalDocumentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LegalDocumentController extends Controller
{
    private LegalDocumentService $service;

    public function __construct(LegalDocumentService $service)
    {
        $this->service = $service;
        
    }

    /**
     * Display a listing of legal documents.
     */
    public function index(Request $request): View
    {
        $query = LegalDocument::query();

        // Filter by category
        if ($request->filled('category')) {
            $query->byCategory($request->input('category'));
        }

        // Filter by company
        if ($request->filled('company_id')) {
            $query->byCompany($request->input('company_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'expired') {
                $query->expired();
            } elseif ($status === 'expiring') {
                $query->expiring();
            } elseif ($status === 'active') {
                $query->active();
            }
        }

        // Search by title or document number
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $documents = $query->paginate(12);
        $companies = Company::all();
        $categories = LegalDocument::distinct('category')->pluck('category');
        $report = $this->service->generateReport();

        return view('legal-documents.index', compact(
            'documents',
            'companies',
            'categories',
            'report'
        ));
    }

    /**
     * Show the form for creating a new legal document.
     */
    public function create(): View
    {
        $companies = Company::all();
        $categories = [
            'Trade License',
            'TIN',
            'VAT',
            'Agreement',
            'Tax',
            'Insurance',
            'Permit',
            'Certificate',
            'Other'
        ];

        return view('legal-documents.create', compact('companies', 'categories'));
    }

    /**
     * Store a newly created legal document in storage.
     */
    public function store(StoreLegalDocumentRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $file = $request->file('file');

            $this->service->storeDocument($file, $validated);

            return redirect()
                ->route('legal-documents.index')
                ->with('success', 'Legal document uploaded successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error uploading document: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified legal document.
     */
    public function show(LegalDocument $legalDocument): View
    {
        $previewUrl = $this->service->getPreviewUrl($legalDocument);

        return view('legal-documents.show', compact('legalDocument', 'previewUrl'));
    }

    /**
     * Show the form for editing the specified legal document.
     */
    public function edit(LegalDocument $legalDocument): View
    {
        $companies = Company::all();
        $categories = [
            'Trade License',
            'TIN',
            'VAT',
            'Agreement',
            'Tax',
            'Insurance',
            'Permit',
            'Certificate',
            'Other'
        ];

        return view('legal-documents.edit', compact(
            'legalDocument',
            'companies',
            'categories'
        ));
    }

    /**
     * Update the specified legal document in storage.
     */
    public function update(UpdateLegalDocumentRequest $request, LegalDocument $legalDocument): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $file = $request->file('file');

            $this->service->updateDocument($legalDocument, $file, $validated);

            return redirect()
                ->route('legal-documents.show', $legalDocument)
                ->with('success', 'Legal document updated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error updating document: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified legal document from storage.
     */
    public function destroy(LegalDocument $legalDocument): RedirectResponse
    {
        try {
            $this->service->deleteDocument($legalDocument);

            return redirect()
                ->route('legal-documents.index')
                ->with('success', 'Legal document deleted successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error deleting document: ' . $e->getMessage());
        }
    }

    /**
     * Download a legal document.
     */
    public function download(LegalDocument $legalDocument)
    {
        return $this->service->downloadDocument($legalDocument);
    }

    /**
     * Mark document as reviewed.
     */
    public function markAsReviewed(LegalDocument $legalDocument): RedirectResponse
    {
        $legalDocument->markAsReviewed(auth()->id());

        return redirect()
            ->back()
            ->with('success', 'Document marked as reviewed!');
    }

    /**
     * Get documents expiring soon (API endpoint for dashboard).
     */
    public function expiringDocuments(Request $request)
    {
        $companyId = $request->input('company_id');
        $documents = $this->service->getExpiringDocuments($companyId, 30);

        return response()->json([
            'status' => 'success',
            'data' => $documents->map(fn($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'document_number' => $doc->document_number,
                'category' => $doc->category,
                'expiry_date' => $doc->expiry_date->format('Y-m-d'),
                'days_until_expiry' => now()->diffInDays($doc->expiry_date),
                'status' => $doc->isExpired() ? 'expired' : 'expiring',
            ])
        ]);
    }

    /**
     * Get document statistics (API endpoint for dashboard).
     */
    public function statistics(Request $request)
    {
        $companyId = $request->input('company_id');
        $report = $this->service->generateReport($companyId);

        return response()->json([
            'status' => 'success',
            'data' => $report
        ]);
    }
}
