<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\QRToken;
use App\Models\Guarantor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class QRController extends Controller
{
    /**
     * Generate a new QR token for a loan
     * 
     * POST /api/v1/qr/generate
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'loan_id' => 'required|exists:loans,id',
                'duration_minutes' => 'nullable|integer|min:5|max:1440', // 5 min to 24 hours
            ]);

            $user = Auth::user();
            $loan = Loan::findOrFail($validated['loan_id']);

            // Verify user has permission to generate QR for this loan
            if ($loan->user_id !== $user->id && !$user->hasRole(['admin', 'staff'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to generate QR for this loan',
                ], 403);
            }

            // Check loan status
            if (!in_array($loan->status, ['pending', 'approved', 'active'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot generate QR for loan with status: ' . $loan->status,
                ], 422);
            }

            // Revoke any existing active QR tokens for this loan
            QRToken::forLoan($loan->id)
                ->active()
                ->update(['status' => 'revoked']);

            // Create QR data
            $qrData = [
                'loan_id' => $loan->id,
                'amount' => $loan->amount,
                'duration' => $loan->duration,
                'applicant_id' => $loan->user_id,
                'applicant_name' => $loan->user->name,
                'generated_at' => now()->toIso8601String(),
                'type' => 'loan_guarantor',
            ];

            // Create QR token
            $durationMinutes = $validated['duration_minutes'] ?? 15; // Default 15 minutes
            $expiresAt = now()->addMinutes($durationMinutes);

            $qrToken = QRToken::create([
                'loan_id' => $loan->id,
                'created_by' => $user->id,
                'qr_data' => $qrData,
                'expires_at' => $expiresAt,
                'status' => 'active',
                'metadata' => [
                    'duration_minutes' => $durationMinutes,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            Log::info('QR Token generated', [
                'qr_token_id' => $qrToken->id,
                'loan_id' => $loan->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'QR token generated successfully',
                'data' => [
                    'token' => $qrToken->token,
                    'qr_data' => $qrToken->qr_data,
                    'expires_at' => $qrToken->expires_at,
                    'expires_in_seconds' => $qrToken->time_remaining,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('QR generation error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR token',
            ], 500);
        }
    }

    /**
     * Validate a scanned QR token
     * 
     * POST /api/v1/qr/validate
     */
    public function validate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'qr_token' => 'required|string',
                'guarantor_id' => 'required|exists:users,id',
            ]);

            $user = Auth::user();
            $guarantorId = $validated['guarantor_id'];

            // Find QR token
            $qrToken = QRToken::where('token', $validated['qr_token'])->first();

            if (!$qrToken) {
                Log::warning('Invalid QR token attempted', [
                    'token' => substr($validated['qr_token'], 0, 10) . '...',
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR token',
                ], 404);
            }

            // Check if token is valid
            if (!$qrToken->isValidForScanning()) {
                $reason = $qrToken->is_expired ? 'expired' : 'invalid_status';
                
                return response()->json([
                    'success' => false,
                    'message' => 'QR token is ' . $reason,
                    'status' => $qrToken->status,
                    'expires_at' => $qrToken->expires_at,
                ], 422);
            }

            // Verify guarantor eligibility
            $guarantor = Guarantor::where('user_id', $guarantorId)
                ->where('loan_id', $qrToken->loan_id)
                ->first();

            if (!$guarantor) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a guarantor for this loan',
                ], 403);
            }

            if ($guarantor->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Guarantor status is not pending',
                    'current_status' => $guarantor->status,
                ], 422);
            }

            // Mark token as scanned
            $guarantorUser = \App\Models\User::findOrFail($guarantorId);
            $qrToken->markAsScanned($guarantorUser);

            // Update guarantor status
            $guarantor->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            // Get loan details
            $loan = $qrToken->loan;

            Log::info('QR token validated successfully', [
                'qr_token_id' => $qrToken->id,
                'loan_id' => $loan->id,
                'guarantor_id' => $guarantorId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'QR token validated successfully',
                'data' => [
                    'loan' => [
                        'id' => $loan->id,
                        'amount' => $loan->amount,
                        'duration' => $loan->duration,
                        'status' => $loan->status,
                    ],
                    'guarantor' => [
                        'id' => $guarantor->id,
                        'status' => $guarantor->status,
                        'verified_at' => $guarantor->verified_at,
                    ],
                    'qr_token' => [
                        'status' => $qrToken->status,
                        'scanned_at' => $qrToken->scanned_at,
                    ],
                ],
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('QR validation error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate QR token',
            ], 500);
        }
    }

    /**
     * Get QR tokens for a loan
     * 
     * GET /api/v1/qr/tokens/{loanId}
     */
    public function getTokens($loanId): JsonResponse
    {
        try {
            $user = Auth::user();
            $loan = Loan::findOrFail($loanId);

            // Verify permission
            if ($loan->user_id !== $user->id && !$user->hasRole(['admin', 'staff'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $tokens = QRToken::forLoan($loanId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($token) {
                    return [
                        'id' => $token->id,
                        'status' => $token->status,
                        'expires_at' => $token->expires_at,
                        'is_expired' => $token->is_expired,
                        'scanned_by' => $token->scanned_by,
                        'scanned_at' => $token->scanned_at,
                        'created_at' => $token->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $tokens,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get QR tokens error', [
                'error' => $e->getMessage(),
                'loan_id' => $loanId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve QR tokens',
            ], 500);
        }
    }

    /**
     * Get QR token status
     * 
     * GET /api/v1/qr/status/{token}
     */
    public function getStatus($token): JsonResponse
    {
        try {
            $qrToken = QRToken::where('token', $token)->first();

            if (!$qrToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR token not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $qrToken->getValidationData(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get QR status error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve QR status',
            ], 500);
        }
    }

    /**
     * Revoke a QR token
     * 
     * POST /api/v1/qr/revoke
     */
    public function revoke(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'qr_token' => 'required|string',
            ]);

            $user = Auth::user();
            $qrToken = QRToken::where('token', $validated['qr_token'])->first();

            if (!$qrToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR token not found',
                ], 404);
            }

            // Verify permission
            if ($qrToken->created_by !== $user->id && !$user->hasRole(['admin', 'staff'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to revoke this token',
                ], 403);
            }

            $qrToken->revoke();

            Log::info('QR token revoked', [
                'qr_token_id' => $qrToken->id,
                'loan_id' => $qrToken->loan_id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'QR token revoked successfully',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('QR revoke error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke QR token',
            ], 500);
        }
    }

    /**
     * Cleanup expired QR tokens (scheduled task)
     * 
     * This should be called via a scheduled command
     */
    public function cleanupExpired(): JsonResponse
    {
        try {
            $count = QRToken::expired()
                ->where('status', '!=', 'revoked')
                ->update(['status' => 'expired']);

            Log::info('QR tokens cleanup completed', [
                'expired_count' => $count,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cleanup completed',
                'expired_count' => $count,
            ], 200);

        } catch (\Exception $e) {
            Log::error('QR cleanup error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed',
            ], 500);
        }
    }
}
