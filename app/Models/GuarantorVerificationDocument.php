<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuarantorVerificationDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'guarantor_id',
        'document_type',
        'document_path',
        'file_name',
        'file_size',
        'mime_type',
        'status',
        'rejection_reason',
        'uploaded_at',
        'reviewed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'uploaded_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: Document belongs to a Guarantor
     */
    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(Guarantor::class);
    }

    /**
     * Scope: Get verified documents
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope: Get by document type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Get document type label
     */
    public function getDocumentTypeLabel(): string
    {
        return match($this->document_type) {
            'employment_letter' => 'Employment Letter',
            'id_document' => 'ID Document',
            'bank_statement' => 'Bank Statement',
            'payslip' => 'Payslip',
            'business_license' => 'Business License',
            'registration_document' => 'Registration Document',
            default => ucfirst($this->document_type),
        };
    }

    /**
     * Get file URL
     */
    public function getFileUrl(): string
    {
        return asset('storage/' . $this->document_path);
    }

    /**
     * Check if document is verified
     */
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Mark as verified
     */
    public function markAsVerified(): void
    {
        $this->status = 'verified';
        $this->reviewed_at = now();
        $this->save();
    }

    /**
     * Reject document with reason
     */
    public function reject(string $reason): void
    {
        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->reviewed_at = now();
        $this->save();
    }

    /**
     * Format file size for display
     */
    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
