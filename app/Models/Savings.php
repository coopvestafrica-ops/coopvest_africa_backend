<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Savings extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'name',
        'balance',
        'rate',
        'total_interest_earned',
        'last_interest_date',
    ];

    protected $casts = [
        'balance' => 'float',
        'rate' => 'float',
        'total_interest_earned' => 'float',
        'last_interest_date' => 'datetime',
    ];

    // Relationships
    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    // Methods
    public function addFunds(float $amount, string $description = 'Deposit'): void
    {
        $this->increment('balance', $amount);

        Transaction::create([
            'user_id' => $this->member_id,
            'type' => 'savings_deposit',
            'amount' => $amount,
            'description' => $description,
            'status' => 'completed',
        ]);
    }

    public function withdrawFunds(float $amount, string $description = 'Withdrawal'): bool
    {
        if ($this->balance < $amount) {
            return false;
        }

        $this->decrement('balance', $amount);

        Transaction::create([
            'user_id' => $this->member_id,
            'type' => 'savings_withdrawal',
            'amount' => $amount,
            'description' => $description,
            'status' => 'completed',
        ]);

        return true;
    }

    public function creditInterest(float $interestAmount): void
    {
        $this->increment('balance', $interestAmount);
        $this->increment('total_interest_earned', $interestAmount);
        $this->update(['last_interest_date' => now()]);

        Transaction::create([
            'user_id' => $this->member_id,
            'type' => 'interest',
            'amount' => $interestAmount,
            'description' => 'Interest Credit',
            'status' => 'completed',
        ]);
    }

    public function calculateMonthlyInterest(): float
    {
        return ($this->balance * $this->rate) / 100 / 12;
    }
}
