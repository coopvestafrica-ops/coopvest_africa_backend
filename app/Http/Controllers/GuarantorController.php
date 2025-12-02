<?php

namespace App\Http\Controllers;

use App\Models\Guarantor;
use App\Models\GuarantorInvitation;
use App\Models\GuarantorVerificationDocument;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class GuarantorController extends Controller
{
    /**
     * Get all guarantors for a loan
     * GET /api/loans/{loanId}/guarantors
     */
    public function index($loanId)
    {
        $loan = Loan::findOrFail($loanId);
        $this->authorize('view', $loan);

        $guarantors = $loan->guarantors()
            ->with('guarantorUser', 'verificationDocuments')
            ->get()
            ->map(fn($g) => $this->formatGuarantorResponse($g));

        return response()->json([
            'success' => true,
            'data' => $guarantors,
            'count' => count($guarantors),
        ]);
    }

    /**
     * Get a specific guarantor
     * GET /api/guarantors/{id}
     */
    public function show($id)
    {
        $guarantor = Guarantor::with('guarantorUser', 'verificationDocuments')->findOrFail($id);
        $this->authorize('view', $guarantor->loan);

        return response()->json([
            'success' => true,
            'data' => $this->formatGuarantorResponse($guarantor),
        ]);
    }

    /**
     * Invite a guarantor for a loan
     * POST /api/loans/{loanId}/guarantors/invite
     */
    public function invite(Request $request, $loanId)
    {
        $loan = Loan::findOrFail($loanId);
        $this->authorize('update', $loan);

        $validated = $request->validate([
            'guarantor_email' => 'required|email',
            'relationship' => 'required|in:friend,family,colleague,business_partner',
            'employment_verification_required' => 'boolean',
            'liability_amount' => 'nullable|numeric|min:0',
        ]);

        // Check if already invited
        $existing = GuarantorInvitation::where('loan_id', $loanId)
            ->where('guarantor_email', strtolower($validated['guarantor_email']))
            ->where('status', 'pending')
            ->first();

        if ($existing && !$existing->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation already sent to this email address',
            ], 400);
        }

        // Create invitation
        $token = Str::random(64);
        $expiresAt = now()->addDays(7); // 7 days expiration
        $link = url("/guarantor-accept/{$token}");

        $invitation = GuarantorInvitation::create([
            'loan_id' => $loanId,
            'guarantor_email' => strtolower($validated['guarantor_email']),
            'invitation_token' => $token,
            'invitation_link' => $link,
            'status' => 'pending',
            'sent_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        // Create placeholder guarantor record
        $guarantor = Guarantor::create([
            'loan_id' => $loanId,
            'relationship' => $validated['relationship'],
            'employment_verification_required' => $validated['employment_verification_required'] ?? false,
            'confirmation_status' => 'pending',
            'verification_status' => 'pending',
            'qr_code_token' => $token,
            'qr_code_expires_at' => $expiresAt,
            'liability_amount' => $validated['liability_amount'] ?? $loan->amount,
        ]);

        // Generate QR code
        $this->generateQRCode($guarantor, $link);

        // TODO: Send email invitation
        // Mail::to($validated['guarantor_email'])->send(new GuarantorInvitationMail($invitation, $link));

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent successfully',
            'data' => $this->formatGuarantorResponse($guarantor),
        ], 201);
    }

    /**
     * Accept guarantor invitation via QR code token
     * POST /api/guarantor-invitations/{token}/accept
     */
    public function acceptByToken(Request $request, $token)
    {
        $validated = $request->validate([
            'guarantor_email' => 'required|email',
        ]);

        $guarantor = Guarantor::where('qr_code_token', $token)
            ->where('qr_code_expires_at', '>', now())
            ->firstOrFail();

        // Find or create guarantor user
        $user = User::where('email', strtolower($validated['guarantor_email']))->first();

        if ($user) {
            $guarantor->guarantor_user_id = $user->id;
        }

        $guarantor->setConfirmationStatus('accepted');

        // Update invitation status
        GuarantorInvitation::where('qr_code_token', $token)->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invitation accepted successfully',
            'data' => $this->formatGuarantorResponse($guarantor),
        ]);
    }

    /**
     * Decline guarantor invitation
     * POST /api/guarantor-invitations/{token}/decline
     */
    public function declineByToken(Request $request, $token)
    {
        $guarantor = Guarantor::where('qr_code_token', $token)
            ->where('qr_code_expires_at', '>', now())
            ->firstOrFail();

        $guarantor->setConfirmationStatus('declined');

        // Update invitation status
        GuarantorInvitation::where('qr_code_token', $token)->update([
            'status' => 'declined',
            'declined_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invitation declined',
            'data' => $this->formatGuarantorResponse($guarantor),
        ]);
    }

    /**
     * Get guarantor's pending requests
     * GET /api/guarantor/pending-requests
     */
    public function myPendingRequests()
    {
        $user = auth()->user();

        $pendingGuarantorships = Guarantor::where('guarantor_user_id', $user->id)
            ->pending()
            ->with('loan', 'loan.loanType')
            ->get()
            ->map(fn($g) => $this->formatGuarantorResponse($g));

        return response()->json([
            'success' => true,
            'data' => $pendingGuarantorships,
            'count' => count($pendingGuarantorships),
        ]);
    }

    /**
     * Get guarantor's obligations
     * GET /api/guarantor/my-obligations
     */
    public function myObligations()
    {
        $user = auth()->user();

        $obligations = Guarantor::where('guarantor_user_id', $user->id)
            ->active() // Only accepted and verified
            ->with('loan', 'loan.loanType', 'loan.borrower')
            ->get()
            ->map(function($g) {
                $formatted = $this->formatGuarantorResponse($g);
                $formatted['loan_details'] = [
                    'amount' => $g->loan->amount,
                    'duration_months' => $g->loan->duration_months,
                    'current_balance' => $g->loan->current_balance,
                    'borrower' => $g->loan->borrower->only(['id', 'first_name', 'last_name', 'email']),
                ];
                return $formatted;
            });

        return response()->json([
            'success' => true,
            'data' => $obligations,
            'count' => count($obligations),
        ]);
    }

    /**
     * Upload verification document
     * POST /api/guarantors/{id}/documents
     */
    public function uploadDocument(Request $request, $id)
    {
        $guarantor = Guarantor::findOrFail($id);
        $this->authorize('update', $guarantor->loan);

        $validated = $request->validate([
            'document_type' => 'required|in:employment_letter,id_document,bank_statement,payslip,business_license,registration_document',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        $file = $request->file('document');
        $path = $file->store('guarantor-documents', 'public');

        $document = GuarantorVerificationDocument::create([
            'guarantor_id' => $id,
            'document_type' => $validated['document_type'],
            'document_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => $document,
        ], 201);
    }

    /**
     * Get verification documents for a guarantor
     * GET /api/guarantors/{id}/documents
     */
    public function getDocuments($id)
    {
        $guarantor = Guarantor::findOrFail($id);
        $this->authorize('view', $guarantor->loan);

        $documents = $guarantor->verificationDocuments()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $documents,
            'count' => count($documents),
        ]);
    }

    /**
     * Delete guarantor from loan
     * DELETE /api/loans/{loanId}/guarantors/{id}
     */
    public function destroy($loanId, $id)
    {
        $loan = Loan::findOrFail($loanId);
        $guarantor = Guarantor::findOrFail($id);

        $this->authorize('update', $loan);

        if ($guarantor->loan_id !== $loan->id) {
            return response()->json([
                'success' => false,
                'message' => 'Guarantor does not belong to this loan',
            ], 400);
        }

        $guarantor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guarantor removed successfully',
        ]);
    }

    /**
     * Verify guarantor (admin only)
     * POST /api/guarantors/{id}/verify
     */
    public function verify(Request $request, $id)
    {
        $this->authorize('isAdmin');

        $guarantor = Guarantor::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:verified,rejected',
            'rejection_reason' => 'required_if:status,rejected|string',
        ]);

        if ($validated['status'] === 'verified') {
            $guarantor->setVerificationStatus('verified');
        } else {
            $guarantor->setVerificationStatus('rejected');
            // TODO: Send rejection email with reason
        }

        return response()->json([
            'success' => true,
            'message' => "Guarantor {$validated['status']} successfully",
            'data' => $this->formatGuarantorResponse($guarantor),
        ]);
    }

    /**
     * Get QR code for a guarantor
     * GET /api/guarantors/{id}/qr-code
     */
    public function getQRCode($id)
    {
        $guarantor = Guarantor::findOrFail($id);
        $this->authorize('view', $guarantor->loan);

        if (!$guarantor->qr_code) {
            return response()->json([
                'success' => false,
                'message' => 'QR code not available',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'qr_code' => $guarantor->qr_code,
                'expires_at' => $guarantor->qr_code_expires_at,
                'is_valid' => $guarantor->isQRCodeValid(),
            ],
        ]);
    }

    /**
     * Helper: Format guarantor response
     */
    private function formatGuarantorResponse(Guarantor $guarantor): array
    {
        return [
            'id' => $guarantor->id,
            'loan_id' => $guarantor->loan_id,
            'guarantor_user_id' => $guarantor->guarantor_user_id,
            'guarantor_user' => $guarantor->guarantorUser?->only(['id', 'first_name', 'last_name', 'email', 'phone']),
            'relationship' => $guarantor->relationship,
            'relationship_label' => $guarantor->getRelationshipLabel(),
            'verification_status' => $guarantor->verification_status,
            'verification_status_label' => $guarantor->getVerificationStatusLabel(),
            'verification_badge_color' => $guarantor->getVerificationBadgeColor(),
            'confirmation_status' => $guarantor->confirmation_status,
            'confirmation_status_label' => $guarantor->getConfirmationStatusLabel(),
            'confirmation_badge_color' => $guarantor->getStatusBadgeColor(),
            'employment_verification_required' => $guarantor->employment_verification_required,
            'employment_verification_completed' => $guarantor->employment_verification_completed,
            'is_active' => $guarantor->isActive(),
            'is_verified' => $guarantor->isVerified(),
            'is_confirmed' => $guarantor->isConfirmed(),
            'liability_amount' => $guarantor->liability_amount,
            'documents_count' => $guarantor->verificationDocuments()->count(),
            'invitation_sent_at' => $guarantor->invitation_sent_at,
            'invitation_accepted_at' => $guarantor->invitation_accepted_at,
            'created_at' => $guarantor->created_at,
            'updated_at' => $guarantor->updated_at,
        ];
    }

    /**
     * Helper: Generate QR code
     */
    private function generateQRCode(Guarantor $guarantor, string $link): void
    {
        try {
            $qrCode = QrCode::format('png')
                ->size(500)
                ->generate($link);

            $guarantor->qr_code = 'data:image/png;base64,' . base64_encode($qrCode);
            $guarantor->save();
        } catch (\Exception $e) {
            \Log::error('Failed to generate QR code: ' . $e->getMessage());
        }
    }
}
