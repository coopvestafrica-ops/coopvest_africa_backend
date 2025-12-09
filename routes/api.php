<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanApplicationController;
use App\Http\Controllers\LoanTypeController;
use App\Http\Controllers\GuarantorController;
use App\Http\Controllers\KYCController;
use App\Http\Controllers\TwoFAController;
use App\Http\Controllers\UserSyncController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/update-profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });

    // Two-Factor Authentication
    Route::prefix('2fa')->group(function () {
        Route::post('/enable', [TwoFAController::class, 'enable']);
        Route::post('/verify', [TwoFAController::class, 'verify']);
        Route::post('/disable', [TwoFAController::class, 'disable']);
        Route::get('/status', [TwoFAController::class, 'status']);
    });

    // KYC Verification
    Route::prefix('kyc')->group(function () {
        Route::get('/', [KYCController::class, 'index']);
        Route::post('/submit', [KYCController::class, 'submit']);
        Route::get('/status', [KYCController::class, 'status']);
        Route::post('/upload-document', [KYCController::class, 'uploadDocument']);
    });

    // Member routes
    Route::prefix('member')->middleware('role:member')->group(function () {
        Route::get('/dashboard', [MemberController::class, 'dashboard']);
        Route::get('/profile', [MemberController::class, 'profile']);
        Route::put('/profile', [MemberController::class, 'updateProfile']);
        Route::get('/savings', [MemberController::class, 'savings']);
        Route::get('/transactions', [MemberController::class, 'transactions']);
        Route::get('/loans', [MemberController::class, 'loans']);
        Route::get('/contributions', [MemberController::class, 'contributions']);
    });

    // Loan Types (Public for members)
    Route::prefix('loan-types')->group(function () {
        Route::get('/', [LoanTypeController::class, 'index']);
        Route::get('/{id}', [LoanTypeController::class, 'show']);
    });

    // Loan Applications
    Route::prefix('loan-applications')->group(function () {
        Route::get('/', [LoanApplicationController::class, 'index']);
        Route::post('/', [LoanApplicationController::class, 'store']);
        Route::get('/{id}', [LoanApplicationController::class, 'show']);
        Route::put('/{id}', [LoanApplicationController::class, 'update']);
        Route::delete('/{id}', [LoanApplicationController::class, 'destroy']);
        Route::post('/{id}/submit', [LoanApplicationController::class, 'submit']);
        Route::post('/{id}/cancel', [LoanApplicationController::class, 'cancel']);
    });

    // Guarantors
    Route::prefix('guarantors')->group(function () {
        Route::get('/', [GuarantorController::class, 'index']);
        Route::post('/', [GuarantorController::class, 'store']);
        Route::get('/{id}', [GuarantorController::class, 'show']);
        Route::put('/{id}', [GuarantorController::class, 'update']);
        Route::delete('/{id}', [GuarantorController::class, 'destroy']);
        Route::post('/{id}/invite', [GuarantorController::class, 'sendInvitation']);
        Route::post('/{id}/verify', [GuarantorController::class, 'verify']);
        Route::post('/{id}/approve', [GuarantorController::class, 'approve']);
        Route::post('/{id}/reject', [GuarantorController::class, 'reject']);
    });

    // Loans (for viewing loan details)
    Route::prefix('loans')->group(function () {
        Route::get('/', [LoanController::class, 'index']);
        Route::get('/{id}', [LoanController::class, 'show']);
        Route::get('/{id}/repayment-schedule', [LoanController::class, 'repaymentSchedule']);
        Route::post('/{id}/repay', [LoanController::class, 'makeRepayment']);
    });

    // Admin routes
    Route::prefix('admin')->middleware('role:admin,super_admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/statistics', [AdminController::class, 'statistics']);
        
        // User management
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{id}', [AdminController::class, 'showUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::post('/users/{id}/activate', [AdminController::class, 'activateUser']);
        Route::post('/users/{id}/deactivate', [AdminController::class, 'deactivateUser']);
        
        // Loan management
        Route::get('/loan-applications', [AdminController::class, 'loanApplications']);
        Route::post('/loan-applications/{id}/approve', [AdminController::class, 'approveLoan']);
        Route::post('/loan-applications/{id}/reject', [AdminController::class, 'rejectLoan']);
        Route::post('/loan-applications/{id}/disburse', [AdminController::class, 'disburseLoan']);
        
        // KYC management
        Route::get('/kyc-verifications', [AdminController::class, 'kycVerifications']);
        Route::post('/kyc-verifications/{id}/approve', [AdminController::class, 'approveKYC']);
        Route::post('/kyc-verifications/{id}/reject', [AdminController::class, 'rejectKYC']);
        
        // Reports
        Route::get('/reports/loans', [AdminController::class, 'loanReports']);
        Route::get('/reports/members', [AdminController::class, 'memberReports']);
        Route::get('/reports/financial', [AdminController::class, 'financialReports']);
    });

    // Super Admin routes
    Route::prefix('super-admin')->middleware('role:super_admin')->group(function () {
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard']);
        
        // Global settings
        Route::get('/settings', [SuperAdminController::class, 'getSettings']);
        Route::put('/settings', [SuperAdminController::class, 'updateSettings']);
        
        // Admin management
        Route::get('/admins', [SuperAdminController::class, 'admins']);
        Route::post('/admins', [SuperAdminController::class, 'createAdmin']);
        Route::put('/admins/{id}', [SuperAdminController::class, 'updateAdmin']);
        Route::delete('/admins/{id}', [SuperAdminController::class, 'deleteAdmin']);
        
        // Loan type management
        Route::post('/loan-types', [SuperAdminController::class, 'createLoanType']);
        Route::put('/loan-types/{id}', [SuperAdminController::class, 'updateLoanType']);
        Route::delete('/loan-types/{id}', [SuperAdminController::class, 'deleteLoanType']);
        
        // Audit logs
        Route::get('/audit-logs', [SuperAdminController::class, 'auditLogs']);
        Route::get('/audit-logs/{id}', [SuperAdminController::class, 'showAuditLog']);
        
        // System health
        Route::get('/system/health', [SuperAdminController::class, 'systemHealth']);
        Route::get('/system/stats', [SuperAdminController::class, 'systemStats']);
    });
});

// Firebase User Sync Routes
Route::prefix('firebase')->group(function () {
    // Public sync endpoint (requires Firebase token)
    Route::post('/sync', [UserSyncController::class, 'sync']);
    Route::get('/sync/status', [UserSyncController::class, 'status']);
    
    // Bulk sync endpoint (requires Firebase token)
    Route::post('/sync/bulk', [UserSyncController::class, 'bulkSync']);
});

// Protected Firebase routes (require authentication)
Route::middleware('auth:sanctum')->prefix('firebase')->group(function () {
    Route::post('/sync', [UserSyncController::class, 'sync']);
    Route::get('/sync/status', [UserSyncController::class, 'status']);
    Route::post('/sync/bulk', [UserSyncController::class, 'bulkSync']);
});

// Health check endpoint (public)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'service' => 'CoopVest Africa API',
        'version' => '1.0.0',
    ]);
});
