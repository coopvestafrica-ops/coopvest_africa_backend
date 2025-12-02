<?php

namespace App\Http\Controllers;

use App\Models\Savings;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MemberController extends Controller
{
    /**
     * Get member profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'kyc_status' => $user->kyc_status,
                'two_fa_enabled' => $user->two_fa_enabled,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
        ], 200);
    }

    /**
     * Update member profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|unique:users,phone,' . $request->user()->id,
                'country' => 'sometimes|string',
            ]);

            $request->user()->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $request->user(),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get member dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalBalance = $user->getTotalSavings();
        $totalLoans = $user->getTotalLoans();
        $totalContributions = $user->getTotalContributions();

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($txn) {
                return [
                    'id' => $txn->id,
                    'type' => $txn->type,
                    'amount' => $txn->amount,
                    'description' => $txn->description,
                    'date' => $txn->created_at,
                    'status' => $txn->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'totalBalance' => $totalBalance,
                'savingsBalance' => $user->getTotalSavings(),
                'loanBalance' => $totalLoans,
                'monthlyContribution' => 2500,
                'recentTransactions' => $recentTransactions,
                'activeLoanCount' => $user->loans()->where('status', 'active')->count(),
                'pendingLoanCount' => $user->loans()->where('status', 'pending')->count(),
            ],
        ], 200);
    }

    /**
     * Get member transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 20);
        $offset = $request->query('offset', 0);

        $transactions = Transaction::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($limit, ['*'], 'page', ($offset / $limit) + 1);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'limit' => $limit,
                'offset' => $offset,
            ],
        ], 200);
    }

    /**
     * Get member savings
     */
    public function savings(Request $request): JsonResponse
    {
        $user = $request->user();
        $savings = $user->savings()->get();

        $totalSavings = $savings->sum('balance');
        $totalInterest = $savings->sum('total_interest_earned');

        return response()->json([
            'success' => true,
            'data' => [
                'totalSavings' => $totalSavings,
                'totalInterest' => $totalInterest,
                'averageRate' => 8.5,
                'accounts' => $savings->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'name' => $s->name,
                        'balance' => $s->balance,
                        'rate' => $s->rate,
                        'totalInterestEarned' => $s->total_interest_earned,
                        'createdDate' => $s->created_at,
                        'nextInterestDate' => $s->last_interest_date?->addMonth(),
                    ];
                }),
            ],
        ], 200);
    }

    /**
     * Get member loans
     */
    public function loans(Request $request): JsonResponse
    {
        $user = $request->user();
        $loans = $user->loans()->get();

        return response()->json([
            'success' => true,
            'data' => $loans->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'amount' => $loan->amount,
                    'remainingBalance' => $loan->remaining_balance,
                    'interestRate' => $loan->interest_rate,
                    'status' => $loan->status,
                    'nextPaymentDate' => $loan->next_payment_date,
                    'monthlyPayment' => $loan->monthly_payment_amount,
                ];
            }),
        ], 200);
    }
}
