<?php

namespace App\Services;

use App\Models\LegalDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LegalDocumentService
{
    private const DISK = 'legal_documents';
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * Upload and store a legal document file.
     */
    public function storeDocument(UploadedFile $file, array $data): LegalDocument
    {
        // Validate file
        $this->validateFile($file);

        // Store file
        $filePath = $this->storeFile($file);

        // Create document record
        $data['file_path'] = $filePath;
        $data['file_name'] = $file->getClientOriginalName();
        $data['file_type'] = $file->getClientOriginalExtension();
        $data['file_size'] = $file->getSize();
        $data['uploaded_by'] = auth()->id();

        return LegalDocument::create($data);
    }

    /**
     * Update document with new file if provided.
     */
    public function updateDocument(LegalDocument $document, UploadedFile $file = null, array $data): LegalDocument
    {
        // If new file is provided, replace old one
        if ($file) {
            $this->validateFile($file);
            
            // Delete old file
            $document->deleteFile();
            
            // Store new file
            $filePath = $this->storeFile($file);
            $data['file_path'] = $filePath;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['file_size'] = $file->getSize();
        }

        $document->update($data);
        return $document;
    }

    /**
     * Store file in storage.
     */
    private function storeFile(UploadedFile $file): string
    {
        $directory = 'documents/' . auth()->id() . '/' . now()->format('Y/m');
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs($directory, $filename, self::DISK);
    }

    /**
     * Validate uploaded file.
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum limit of 10MB.');
        }

        // Check file type
        $allowedMimes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xlsx', 'xls', 'zip'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedMimes)) {
            throw new \Exception('File type not allowed. Allowed types: PDF, DOC, DOCX, JPG, PNG, XLSX, XLS, ZIP.');
        }
    }

    /**
     * Delete document and its file.
     */
    public function deleteDocument(LegalDocument $document): bool
    {
        $document->deleteFile();
        return $document->delete();
    }

    /**
     * Get download response for a document.
     */
    public function downloadDocument(LegalDocument $document)
    {
        return Storage::disk(self::DISK)->download(
            $document->file_path,
            $document->file_name
        );
    }

    /**
     * Get preview URL for a document.
     */
    public function getPreviewUrl(LegalDocument $document): ?string
    {
        $previewableTypes = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (!in_array($document->file_type, $previewableTypes)) {
            return null;
        }

        return Storage::disk(self::DISK)->url($document->file_path);
    }

    /**
     * Get documents expiring soon.
     */
    public function getExpiringDocuments($companyId = null, $daysUntil = 30)
    {
        $query = LegalDocument::active();

        if ($companyId) {
            $query->byCompany($companyId);
        }

        return $query->whereBetween(
            'expiry_date',
            [now(), now()->addDays($daysUntil)]
        )->orderBy('expiry_date', 'asc')->get();
    }

    /**
     * Get expired documents.
     */
    public function getExpiredDocuments($companyId = null)
    {
        $query = LegalDocument::where('expiry_date', '<', now());

        if ($companyId) {
            $query->byCompany($companyId);
        }

        return $query->orderBy('expiry_date', 'asc')->get();
    }

    /**
     * Generate document report.
     */
    public function generateReport($companyId = null)
    {
        return [
            'total' => LegalDocument::when($companyId, function ($q) use ($companyId) {
                return $q->byCompany($companyId);
            })->count(),
            'active' => LegalDocument::active()->when($companyId, function ($q) use ($companyId) {
                return $q->byCompany($companyId);
            })->count(),
            'expired' => $this->getExpiredDocuments($companyId)->count(),
            'expiring_soon' => $this->getExpiringDocuments($companyId)->count(),
            'by_category' => LegalDocument::when($companyId, function ($q) use ($companyId) {
                return $q->byCompany($companyId);
            })->selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category'),
        ];
    }
}
