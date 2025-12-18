<?php

/**
 * API Integration Routes for CoopVest Africa
 * Defines all API endpoints for cross-platform communication
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanApplicationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GuarantorController;
use App\Http\Controllers\KYCController;
use App\Http\Controllers\QRController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Authentication
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'getCurrentUser']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

        // Users
        Route::apiResource('users', UserController::class);
        Route::get('/users/{id}/loans', [UserController::class, 'getUserLoans']);
        Route::get('/users/{id}/guarantor-requests', [UserController::class, 'getGuarantorRequests']);

        // Loans
        Route::apiResource('loans', LoanController::class);
        Route::get('/loans/{id}/status', [LoanController::class, 'getStatus']);
        Route::post('/loans/{id}/apply', [LoanApplicationController::class, 'apply']);
        Route::get('/loans/{id}/applications', [LoanController::class, 'getApplications']);

        // Loan Applications
        Route::apiResource('loan-applications', LoanApplicationController::class);
        Route::post('/loan-applications/{id}/submit', [LoanApplicationController::class, 'submit']);
        Route::post('/loan-applications/{id}/approve', [LoanApplicationController::class, 'approve']);
        Route::post('/loan-applications/{id}/reject', [LoanApplicationController::class, 'reject']);
        Route::post('/loan-applications/{id}/disburse', [LoanApplicationController::class, 'disburse']);

        // Guarantors
        Route::apiResource('guarantors', GuarantorController::class);
        Route::post('/guarantors/{id}/approve', [GuarantorController::class, 'approve']);
        Route::post('/guarantors/{id}/reject', [GuarantorController::class, 'reject']);
        Route::get('/guarantor-requests', [GuarantorController::class, 'getRequests']);

        // KYC
        Route::apiResource('kyc', KYCController::class);
        Route::post('/kyc/{id}/verify', [KYCController::class, 'verify']);
        Route::post('/kyc/{id}/reject', [KYCController::class, 'reject']);

        // QR Codes
        Route::post('/qr/generate', [QRController::class, 'generate']);
        Route::get('/qr/{code}', [QRController::class, 'verify']);

        // Admin routes
        Route::middleware('role:admin,super_admin')->group(function () {
            Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
            Route::get('/admin/users', [AdminController::class, 'listUsers']);
            Route::get('/admin/loans', [AdminController::class, 'listLoans']);
            Route::get('/admin/applications', [AdminController::class, 'listApplications']);
            Route::post('/admin/feature-flags', [AdminController::class, 'updateFeatureFlags']);
            Route::get('/admin/reports', [AdminController::class, 'getReports']);
            Route::get('/admin/analytics', [AdminController::class, 'getAnalytics']);
        });

        // Super admin routes
        Route::middleware('role:super_admin')->group(function () {
            Route::post('/admin/users/{id}/role', [AdminController::class, 'updateUserRole']);
            Route::post('/admin/users/{id}/status', [AdminController::class, 'updateUserStatus']);
            Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);
        });
    });

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now(),
            'version' => '1.0.0',
        ]);
    });
});
