<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'country',
        'password',
        'role',
        'kyc_status',
        'kyc_verified_at',
        'two_fa_enabled',
        'two_fa_secret',
        'is_active',
        'last_login_at',
        'firebase_uid',
        'firebase_email_verified',
        'firebase_disabled',
        'firebase_metadata',
        'firebase_custom_claims',
        'phone_number',
        'name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_fa_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'kyc_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'two_fa_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function kycVerification(): HasMany
    {
        return $this->hasMany(KYCVerification::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'member_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class, 'member_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function savings(): HasMany
    {
        return $this->hasMany(Savings::class, 'member_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Methods
    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function hasKYCVerified(): bool
    {
        return $this->kyc_status === 'verified';
    }

    public function getTotalSavings(): float
    {
        return $this->savings()->sum('balance');
    }

    public function getTotalLoans(): float
    {
        return $this->loans()
            ->where('status', 'active')
            ->sum('remaining_balance');
    }

    public function getTotalContributions(): float
    {
        return $this->contributions()
            ->where('status', 'completed')
            ->sum('amount');
    }
}