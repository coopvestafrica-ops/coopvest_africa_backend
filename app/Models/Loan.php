<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'user_id',
        'loan_type_id',
        'amount',
        'currency',
        'tenure',
        'interest_rate',
        'total_interest',
        'monthly_payment',
        'status',
        'approved_at',
        'disbursed_at',
        'completed_at',
        'application_date',
        'due_date',
        'next_payment_date',
        'is_rolled_over',
        'previous_loan_id',
        'rollover_date',
        'remaining_rollovers',
        'total_paid',
        'outstanding_balance',
        'payments_made',
        'missed_payments',
        'last_payment_date',
        'loan_purpose'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'total_interest' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'is_rolled_over' => 'boolean',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'completed_at' => 'datetime',
        'application_date' => 'datetime',
        'due_date' => 'datetime',
        'next_payment_date' => 'datetime',
        'rollover_date' => 'datetime',
        'last_payment_date' => 'datetime'
    ];

    /**
     * Get the user who owns this loan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'loan_id');
    }

    // Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(int $approvedBy): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function disburse(): void
    {
        $this->update([
            'status' => 'active',
            'disbursed_at' => now(),
            'next_payment_date' => now()->addMonth(),
        ]);

        // Create transaction for disbursement
        Transaction::create([
            'user_id' => $this->member_id,
            'loan_id' => $this->id,
            'type' => 'loan_disbursement',
            'amount' => $this->amount,
            'description' => "Loan Disbursement - {$this->purpose}",
            'status' => 'completed',
        ]);
    }

    public function reject(string $reason, int $approvedBy): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function recordPayment(float $amount): void
    {
        $this->remaining_balance -= $amount;

        if ($this->remaining_balance <= 0) {
            $this->status = 'completed';
            $this->remaining_balance = 0;
        }

        $this->next_payment_date = now()->addMonth();
        $this->save();

        // Create payment record
        LoanPayment::create([
            'loan_id' => $this->id,
            'amount' => $amount,
            'payment_date' => now(),
        ]);

        // Create transaction
        Transaction::create([
            'user_id' => $this->member_id,
            'loan_id' => $this->id,
            'type' => 'loan_payment',
            'amount' => $amount,
            'description' => 'Loan Payment',
            'status' => 'completed',
        ]);
    }

    public function calculateTotalInterest(): float
    {
        return ($this->amount * $this->interest_rate * $this->duration_months) / 100 / 12;
    }

    public function getMonthlyPayment(): float
    {
        $totalAmount = $this->amount + $this->calculateTotalInterest();
        return $totalAmount / $this->duration_months;
    }
}
