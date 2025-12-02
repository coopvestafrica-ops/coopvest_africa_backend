<?php

namespace App\Http\Controllers;

use App\Models\KYCVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KYCController extends Controller
{
    /**
     * Submit KYC documents
     */
    public function submit(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'document_type' => 'required|in:passport,national_id,drivers_license,voter_id',
                'document_number' => 'required|string|max:50',
                'date_of_birth' => 'required|date',
                'document_image' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'proof_of_address' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            $user = $request->user();

            // Store files
            $documentPath = $request->file('document_image')->store('kyc/documents', 'public');
            $addressPath = $request->file('proof_of_address')->store('kyc/address', 'public');

            $kyc = KYCVerification::create([
                'user_id' => $user->id,
                'document_type' => $validated['document_type'],
                'document_number' => $validated['document_number'],
                'date_of_birth' => $validated['date_of_birth'],
                'document_image_path' => $documentPath,
                'proof_of_address_path' => $addressPath,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KYC documents submitted successfully',
                'data' => [
                    'id' => $kyc->id,
                    'status' => $kyc->status,
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
     * Get KYC status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $kyc = $user->kycVerification()->latest()->first();

        if (!$kyc) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'not_submitted',
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $kyc->status,
                'verified_at' => $kyc->verified_at,
                'rejection_reason' => $kyc->rejection_reason,
            ],
        ], 200);
    }

    /**
     * Get KYC details (Admin)
     */
    public function getDetails(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $kyc = KYCVerification::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $kyc->id,
                'user_id' => $kyc->user_id,
                'user_name' => $kyc->user->full_name,
                'document_type' => $kyc->document_type,
                'document_number' => $kyc->document_number,
                'date_of_birth' => $kyc->date_of_birth,
                'document_image_url' => asset("storage/{$kyc->document_image_path}"),
                'proof_of_address_url' => asset("storage/{$kyc->proof_of_address_path}"),
                'status' => $kyc->status,
                'created_at' => $kyc->created_at,
            ],
        ], 200);
    }

    /**
     * Approve KYC (Admin)
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        $kyc = KYCVerification::findOrFail($id);
        $kyc->approve($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'KYC approved successfully',
        ], 200);
    }

    /**
     * Reject KYC (Admin)
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $this->authorize('isAdmin', $request->user());

        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $kyc = KYCVerification::findOrFail($id);
            $kyc->reject($validated['reason'], $request->user()->id);

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
}
