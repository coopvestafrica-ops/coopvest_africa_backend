<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guarantor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'loan_id',
        'guarantor_user_id',
        'relationship',
        'verification_status',
        'employment_verification_required',
        'employment_verification_completed',
        'employment_verification_url',
        'confirmation_status',
        'invitation_sent_at',
        'invitation_accepted_at',
        'invitation_declined_at',
        'qr_code',
        'qr_code_token',
        'qr_code_expires_at',
        'notes',
        'liability_amount',
    ];

    protected $casts = [
        'employment_verification_required' => 'boolean',
        'employment_verification_completed' => 'boolean',
        'invitation_sent_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
        'invitation_declined_at' => 'datetime',
        'qr_code_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'liability_amount' => 'decimal:2',
    ];

    protected $hidden = [
        'qr_code_token',
    ];

    /**
     * Relationship: Guarantor belongs to a Loan
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Relationship: Guarantor is a User
     */
    public function guarantorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guarantor_user_id');
    }

    /**
     * Relationship: Loan Applicant (User who requested the loan)
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->through('loan');
    }

    /**
     * Relationship: Has many verification documents
     */
    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(GuarantorVerificationDocument::class);
    }

    /**
     * Scope: Get pending guarantors (awaiting acceptance)
     */
    public function scopePending($query)
    {
        return $query->where('confirmation_status', 'pending');
    }

    /**
     * Scope: Get accepted guarantors
     */
    public function scopeAccepted($query)
    {
        return $query->where('confirmation_status', 'accepted');
    }

    /**
     * Scope: Get declined guarantors
     */
    public function scopeDeclined($query)
    {
        return $query->where('confirmation_status', 'declined');
    }

    /**
     * Scope: Get verified guarantors
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope: Get by relationship type
     */
    public function scopeByRelationship($query, string $relationship)
    {
        return $query->where('relationship', $relationship);
    }

    /**
     * Scope: Get active guarantors (accepted and verified)
     */
    public function scopeActive($query)
    {
        return $query->where('confirmation_status', 'accepted')
            ->where('verification_status', 'verified');
    }

    /**
     * Check if guarantor has confirmed acceptance
     */
    public function isConfirmed(): bool
    {
        return $this->confirmation_status === 'accepted';
    }

    /**
     * Check if guarantor is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Check if guarantor is active (confirmed and verified)
     */
    public function isActive(): bool
    {
        return $this->isConfirmed() && $this->isVerified();
    }

    /**
     * Check if QR code token is still valid
     */
    public function isQRCodeValid(): bool
    {
        if (!$this->qr_code_expires_at) {
            return false;
        }
        return now()->isBefore($this->qr_code_expires_at);
    }

    /**
     * Get guarantor's liability for this loan
     */
    public function getLiabilityAmount(): float
    {
        return $this->liability_amount ?? ($this->loan->amount ?? 0);
    }

    /**
     * Set verification status
     */
    public function setVerificationStatus(string $status): void
    {
        $validStatuses = ['pending', 'verified', 'rejected', 'expired'];
        if (in_array($status, $validStatuses)) {
            $this->verification_status = $status;
            $this->save();
        }
    }

    /**
     * Set confirmation status
     */
    public function setConfirmationStatus(string $status): void
    {
        $validStatuses = ['pending', 'accepted', 'declined', 'revoked'];
        if (in_array($status, $validStatuses)) {
            $this->confirmation_status = $status;
            
            if ($status === 'accepted') {
                $this->invitation_accepted_at = now();
            } elseif ($status === 'declined') {
                $this->invitation_declined_at = now();
            }
            
            $this->save();
        }
    }

    /**
     * Get relationship display name
     */
    public function getRelationshipLabel(): string
    {
        return match($this->relationship) {
            'friend' => 'Friend',
            'family' => 'Family Member',
            'colleague' => 'Colleague',
            'business_partner' => 'Business Partner',
            default => ucfirst($this->relationship),
        };
    }

    /**
     * Get verification status label
     */
    public function getVerificationStatusLabel(): string
    {
        return match($this->verification_status) {
            'pending' => 'Pending Review',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            'expired' => 'Verification Expired',
            default => ucfirst($this->verification_status),
        };
    }

    /**
     * Get confirmation status label
     */
    public function getConfirmationStatusLabel(): string
    {
        return match($this->confirmation_status) {
            'pending' => 'Awaiting Response',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            'revoked' => 'Revoked',
            default => ucfirst($this->confirmation_status),
        };
    }

    /**
     * Get status badge color for frontend
     */
    public function getStatusBadgeColor(): string
    {
        return match($this->confirmation_status) {
            'accepted' => 'success',
            'declined' => 'danger',
            'pending' => 'warning',
            'revoked' => 'secondary',
            default => 'info',
        };
    }

    /**
     * Get verification badge color for frontend
     */
    public function getVerificationBadgeColor(): string
    {
        return match($this->verification_status) {
            'verified' => 'success',
            'rejected' => 'danger',
            'expired' => 'warning',
            'pending' => 'info',
            default => 'secondary',
        };
    }
}
