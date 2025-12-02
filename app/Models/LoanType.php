<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'minimum_amount',
        'maximum_amount',
        'interest_rate',
        'duration_months',
        'processing_fee_percentage',
        'requires_guarantor',
        'minimum_employment_months',
        'minimum_salary',
        'eligibility_requirements',
        'max_rollover_times',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_guarantor' => 'boolean',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'processing_fee_percentage' => 'decimal:2',
        'minimum_salary' => 'decimal:2',
        'eligibility_requirements' => 'array'
    ];

    /**
     * Get all loans for this type
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get all loan applications for this type
     */
    public function applications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    /**
     * Check if a user is eligible for this loan type
     */
    public function isUserEligible(User $user): bool
    {
        // Check minimum salary
        if ($this->minimum_salary && $user->employment?->monthly_salary < $this->minimum_salary) {
            return false;
        }

        // Check employment duration
        if ($this->minimum_employment_months && $user->employment?->months_employed < $this->minimum_employment_months) {
            return false;
        }

        return true;
    }

    /**
     * Get scope to only active loan types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
