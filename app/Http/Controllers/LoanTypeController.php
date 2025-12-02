<?php

namespace App\Http\Controllers;

use App\Models\LoanType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LoanTypeController extends Controller
{
    /**
     * Get all active loan types
     * GET /api/loan-types
     */
    public function index()
    {
        $loanTypes = LoanType::active()
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $loanTypes,
            'count' => $loanTypes->count()
        ]);
    }

    /**
     * Get specific loan type
     * GET /api/loan-types/{id}
     */
    public function show(LoanType $loanType)
    {
        if (!$loanType->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'This loan type is not currently available'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $loanType
        ]);
    }

    /**
     * Calculate loan details (monthly payment, interest, etc.)
     * GET /api/loan-types/{id}/calculate?amount=10000&tenure=12
     */
    public function calculate(LoanType $loanType, Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:' . $loanType->minimum_amount . '|max:' . $loanType->maximum_amount,
            'tenure' => 'required|integer|min:1|max:36'
        ]);

        $amount = $validated['amount'];
        $tenure = $validated['tenure'];

        $monthlyPayment = $loanType->calculateMonthlyPayment($amount, $tenure);
        $totalInterest = $loanType->calculateTotalInterest($amount, $tenure);
        $processingFee = $loanType->calculateProcessingFee($amount);
        $totalPayment = $amount + $totalInterest + $processingFee;

        return response()->json([
            'success' => true,
            'data' => [
                'loan_type_id' => $loanType->id,
                'loan_type_name' => $loanType->name,
                'requested_amount' => $amount,
                'tenure_months' => $tenure,
                'interest_rate' => $loanType->interest_rate,
                'monthly_payment' => round($monthlyPayment, 2),
                'total_interest' => round($totalInterest, 2),
                'processing_fee' => round($processingFee, 2),
                'total_payment' => round($totalPayment, 2),
                'payment_breakdown' => [
                    'principal' => $amount,
                    'interest' => round($totalInterest, 2),
                    'fees' => round($processingFee, 2)
                ]
            ]
        ]);
    }

    /**
     * Create new loan type (Admin only)
     * POST /api/loan-types
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:loan_types',
            'description' => 'nullable|string',
            'minimum_amount' => 'required|numeric|min:0',
            'maximum_amount' => 'required|numeric|gt:minimum_amount',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'duration_months' => 'required|integer|min:1',
            'processing_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'requires_guarantor' => 'nullable|boolean',
            'minimum_employment_months' => 'nullable|integer|min:0',
            'minimum_salary' => 'nullable|numeric|min:0',
            'eligibility_requirements' => 'nullable|array',
            'max_rollover_times' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        $loanType = LoanType::create($validated);

        return response()->json([
            'success' => true,
            'data' => $loanType,
            'message' => 'Loan type created successfully'
        ], Response::HTTP_CREATED);
    }

    /**
     * Update loan type (Admin only)
     * PUT /api/loan-types/{id}
     */
    public function update(Request $request, LoanType $loanType)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:loan_types,name,' . $loanType->id,
            'description' => 'nullable|string',
            'minimum_amount' => 'sometimes|numeric|min:0',
            'maximum_amount' => 'sometimes|numeric|gt:minimum_amount',
            'interest_rate' => 'sometimes|numeric|min:0|max:100',
            'duration_months' => 'sometimes|integer|min:1',
            'processing_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'requires_guarantor' => 'nullable|boolean',
            'minimum_employment_months' => 'nullable|integer|min:0',
            'minimum_salary' => 'nullable|numeric|min:0',
            'eligibility_requirements' => 'nullable|array',
            'max_rollover_times' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        $loanType->update($validated);

        return response()->json([
            'success' => true,
            'data' => $loanType,
            'message' => 'Loan type updated successfully'
        ]);
    }

    /**
     * Delete loan type (Admin only - soft delete)
     * DELETE /api/loan-types/{id}
     */
    public function destroy(LoanType $loanType)
    {
        $name = $loanType->name;
        $loanType->delete();

        return response()->json([
            'success' => true,
            'message' => "Loan type '{$name}' deleted successfully"
        ]);
    }

    /**
     * Get all loan types including inactive ones (Admin only)
     * GET /api/loan-types/admin/all
     */
    public function allWithInactive()
    {
        $loanTypes = LoanType::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $loanTypes,
            'count' => $loanTypes->count(),
            'active_count' => $loanTypes->where('is_active', true)->count(),
            'inactive_count' => $loanTypes->where('is_active', false)->count()
        ]);
    }
}
