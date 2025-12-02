<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KYCController;
use App\Http\Controllers\TwoFAController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanApplicationController;
use App\Http\Controllers\LoanTypeController;
use App\Http\Controllers\GuarantorController;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/password-reset/request', [AuthController::class, 'requestPasswordReset']);
    Route::post('/password-reset/confirm', [AuthController::class, 'resetPassword']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // Member routes
    Route::prefix('member')->group(function () {
        Route::get('/profile', [MemberController::class, 'profile']);
        Route::put('/profile', [MemberController::class, 'updateProfile']);
        Route::get('/dashboard', [MemberController::class, 'dashboard']);
        Route::get('/transactions', [MemberController::class, 'transactions']);
        Route::get('/savings', [MemberController::class, 'savings']);
        Route::get('/loans', [MemberController::class, 'loans']);
    });

    // KYC routes
    Route::prefix('kyc')->group(function () {
        Route::post('/submit', [KYCController::class, 'submit']);
        Route::get('/status', [KYCController::class, 'status']);
    });

    // 2FA routes
    Route::prefix('2fa')->group(function () {
        Route::post('/setup', [TwoFAController::class, 'setup']);
        Route::post('/confirm', [TwoFAController::class, 'confirm']);
        Route::post('/verify', [TwoFAController::class, 'verify']);
        Route::post('/disable', [TwoFAController::class, 'disable']);
    });

    // Loan routes
    Route::prefix('loans')->group(function () {
        Route::post('/apply', [LoanController::class, 'apply']);
        Route::get('/{id}', [LoanController::class, 'show']);
        Route::post('/calculate', [LoanController::class, 'calculate']);
        Route::post('/{id}/payment', [LoanController::class, 'makePayment']);
        
        // Admin only
        Route::get('/admin/pending', [LoanController::class, 'pending'])->middleware('admin');
        Route::post('/{id}/approve', [LoanController::class, 'approve'])->middleware('admin');
        Route::post('/{id}/reject', [LoanController::class, 'reject'])->middleware('admin');
    });

    // Loan Applications routes
    Route::prefix('loan-applications')->group(function () {
        Route::get('/my-applications', [LoanApplicationController::class, 'getUserApplications']);
        Route::get('/available-types', [LoanApplicationController::class, 'getAvailableLoanTypes']);
        Route::post('/create', [LoanApplicationController::class, 'createApplication']);
        Route::get('/{id}', [LoanApplicationController::class, 'getApplication']);
        Route::put('/{id}', [LoanApplicationController::class, 'updateApplication']);
        Route::post('/{id}/submit', [LoanApplicationController::class, 'submitApplication']);
        Route::post('/{id}/next-stage', [LoanApplicationController::class, 'moveToNextStage'])->middleware('admin');
        Route::get('/admin/review', [LoanApplicationController::class, 'getApplicationsForReview'])->middleware('admin');
    });

    // Loan Types routes
    Route::prefix('loan-types')->group(function () {
        Route::get('/', [LoanTypeController::class, 'index']); // List all active loan types
        Route::get('/{id}', [LoanTypeController::class, 'show']); // Get specific loan type
        Route::get('/{id}/calculate', [LoanTypeController::class, 'calculate']); // Calculate loan details
        
        // Admin only
        Route::post('/', [LoanTypeController::class, 'store'])->middleware('admin');
        Route::put('/{id}', [LoanTypeController::class, 'update'])->middleware('admin');
        Route::delete('/{id}', [LoanTypeController::class, 'destroy'])->middleware('admin');
        Route::get('/admin/all', [LoanTypeController::class, 'allWithInactive'])->middleware('admin');
    });

    // Guarantor routes
    Route::prefix('loans/{loanId}/guarantors')->group(function () {
        Route::get('/', [GuarantorController::class, 'index']); // Get all guarantors for a loan
        Route::post('/invite', [GuarantorController::class, 'invite']); // Invite a guarantor
        Route::delete('/{id}', [GuarantorController::class, 'destroy']); // Remove guarantor from loan
    });

    Route::prefix('guarantors')->group(function () {
        Route::get('/{id}', [GuarantorController::class, 'show']); // Get specific guarantor
        Route::post('/{id}/documents', [GuarantorController::class, 'uploadDocument']); // Upload verification document
        Route::get('/{id}/documents', [GuarantorController::class, 'getDocuments']); // Get verification documents
        Route::get('/{id}/qr-code', [GuarantorController::class, 'getQRCode']); // Get QR code
        Route::post('/{id}/verify', [GuarantorController::class, 'verify'])->middleware('admin'); // Verify guarantor (admin)
    });

    Route::prefix('guarantor')->group(function () {
        Route::get('/pending-requests', [GuarantorController::class, 'myPendingRequests']); // Get user's pending guarantor requests
        Route::get('/my-obligations', [GuarantorController::class, 'myObligations']); // Get user's guarantor obligations
    });

    // Guarantor invitation public routes (no auth required)
});

// Public guarantor invitation routes (for accepting via QR code)
Route::prefix('guarantor-invitations')->group(function () {
    Route::post('/{token}/accept', [GuarantorController::class, 'acceptByToken']); // Accept invitation via QR token
    Route::post('/{token}/decline', [GuarantorController::class, 'declineByToken']); // Decline invitation via QR token
});
