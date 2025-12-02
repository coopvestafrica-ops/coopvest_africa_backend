<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users',
                'phone' => 'required|string|unique:users',
                'country' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'country' => $validated['country'],
                'password' => Hash::make($validated['password']),
                'role' => 'member',
                'kyc_status' => 'pending',
                'is_active' => true,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->full_name,
                        'role' => $user->role,
                    ],
                    'token' => $token,
                    'refreshToken' => $token,
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
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is inactive',
                ], 403);
            }

            $user->update(['last_login_at' => now()]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->full_name,
                        'role' => $user->role,
                    ],
                    'token' => $token,
                    'refreshToken' => $token,
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
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Get current user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->full_name,
                'role' => $user->role,
                'kyc_status' => $user->kyc_status,
                'two_fa_enabled' => $user->two_fa_enabled,
            ],
        ], 200);
    }

    /**
     * Request password reset
     */
    public function requestPasswordReset(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                // Don't reveal if email exists
                return response()->json([
                    'success' => true,
                    'message' => 'If email exists, reset link has been sent',
                ], 200);
            }

            // In production, send email with reset link
            // For now, generate a reset token
            $resetToken = bin2hex(random_bytes(32));
            
            // Store in cache or database
            cache()->put("password_reset_{$resetToken}", $user->id, now()->addHours(1));

            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to email',
                'data' => [
                    'reset_token' => $resetToken, // In production, send via email
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
     * Reset password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $userId = cache()->get("password_reset_{$validated['token']}");

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token',
                ], 401);
            }

            $user = User::find($userId);
            $user->update(['password' => Hash::make($validated['new_password'])]);

            cache()->forget("password_reset_{$validated['token']}");

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
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
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'refreshToken' => $token,
            ],
        ], 200);
    }
}
