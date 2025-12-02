<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\GlobalSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    /**
     * Apply for a loan
     */
    public function apply(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1000',
                'duration_months' => 'required|integer|min:1|max:60',
                'purpose' => 'required|string|max:500',
            ]);

            $user = $request->user();

            // Check KYC status
            if (!$user->hasKYCVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'KYC verification required before applying for a loan',
                ], 403);
            }

            // Get default interest rate from global settings
            $interestRate = GlobalSetting::get('default_loan_interest_rate', 12.5);

            $loan = Loan::create([
                'member_id' => $user->id,
                'amount' => $validated['amount'],
                'remaining_balance' => $validated['amount'],
                'interest_rate' => $interestRate,
                'duration_months' => $validated['duration_months'],
                'purpose' => $validated['purpose'],
                'status' => 'pending',
                'monthly_payment_amount' => $this->calculateMonthlyPayment(
                    $validated['amount'],
                    $interestRate,
                    $validated['duration_months']
                ),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Loan application submitted successfully',
                'data' => [
                    'id' => $loan->id,
                    'status' => $loan->status,
                    'monthlyPayment' => $loan->monthly_payment_amount,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get loan details
     */
    public function show(Request $request, $id): JsonResponse
    {
        $loan = Loan::findOrFail($id);

        // Check authorization
        if ($loan->member_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $loan->id,
                'amount' => $loan->amount,
                'remainingBalance' => $loan->remaining_balance,
                'interestRate' => $loan->interest_rate,
                'durationMonths' => $loan->duration_months,
                'purpose' => $loan->purpose,
                'status' => $loan->status,
                'monthlyPayment' => $loan->monthly_payment_amount,
                'nextPaymentDate' => $loan->next_payment_date,
                'disbursedAt' => $loan->disbursed_at,
                'totalInterest' => $loan->calculateTotalInterest(),
            ],
        ], 200);
    }

    /**
     * Calculate loan details
     */
    public function calculate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1000',
                'duration_months' => 'required|integer|min:1|max:60',
            ]);

            $interestRate = GlobalSetting::get('default_loan_interest_rate', 12.5);

            $monthlyPayment = $this->calculateMonthlyPayment(
                $validated['amount'],
                $interestRate,
                $validated['duration_months']
            );

            $totalInterest = ($validated['amount'] * $interestRate * $validated['duration_months']) / 100 / 12;
            $totalAmount = $validated['amount'] + $totalInterest;

            return response()->json([
                'success' => true,
                'data' => [
                    'loanAmount' => $validated['amount'],
                    'interestRate' => $interestRate,
                    'durationMonths' => $validated['duration_months'],
                    'monthlyPayment' => $monthlyPayment,
                    'totalInterest' => $totalInterest,
                    'totalAmount' => $totalAmount,
                ],
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
     * Make loan payment
     */
    public function makePayment(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
            ]);

            $loan = Loan::findOrFail($id);

            if ($loan->member_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            if ($loan->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan is not active',
                ], 400);
            }

            $loan->recordPayment($validated['amount']);

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => [
                    'remainingBalance' => $loan->remaining_balance,
                    'status' => $loan->status,
                ],
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
     * Get pending loans (Admin)
     */
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $loans = Loan::where('status', 'pending')
            ->with('member')
            ->get()
            ->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'memberId' => $loan->member_id,
                    'memberName' => $loan->member->full_name,
                    'amount' => $loan->amount,
                    'purpose' => $loan->purpose,
                    'createdAt' => $loan->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $loans,
        ], 200);
    }

    /**
     * Approve loan (Admin)
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $loan = Loan::findOrFail($id);
        $loan->approve($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Loan approved successfully',
        ], 200);
    }

    /**
     * Reject loan (Admin)
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $loan = Loan::findOrFail($id);
            $loan->reject($validated['reason'], $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Loan rejected successfully',
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
     * Calculate monthly payment
     */
    private function calculateMonthlyPayment(float $amount, float $rate, int $months): float
    {
        $totalInterest = ($amount * $rate * $months) / 100 / 12;
        $totalAmount = $amount + $totalInterest;
        return $totalAmount / $months;
    }
}
