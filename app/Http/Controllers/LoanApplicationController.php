<?php

namespace App\Http\Controllers;

use App\Models\LoanApplication;
use App\Models\LoanType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoanApplicationController extends Controller
{
    /**
     * Get all loan applications for the authenticated user
     */
    public function getUserApplications(): JsonResponse
    {
        try {
            $user = Auth::user();
            $applications = LoanApplication::where('user_id', $user->id)
                ->with('loanType')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $applications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching applications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific loan application
     */
    public function getApplication($id): JsonResponse
    {
        try {
            $application = LoanApplication::with('loanType', 'user')
                ->findOrFail($id);

            // Check authorization
            if ($application->user_id !== Auth::id() && !Auth::user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $application
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found'
            ], 404);
        }
    }

    /**
     * Create a new loan application
     */
    public function createApplication(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'loan_type_id' => 'required|exists:loan_types,id',
                'requested_amount' => 'required|numeric|min:1',
                'requested_tenure' => 'required|integer|min:1',
                'loan_purpose' => 'required|string|max:500',
                'employment_status' => 'required|in:employed,self_employed,unemployed',
                'employer_name' => 'nullable|string|max:255',
                'job_title' => 'nullable|string|max:255',
                'employment_start_date' => 'nullable|date',
                'monthly_salary' => 'nullable|numeric|min:0',
                'monthly_expenses' => 'required|numeric|min:0',
                'existing_loans' => 'required|integer|min:0',
                'existing_loan_balance' => 'required|numeric|min:0',
                'savings_balance' => 'required|numeric|min:0',
                'business_revenue' => 'nullable|numeric|min:0'
            ]);

            $user = Auth::user();

            // Check if user has KYC verification
            if (!$user->kyc_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'KYC verification is required before applying for a loan'
                ], 422);
            }

            // Create the application
            $application = LoanApplication::create([
                'user_id' => $user->id,
                'loan_type_id' => $validated['loan_type_id'],
                'requested_amount' => $validated['requested_amount'],
                'currency' => 'USD',
                'requested_tenure' => $validated['requested_tenure'],
                'loan_purpose' => $validated['loan_purpose'],
                'employment_status' => $validated['employment_status'],
                'employer_name' => $validated['employer_name'],
                'job_title' => $validated['job_title'],
                'employment_start_date' => $validated['employment_start_date'],
                'monthly_salary' => $validated['monthly_salary'],
                'monthly_expenses' => $validated['monthly_expenses'],
                'existing_loans' => $validated['existing_loans'],
                'existing_loan_balance' => $validated['existing_loan_balance'],
                'savings_balance' => $validated['savings_balance'],
                'business_revenue' => $validated['business_revenue'],
                'status' => 'draft',
                'stage' => 'personal_info'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Loan application created successfully',
                'data' => $application->load('loanType')
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating application: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing loan application
     */
    public function updateApplication(Request $request, $id): JsonResponse
    {
        try {
            $application = LoanApplication::findOrFail($id);

            // Check authorization
            if ($application->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Can only update draft applications
            if ($application->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update applications that have been submitted'
                ], 422);
            }

            $validated = $request->validate([
                'requested_amount' => 'sometimes|numeric|min:1',
                'requested_tenure' => 'sometimes|integer|min:1',
                'loan_purpose' => 'sometimes|string|max:500',
                'employment_status' => 'sometimes|in:employed,self_employed,unemployed',
                'employer_name' => 'sometimes|nullable|string|max:255',
                'job_title' => 'sometimes|nullable|string|max:255',
                'employment_start_date' => 'sometimes|nullable|date',
                'monthly_salary' => 'sometimes|nullable|numeric|min:0',
                'monthly_expenses' => 'sometimes|numeric|min:0',
                'existing_loans' => 'sometimes|integer|min:0',
                'existing_loan_balance' => 'sometimes|numeric|min:0',
                'savings_balance' => 'sometimes|numeric|min:0',
                'business_revenue' => 'sometimes|nullable|numeric|min:0'
            ]);

            $application->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Loan application updated successfully',
                'data' => $application->load('loanType')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating application: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit a loan application for review
     */
    public function submitApplication($id): JsonResponse
    {
        try {
            $application = LoanApplication::findOrFail($id);

            // Check authorization
            if ($application->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Can only submit draft applications
            if ($application->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft applications can be submitted'
                ], 422);
            }

            // Perform eligibility check
            if (!$application->isEligibleForApproval()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application does not meet eligibility requirements'
                ], 422);
            }

            $application->update([
                'status' => 'submitted',
                'submitted_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully',
                'data' => $application->load('loanType')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting application: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move application to next stage (admin/internal use)
     */
    public function moveToNextStage($id): JsonResponse
    {
        try {
            $application = LoanApplication::findOrFail($id);

            // Check authorization (admin only)
            if (!Auth::user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $application->moveToNextStage();

            return response()->json([
                'success' => true,
                'message' => 'Application moved to next stage',
                'data' => $application
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error moving application: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check loan types available to user
     */
    public function getAvailableLoanTypes(): JsonResponse
    {
        try {
            $user = Auth::user();
            $loanTypes = LoanType::where('is_active', true)->get();

            // Determine eligibility for each type
            $typesWithEligibility = $loanTypes->map(function ($type) use ($user) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'minimum_amount' => $type->minimum_amount,
                    'maximum_amount' => $type->maximum_amount,
                    'interest_rate' => $type->interest_rate,
                    'is_eligible' => $type->isUserEligible($user)
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $typesWithEligibility
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching loan types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get applications for admin review
     */
    public function getApplicationsForReview(Request $request): JsonResponse
    {
        try {
            // Check authorization
            if (!Auth::user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $query = LoanApplication::with('loanType', 'user');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by stage
            if ($request->has('stage')) {
                $query->where('stage', $request->stage);
            }

            $applications = $query->orderBy('submitted_at', 'asc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $applications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching applications: ' . $e->getMessage()
            ], 500);
        }
    }
}
