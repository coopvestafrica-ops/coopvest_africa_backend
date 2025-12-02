<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApplication extends Model
{
    protected $fillable = [
        'user_id',
        'loan_type_id',
        'requested_amount',
        'currency',
        'requested_tenure',
        'loan_purpose',
        'employment_status',
        'employer_name',
        'job_title',
        'employment_start_date',
        'monthly_salary',
        'monthly_expenses',
        'existing_loans',
        'existing_loan_balance',
        'savings_balance',
        'business_revenue',
        'status',
        'stage',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'rejection_reason',
        'notes'
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'monthly_expenses' => 'decimal:2',
        'existing_loan_balance' => 'decimal:2',
        'savings_balance' => 'decimal:2',
        'business_revenue' => 'decimal:2',
        'employment_start_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime'
    ];

    /**
     * Get the user who submitted this application
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the loan type for this application
     */
    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    /**
     * Check if application is eligible for approval
     */
    public function isEligibleForApproval(): bool
    {
        $user = $this->user;
        $loanType = $this->loanType;

        // Check user KYC status
        if (!$user->kyc_verified) {
            return false;
        }

        // Check minimum salary
        if ($loanType->minimum_salary && $this->monthly_salary < $loanType->minimum_salary) {
            return false;
        }

        // Check debt-to-income ratio (don't allow if existing loans > 50% of income)
        $totalExistingPayments = $this->existing_loan_balance;
        $monthlyIncome = $this->monthly_salary ?? 0;
        
        if ($monthlyIncome > 0 && ($totalExistingPayments / $monthlyIncome) > 0.5) {
            return false;
        }

        return true;
    }

    /**
     * Approve the application
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now()
        ]);
    }

    /**
     * Reject the application
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_at' => now()
        ]);
    }

    /**
     * Move to next stage
     */
    public function moveToNextStage(): void
    {
        $stages = ['personal_info', 'employment', 'financial', 'guarantors', 'documents', 'review'];
        $currentIndex = array_search($this->stage, $stages);

        if ($currentIndex !== false && $currentIndex < count($stages) - 1) {
            $this->update(['stage' => $stages[$currentIndex + 1]]);
        }
    }

    /**
     * Scope for pending applications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'submitted')->orWhere('status', 'under_review');
    }

    /**
     * Scope for approved applications
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
