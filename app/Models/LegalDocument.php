<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class LegalDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'category',
        'document_number',
        'issue_date',
        'expiry_date',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'description',
        'uploaded_by',
        'is_active',
        'last_reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'last_reviewed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns this document.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who uploaded this document.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the user who last reviewed this document.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the full URL of the document file.
     */
    public function getFileUrl(): string
    {
        return Storage::disk('legal_documents')->url($this->file_path);
    }

    /**
     * Download the document file.
     */
    public function downloadFile()
    {
        return Storage::disk('legal_documents')->download(
            $this->file_path,
            $this->file_name
        );
    }

    /**
     * Delete the document file from storage.
     */
    public function deleteFile(): bool
    {
        return Storage::disk('legal_documents')->delete($this->file_path);
    }

    /**
     * Check if the document has expired.
     */
    public function isExpired(): bool
    {
        if (is_null($this->expiry_date)) {
            return false;
        }

        return now()->isAfter($this->expiry_date);
    }

    /**
     * Check if the document is expiring soon (within 30 days).
     */
    public function isExpiringsoon(): bool
    {
        if (is_null($this->expiry_date)) {
            return false;
        }

        $daysUntilExpiry = now()->diffInDays($this->expiry_date, false);
        return $daysUntilExpiry >= 0 && $daysUntilExpiry <= 30;
    }

    /**
     * Mark document as reviewed.
     */
    public function markAsReviewed(int $userId): void
    {
        $this->update([
            'last_reviewed_at' => now(),
            'reviewed_by' => $userId,
        ]);
    }

    /**
     * Get the category label with styling.
     */
    public function getCategoryBadgeClass(): string
    {
        $badges = [
            'Trade License' => 'bg-blue-100 text-blue-800',
            'TIN' => 'bg-purple-100 text-purple-800',
            'VAT' => 'bg-green-100 text-green-800',
            'Agreement' => 'bg-yellow-100 text-yellow-800',
            'Tax' => 'bg-red-100 text-red-800',
            'Insurance' => 'bg-indigo-100 text-indigo-800',
            'Permit' => 'bg-pink-100 text-pink-800',
            'Certificate' => 'bg-cyan-100 text-cyan-800',
            'Other' => 'bg-gray-100 text-gray-800',
        ];

        return $badges[$this->category] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanReadableFileSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $i < count($units) - 1; $i++) {
            if ($bytes < 1024) {
                return round($bytes, 2) . ' ' . $units[$i];
            }
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' GB';
    }

    /**
     * Get the file icon based on file type.
     */
    public function getFileIcon(): string
    {
        $icons = [
            'pdf' => 'fas fa-file-pdf text-red-500',
            'doc' => 'fas fa-file-word text-blue-500',
            'docx' => 'fas fa-file-word text-blue-500',
            'jpg' => 'fas fa-image text-green-500',
            'jpeg' => 'fas fa-image text-green-500',
            'png' => 'fas fa-image text-green-500',
            'xlsx' => 'fas fa-file-excel text-green-600',
            'xls' => 'fas fa-file-excel text-green-600',
            'zip' => 'fas fa-file-archive text-purple-500',
        ];

        return $icons[$this->file_type] ?? 'fas fa-file text-gray-500';
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter active documents only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to get expiring documents.
     */
    public function scopeExpiring($query)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays(30)]);
    }

    /**
     * Scope to get expired documents.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }
}
