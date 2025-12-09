<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QRToken extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_id',
        'token',
        'qr_data',
        'created_by',
        'expires_at',
        'scanned_by',
        'scanned_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'qr_data' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'scanned_at' => 'datetime',
    ];

    protected $hidden = [
        'token', // Hide token from API responses by default
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Generate unique token on creation
        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = self::generateUniqueToken();
            }
            if (empty($model->status)) {
                $model->status = 'active';
            }
        });
    }

    /**
     * Generate a unique QR token
     */
    public static function generateUniqueToken(): string
    {
        do {
            $token = 'QR_' . Str::random(32) . '_' . time();
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Relationships
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scannedBy()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }

    public function scopeForLoan($query, $loanId)
    {
        return $query->where('loan_id', $loanId);
    }

    /**
     * Accessors & Mutators
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at <= now();
    }

    public function getIsValidAttribute(): bool
    {
        return $this->status === 'active' && !$this->is_expired;
    }

    public function getTimeRemainingAttribute(): ?int
    {
        if ($this->is_expired) {
            return 0;
        }
        return $this->expires_at->diffInSeconds(now());
    }

    /**
     * Methods
     */

    /**
     * Check if token is valid for scanning
     */
    public function isValidForScanning(): bool
    {
        return $this->status === 'active' && !$this->is_expired;
    }

    /**
     * Mark token as scanned
     */
    public function markAsScanned(User $user): bool
    {
        return $this->update([
            'scanned_by' => $user->id,
            'scanned_at' => now(),
            'status' => 'used',
        ]);
    }

    /**
     * Revoke the token
     */
    public function revoke(): bool
    {
        return $this->update([
            'status' => 'revoked',
        ]);
    }

    /**
     * Get QR data for display
     */
    public function getQRDisplayData(): array
    {
        return [
            'token' => $this->token,
            'loan_id' => $this->loan_id,
            'qr_data' => $this->qr_data,
            'expires_at' => $this->expires_at,
            'is_expired' => $this->is_expired,
            'time_remaining' => $this->time_remaining,
            'status' => $this->status,
        ];
    }

    /**
     * Get validation data
     */
    public function getValidationData(): array
    {
        return [
            'valid' => $this->isValidForScanning(),
            'token' => $this->token,
            'loan_id' => $this->loan_id,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'is_expired' => $this->is_expired,
            'scanned_by' => $this->scanned_by,
            'scanned_at' => $this->scanned_at,
        ];
    }
}
