<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'amount',
        'contribution_date',
        'status',
        'payment_method',
        'transaction_reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'contribution_date' => 'datetime',
    ];

    // Relationships
    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    // Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);

        // Create transaction
        Transaction::create([
            'user_id' => $this->member_id,
            'type' => 'contribution',
            'amount' => $this->amount,
            'description' => 'Monthly Contribution',
            'status' => 'completed',
        ]);

        // Update member's savings
        $savings = Savings::firstOrCreate(
            ['member_id' => $this->member_id, 'name' => 'Main Savings'],
            ['balance' => 0, 'rate' => 8.5]
        );
        $savings->increment('balance', $this->amount);
    }

    public function markAsFailed(string $reason = ''): void
    {
        $this->update(['status' => 'failed', 'notes' => $reason]);
    }
}
