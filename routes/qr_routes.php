<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QRController;

/*
|--------------------------------------------------------------------------
| QR Code Routes
|--------------------------------------------------------------------------
|
| These routes handle QR code generation, validation, and management
| for the loan guarantor system.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // QR Generation
    Route::post('/qr/generate', [QRController::class, 'generate'])
        ->name('qr.generate')
        ->middleware('throttle:10,1'); // 10 requests per minute

    // QR Validation
    Route::post('/qr/validate', [QRController::class, 'validate'])
        ->name('qr.validate')
        ->middleware('throttle:5,1'); // 5 requests per minute

    // Get QR Tokens for a Loan
    Route::get('/qr/tokens/{loanId}', [QRController::class, 'getTokens'])
        ->name('qr.tokens')
        ->where('loanId', '[0-9]+');

    // Revoke QR Token
    Route::post('/qr/revoke', [QRController::class, 'revoke'])
        ->name('qr.revoke');

    // Cleanup Expired Tokens (Admin only)
    Route::post('/qr/cleanup', [QRController::class, 'cleanupExpired'])
        ->name('qr.cleanup')
        ->middleware('role:admin,staff');
});

// Public QR Status Check (no auth required)
Route::get('/qr/status/{token}', [QRController::class, 'getStatus'])
    ->name('qr.status')
    ->middleware('throttle:30,1'); // 30 requests per minute
