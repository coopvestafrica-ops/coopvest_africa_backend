<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuarantorInvitation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'loan_id',
        'guarantor_email',
        'invitation_token',
        'invitation_link',
        'status',
        'sent_at',
        'accepted_at',
        'declined_at',
        'expires_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'invitation_token',
    ];

    /**
     * Relationship: Invitation belongs to a Loan
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Scope: Get pending invitations
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope: Get accepted invitations
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope: Get expired invitations
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', 'pending');
    }

    /**
     * Scope: Get by email
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('guarantor_email', strtolower($email));
    }

    /**
     * Check if invitation is still valid
     */
    public function isValid(): bool
    {
        return $this->status === 'pending' && now()->isBefore($this->expires_at);
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    /**
     * Accept invitation
     */
    public function accept(): void
    {
        $this->status = 'accepted';
        $this->accepted_at = now();
        $this->save();
    }

    /**
     * Decline invitation
     */
    public function decline(): void
    {
        $this->status = 'declined';
        $this->declined_at = now();
        $this->save();
    }

    /**
     * Generate invitation link
     */
    public function generateLink(): string
    {
        $baseUrl = config('app.url');
        return "{$baseUrl}/guarantor-accept/{$this->invitation_token}";
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            'expired' => 'Expired',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            'accepted' => 'success',
            'declined' => 'danger',
            'pending' => 'warning',
            'expired' => 'secondary',
            default => 'info',
        };
    }
}
