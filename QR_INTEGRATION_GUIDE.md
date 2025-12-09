# QR Code API Integration Guide

## Backend Setup Instructions

### Step 1: Add QR Routes to API

Edit `routes/api.php` and add the following line after the other route includes:

```php
// At the top with other imports
use App\Http\Controllers\QRController;

// In the middleware group (around line 50-60)
Route::middleware('auth:sanctum')->group(function () {
    // ... existing routes ...
    
    // QR Code Routes
    Route::prefix('qr')->group(function () {
        Route::post('/generate', [QRController::class, 'generate'])
            ->middleware('throttle:10,1');
        
        Route::post('/validate', [QRController::class, 'validate'])
            ->middleware('throttle:5,1');
        
        Route::get('/tokens/{loanId}', [QRController::class, 'getTokens'])
            ->where('loanId', '[0-9]+');
        
        Route::post('/revoke', [QRController::class, 'revoke']);
        
        Route::post('/cleanup', [QRController::class, 'cleanupExpired'])
            ->middleware('role:admin,staff');
    });
});

// Public routes (outside middleware group)
Route::get('/qr/status/{token}', [QRController::class, 'getStatus'])
    ->middleware('throttle:30,1');
```

### Step 2: Run Migration

```bash
# Create migration
php artisan make:migration create_qr_tokens_table

# Copy the migration content from database/migrations/2024_12_09_create_qr_tokens_table.php

# Run migration
php artisan migrate
```

### Step 3: Create Scheduled Cleanup Task

Edit `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // ... existing schedules ...
    
    // Cleanup expired QR tokens every hour
    $schedule->call(function () {
        \App\Models\QRToken::expired()
            ->where('status', '!=', 'revoked')
            ->update(['status' => 'expired']);
    })->hourly();
}
```

### Step 4: Update Loan Model

Add relationship to `app/Models/Loan.php`:

```php
public function qrTokens()
{
    return $this->hasMany(QRToken::class);
}

public function activeQRToken()
{
    return $this->hasOne(QRToken::class)
        ->where('status', 'active')
        ->where('expires_at', '>', now());
}
```

### Step 5: Update User Model

Add relationship to `app/Models/User.php`:

```php
public function generatedQRTokens()
{
    return $this->hasMany(QRToken::class, 'created_by');
}

public function scannedQRTokens()
{
    return $this->hasMany(QRToken::class, 'scanned_by');
}
```

### Step 6: Test the API

```bash
# Generate QR Token
curl -X POST http://localhost:8000/api/v1/qr/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "loan_id": 1,
    "duration_minutes": 15
  }'

# Validate QR Token
curl -X POST http://localhost:8000/api/v1/qr/validate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "qr_token": "QR_xxxxx",
    "guarantor_id": 2
  }'

# Get QR Status (public)
curl -X GET http://localhost:8000/api/v1/qr/status/QR_xxxxx
```

---

## API Endpoints Reference

### 1. Generate QR Token

**Endpoint:** `POST /api/v1/qr/generate`

**Authentication:** Required (Sanctum)

**Request:**
```json
{
  "loan_id": 1,
  "duration_minutes": 15
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "QR token generated successfully",
  "data": {
    "token": "QR_abc123def456_1702123456",
    "qr_data": {
      "loan_id": 1,
      "amount": 50000,
      "duration": 12,
      "applicant_id": 1,
      "applicant_name": "John Doe",
      "generated_at": "2024-12-09T12:34:56Z",
      "type": "loan_guarantor"
    },
    "expires_at": "2024-12-09T12:49:56Z",
    "expires_in_seconds": 900
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Cannot generate QR for loan with status: rejected"
}
```

---

### 2. Validate QR Token

**Endpoint:** `POST /api/v1/qr/validate`

**Authentication:** Required (Sanctum)

**Request:**
```json
{
  "qr_token": "QR_abc123def456_1702123456",
  "guarantor_id": 2
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "QR token validated successfully",
  "data": {
    "loan": {
      "id": 1,
      "amount": 50000,
      "duration": 12,
      "status": "active"
    },
    "guarantor": {
      "id": 1,
      "status": "verified",
      "verified_at": "2024-12-09T12:35:00Z"
    },
    "qr_token": {
      "status": "used",
      "scanned_at": "2024-12-09T12:35:00Z"
    }
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "QR token is expired",
  "status": "expired",
  "expires_at": "2024-12-09T12:49:56Z"
}
```

---

### 3. Get QR Tokens for Loan

**Endpoint:** `GET /api/v1/qr/tokens/{loanId}`

**Authentication:** Required (Sanctum)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "status": "used",
      "expires_at": "2024-12-09T12:49:56Z",
      "is_expired": false,
      "scanned_by": 2,
      "scanned_at": "2024-12-09T12:35:00Z",
      "created_at": "2024-12-09T12:34:56Z"
    },
    {
      "id": 2,
      "status": "active",
      "expires_at": "2024-12-09T13:00:00Z",
      "is_expired": false,
      "scanned_by": null,
      "scanned_at": null,
      "created_at": "2024-12-09T12:45:00Z"
    }
  ]
}
```

---

### 4. Get QR Status (Public)

**Endpoint:** `GET /api/v1/qr/status/{token}`

**Authentication:** Not required

**Response (200):**
```json
{
  "success": true,
  "data": {
    "valid": true,
    "token": "QR_abc123def456_1702123456",
    "loan_id": 1,
    "status": "active",
    "expires_at": "2024-12-09T12:49:56Z",
    "is_expired": false,
    "scanned_by": null,
    "scanned_at": null
  }
}
```

---

### 5. Revoke QR Token

**Endpoint:** `POST /api/v1/qr/revoke`

**Authentication:** Required (Sanctum)

**Request:**
```json
{
  "qr_token": "QR_abc123def456_1702123456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "QR token revoked successfully"
}
```

---

## Database Schema

### qr_tokens Table

```sql
CREATE TABLE qr_tokens (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  uuid CHAR(36) UNIQUE NOT NULL,
  loan_id BIGINT NOT NULL,
  created_by BIGINT NOT NULL,
  scanned_by BIGINT NULL,
  token VARCHAR(255) UNIQUE NOT NULL,
  qr_data LONGTEXT NULL,
  metadata JSON NULL,
  expires_at TIMESTAMP NOT NULL,
  scanned_at TIMESTAMP NULL,
  status ENUM('active', 'used', 'expired', 'revoked') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  
  FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (scanned_by) REFERENCES users(id) ON DELETE SET NULL,
  
  INDEX idx_token (token),
  INDEX idx_loan_id (loan_id),
  INDEX idx_status (status),
  INDEX idx_expires_at (expires_at),
  INDEX idx_loan_status (loan_id, status),
  INDEX idx_created_by_created_at (created_by, created_at)
);
```

---

## Security Considerations

### 1. Rate Limiting

- QR Generation: 10 requests/minute per user
- QR Validation: 5 requests/minute per user
- Status Check: 30 requests/minute (public)

### 2. Token Encryption

Consider encrypting tokens in database:

```php
// In QRToken model
protected $encrypted = ['token'];
```

### 3. Audit Logging

All QR operations are logged with:
- User ID
- IP Address
- User Agent
- Timestamp
- Action type

### 4. Expiration

- Default: 15 minutes
- Minimum: 5 minutes
- Maximum: 24 hours
- Auto-cleanup: Hourly scheduled task

---

## Troubleshooting

### Issue: Migration fails

**Solution:**
```bash
# Check if table exists
php artisan tinker
>>> DB::table('qr_tokens')->count()

# If exists, rollback and retry
php artisan migrate:rollback
php artisan migrate
```

### Issue: QR validation fails with "User is not a guarantor"

**Solution:**
- Ensure guarantor is added to loan before generating QR
- Check guarantor status is 'pending'
- Verify guarantor user_id matches

### Issue: Token expires too quickly

**Solution:**
- Check server time synchronization
- Verify `expires_at` is set correctly
- Check timezone configuration in `.env`

---

## Next Steps

1. ✅ Backend API implementation complete
2. ⏳ Update Flutter app to use backend QR endpoints
3. ⏳ Update website to display QR codes
4. ⏳ Implement real-time sync
5. ⏳ Deploy to production

---

**Last Updated:** December 9, 2024  
**Status:** Ready for Implementation
