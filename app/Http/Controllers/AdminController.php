<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Loan;
use App\Models\KYCVerification;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Get admin dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $totalMembers = User::where('role', 'member')->count();
        $activeLoans = Loan::where('status', 'active')->count();
        $pendingLoans = Loan::where('status', 'pending')->count();
        $totalLoanAmount = Loan::sum('amount');
        $totalDisbursed = Loan::where('status', '!=', 'pending')->sum('amount');

        $recentLoans = Loan::with('member')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'memberName' => $loan->member->full_name,
                    'amount' => $loan->amount,
                    'status' => $loan->status,
                    'createdAt' => $loan->created_at,
                ];
            });

        $pendingKYC = KYCVerification::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'totalMembers' => $totalMembers,
                'activeLoans' => $activeLoans,
                'pendingLoans' => $pendingLoans,
                'totalLoanAmount' => $totalLoanAmount,
                'totalDisbursed' => $totalDisbursed,
                'pendingKYC' => $pendingKYC,
                'recentLoans' => $recentLoans,
            ],
        ], 200);
    }

    /**
     * Get all members
     */
    public function getMembers(Request $request): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $limit = $request->query('limit', 20);
        $offset = $request->query('offset', 0);
        $search = $request->query('search', '');

        $query = User::where('role', 'member');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $members = $query->paginate($limit, ['*'], 'page', ($offset / $limit) + 1);

        return response()->json([
            'success' => true,
            'data' => $members->items(),
            'pagination' => [
                'total' => $members->total(),
                'limit' => $limit,
                'offset' => $offset,
            ],
        ], 200);
    }

    /**
     * Get member details
     */
    public function getMemberDetails(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $member = User::findOrFail($id);

        $loans = $member->loans()->get();
        $contributions = $member->contributions()->sum('amount');
        $savings = $member->getTotalSavings();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $member->id,
                'firstName' => $member->first_name,
                'lastName' => $member->last_name,
                'email' => $member->email,
                'phone' => $member->phone,
                'country' => $member->country,
                'kycStatus' => $member->kyc_status,
                'totalSavings' => $savings,
                'totalContributions' => $contributions,
                'activeLoans' => $loans->where('status', 'active')->count(),
                'totalLoans' => $loans->count(),
                'createdAt' => $member->created_at,
            ],
        ], 200);
    }

    /**
     * Get pending KYC verifications
     */
    public function getPendingKYC(Request $request): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $kyc = KYCVerification::where('status', 'pending')
            ->with('user')
            ->latest()
            ->get()
            ->map(function ($k) {
                return [
                    'id' => $k->id,
                    'userId' => $k->user_id,
                    'userName' => $k->user->full_name,
                    'documentType' => $k->document_type,
                    'documentNumber' => $k->document_number,
                    'submittedAt' => $k->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $kyc,
        ], 200);
    }

    /**
     * Get KYC details for review
     */
    public function getKYCDetails(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $kyc = KYCVerification::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $kyc->id,
                'userId' => $kyc->user_id,
                'userName' => $kyc->user->full_name,
                'documentType' => $kyc->document_type,
                'documentNumber' => $kyc->document_number,
                'dateOfBirth' => $kyc->date_of_birth,
                'documentImageUrl' => asset("storage/{$kyc->document_image_path}"),
                'proofOfAddressUrl' => asset("storage/{$kyc->proof_of_address_path}"),
                'status' => $kyc->status,
                'submittedAt' => $kyc->created_at,
            ],
        ], 200);
    }

    /**
     * Approve KYC
     */
    public function approveKYC(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $kyc = KYCVerification::findOrFail($id);
        $kyc->approve($request->user()->id);

        AuditLog::log('KYC_APPROVED', 'KYCVerification', $id);

        return response()->json([
            'success' => true,
            'message' => 'KYC approved successfully',
        ], 200);
    }

    /**
     * Reject KYC
     */
    public function rejectKYC(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $kyc = KYCVerification::findOrFail($id);
            $kyc->reject($validated['reason'], $request->user()->id);

            AuditLog::log('KYC_REJECTED', 'KYCVerification', $id, [], ['reason' => $validated['reason']]);

            return response()->json([
                'success' => true,
                'message' => 'KYC rejected successfully',
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
     * Get loan approvals
     */
    public function getLoanApprovals(Request $request): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $loans = Loan::where('status', 'pending')
            ->with('member')
            ->latest()
            ->get()
            ->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'memberId' => $loan->member_id,
                    'memberName' => $loan->member->full_name,
                    'amount' => $loan->amount,
                    'purpose' => $loan->purpose,
                    'durationMonths' => $loan->duration_months,
                    'interestRate' => $loan->interest_rate,
                    'monthlyPayment' => $loan->monthly_payment_amount,
                    'appliedAt' => $loan->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $loans,
        ], 200);
    }

    /**
     * Approve loan
     */
    public function approveLoan(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $loan = Loan::findOrFail($id);

        if ($loan->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Loan is not pending',
            ], 400);
        }

        $loan->approve($request->user()->id);

        AuditLog::log('LOAN_APPROVED', 'Loan', $id);

        return response()->json([
            'success' => true,
            'message' => 'Loan approved successfully',
        ], 200);
    }

    /**
     * Reject loan
     */
    public function rejectLoan(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $loan = Loan::findOrFail($id);

            if ($loan->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan is not pending',
                ], 400);
            }

            $loan->reject($validated['reason'], $request->user()->id);

            AuditLog::log('LOAN_REJECTED', 'Loan', $id, [], ['reason' => $validated['reason']]);

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
     * Disburse loan
     */
    public function disburseLoan(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $loan = Loan::findOrFail($id);

        if ($loan->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Loan must be approved before disbursement',
            ], 400);
        }

        $loan->disburse();

        AuditLog::log('LOAN_DISBURSED', 'Loan', $id);

        return response()->json([
            'success' => true,
            'message' => 'Loan disbursed successfully',
        ], 200);
    }

    /**
     * Get audit logs
     */
    public function getAuditLogs(Request $request): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $limit = $request->query('limit', 50);
        $offset = $request->query('offset', 0);

        $logs = AuditLog::with('user')
            ->latest()
            ->paginate($limit, ['*'], 'page', ($offset / $limit) + 1);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'limit' => $limit,
                'offset' => $offset,
            ],
        ], 200);
    }
}
