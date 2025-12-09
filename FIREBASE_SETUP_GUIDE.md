# Firebase Admin SDK Setup Guide

## Overview

This guide provides comprehensive instructions for setting up and using Firebase Admin SDK with the Coopvest Africa backend. The Firebase integration enables secure user authentication, synchronization, and management.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Middleware](#middleware)
4. [User Synchronization](#user-synchronization)
5. [API Endpoints](#api-endpoints)
6. [Database Migration](#database-migration)
7. [Usage Examples](#usage-examples)
8. [Troubleshooting](#troubleshooting)

## Installation

### Prerequisites

- PHP 8.2 or higher
- Laravel 11.0 or higher
- Composer
- Firebase project with Admin SDK credentials

### Step 1: Install Firebase Admin SDK

The Firebase Admin SDK has already been added to `composer.json`. To install dependencies:

```bash
composer install
```

Or if you need to add it manually:

```bash
composer require kreait/firebase-php:^7.0
```

### Step 2: Obtain Firebase Credentials

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project
3. Navigate to **Project Settings** → **Service Accounts**
4. Click **Generate New Private Key**
5. Save the JSON file securely

## Configuration

### Step 1: Set Up Environment Variables

Copy the Firebase credentials JSON file to your project:

```bash
mkdir -p storage/app
cp /path/to/firebase-credentials.json storage/app/firebase-credentials.json
```

### Step 2: Update .env File

Add the following variables to your `.env` file:

```env
# Firebase Configuration
FIREBASE_CREDENTIALS_PATH=storage/app/firebase-credentials.json
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_API_KEY=your-api-key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_DATABASE_URL=https://your-project.firebaseio.com
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=your-sender-id
FIREBASE_APP_ID=your-app-id
FIREBASE_MEASUREMENT_ID=your-measurement-id

# Firebase Admin SDK Settings
FIREBASE_ADMIN_SDK_ENABLED=true
FIREBASE_VERIFY_ID_TOKEN=true
FIREBASE_CACHE_TOKENS=true
FIREBASE_TOKEN_CACHE_TTL=3600

# Firebase User Sync Settings
FIREBASE_USER_SYNC_ENABLED=true
FIREBASE_AUTO_CREATE_USERS=true
FIREBASE_SYNC_CUSTOM_CLAIMS=true
FIREBASE_SYNC_METADATA=true

# Firebase Middleware Settings
FIREBASE_AUTH_MIDDLEWARE_ENABLED=true
FIREBASE_AUTH_THROW_EXCEPTIONS=true
FIREBASE_SYNC_MIDDLEWARE_ENABLED=true
FIREBASE_QUEUE_SYNC=false
```

### Step 3: Verify Configuration

The configuration file is located at `config/firebase.php`. Review and adjust settings as needed.

## Middleware

### FirebaseAuth Middleware

**Purpose**: Verifies Firebase ID tokens and extracts user information.

**Location**: `app/Http/Middleware/FirebaseAuth.php`

**Features**:
- Validates Bearer tokens from Authorization header
- Verifies token signature and expiration
- Extracts Firebase UID and stores in request
- Returns 401 for invalid/expired tokens

**Usage**:
```php
Route::middleware('firebase.auth')->group(function () {
    // Protected routes
});
```

### FirebaseSync Middleware

**Purpose**: Automatically synchronizes Firebase user data with the database.

**Location**: `app/Http/Middleware/FirebaseSync.php`

**Features**:
- Syncs user data from Firebase to database
- Auto-creates users if enabled
- Syncs custom claims and metadata
- Non-blocking (doesn't fail requests on sync errors)

**Usage**:
```php
Route::middleware('firebase.sync')->group(function () {
    // Routes with automatic user sync
});
```

## User Synchronization

### Automatic Synchronization

When `FIREBASE_USER_SYNC_ENABLED=true`, the system automatically:

1. **Creates new users** from Firebase (if `FIREBASE_AUTO_CREATE_USERS=true`)
2. **Updates existing users** with Firebase data
3. **Syncs custom claims** (if `FIREBASE_SYNC_CUSTOM_CLAIMS=true`)
4. **Syncs metadata** (if `FIREBASE_SYNC_METADATA=true`)

### Database Fields

The following fields are added to the `users` table:

| Field | Type | Description |
|-------|------|-------------|
| `firebase_uid` | string | Unique Firebase user ID |
| `name` | string | User's display name |
| `phone_number` | string | User's phone number |
| `firebase_email_verified` | boolean | Email verification status |
| `firebase_disabled` | boolean | Account disabled status |
| `firebase_metadata` | json | Firebase metadata (timestamps, etc.) |
| `firebase_custom_claims` | json | Custom claims from Firebase |

### Running Migrations

```bash
php artisan migrate
```

This will create the necessary database columns for Firebase integration.

## API Endpoints

### 1. Sync User

**Endpoint**: `POST /api/firebase/sync`

**Description**: Synchronize a user with Firebase

**Request**:
```json
{
    "firebase_uid": "user-firebase-uid"
}
```

**Response** (Success):
```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "user_id": 1,
        "firebase_uid": "user-firebase-uid",
        "email": "user@example.com",
        "name": "John Doe",
        "action": "created"
    }
}
```

**Response** (Error):
```json
{
    "success": false,
    "message": "Firebase UID is required",
    "error": "MISSING_FIREBASE_UID"
}
```

### 2. Get Sync Status

**Endpoint**: `GET /api/firebase/sync/status`

**Description**: Check if a user is synced with Firebase

**Query Parameters**:
- `firebase_uid` (required): Firebase user ID

**Response** (Synced):
```json
{
    "success": true,
    "message": "User sync status retrieved",
    "data": {
        "synced": true,
        "user_id": 1,
        "firebase_uid": "user-firebase-uid",
        "email": "user@example.com",
        "name": "John Doe",
        "email_verified": true,
        "disabled": false,
        "synced_at": "2024-12-09T10:30:00Z"
    }
}
```

**Response** (Not Synced):
```json
{
    "success": true,
    "message": "User not synced",
    "data": {
        "synced": false,
        "firebase_uid": "user-firebase-uid"
    }
}
```

### 3. Bulk Sync Users

**Endpoint**: `POST /api/firebase/sync/bulk`

**Description**: Synchronize multiple users at once

**Request**:
```json
{
    "firebase_uids": [
        "uid-1",
        "uid-2",
        "uid-3"
    ]
}
```

**Response**:
```json
{
    "success": true,
    "message": "Bulk sync completed",
    "data": {
        "total": 3,
        "synced": 2,
        "failed": 1,
        "errors": [
            {
                "firebase_uid": "uid-3",
                "error": "User not found in Firebase"
            }
        ]
    }
}
```

## Database Migration

### Create Migration

A migration file has been created at:
```
database/migrations/2024_12_09_000001_add_firebase_fields_to_users_table.php
```

### Run Migration

```bash
php artisan migrate
```

### Rollback Migration

```bash
php artisan migrate:rollback
```

## Usage Examples

### Example 1: Protect Routes with Firebase Auth

```php
// routes/api.php
Route::middleware('firebase.auth')->group(function () {
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
});
```

### Example 2: Auto-Sync User Data

```php
// routes/api.php
Route::middleware(['firebase.auth', 'firebase.sync'])->group(function () {
    Route::get('/user/dashboard', [UserController::class, 'dashboard']);
});
```

### Example 3: Manual Sync in Controller

```php
// app/Http/Controllers/UserController.php
use App\Http\Controllers\UserSyncController;

class UserController extends Controller
{
    public function syncUser(Request $request)
    {
        $syncController = new UserSyncController();
        return $syncController->sync($request);
    }
}
```

### Example 4: Get Firebase UID from Request

```php
// In a controller method
public function getUser(Request $request)
{
    $firebaseUid = $request->attributes->get('firebase_uid');
    $user = User::where('firebase_uid', $firebaseUid)->first();
    
    return response()->json($user);
}
```

## Troubleshooting

### Issue: "Firebase credentials file not found"

**Solution**:
1. Verify the credentials file exists at `storage/app/firebase-credentials.json`
2. Check `FIREBASE_CREDENTIALS_PATH` in `.env`
3. Ensure file permissions allow reading

### Issue: "Invalid or expired token"

**Solution**:
1. Verify the token is a valid Firebase ID token
2. Check token expiration (tokens expire after 1 hour)
3. Ensure the Authorization header format is correct: `Bearer <token>`

### Issue: "User not found in Firebase"

**Solution**:
1. Verify the Firebase UID is correct
2. Check that the user exists in Firebase Console
3. Ensure Firebase Admin SDK has proper permissions

### Issue: "Middleware not working"

**Solution**:
1. Verify middleware is registered in `app/Http/Kernel.php`
2. Check that routes are using the correct middleware alias
3. Review logs for detailed error messages

### Enable Debug Logging

Add to `.env`:
```env
LOG_LEVEL=debug
```

Check logs in `storage/logs/laravel.log`

## Security Considerations

1. **Credentials**: Never commit `firebase-credentials.json` to version control
2. **Token Validation**: Always verify tokens before processing requests
3. **HTTPS**: Use HTTPS in production for all API endpoints
4. **Rate Limiting**: Implement rate limiting on sync endpoints
5. **Permissions**: Restrict Firebase Admin SDK permissions to minimum required

## Performance Optimization

1. **Token Caching**: Enable `FIREBASE_CACHE_TOKENS=true` to cache token verification
2. **Async Sync**: Set `FIREBASE_QUEUE_SYNC=true` to queue sync operations
3. **Batch Operations**: Use bulk sync endpoint for multiple users
4. **Database Indexes**: Ensure indexes on `firebase_uid` and `email` columns

## Support

For issues or questions:
1. Check the [Firebase Documentation](https://firebase.google.com/docs)
2. Review [Kreait Firebase PHP Documentation](https://github.com/kreait/firebase-php)
3. Check application logs in `storage/logs/`

## Version History

- **v1.0.0** (2024-12-09): Initial Firebase Admin SDK integration
  - Firebase authentication middleware
  - User synchronization middleware
  - User sync endpoints (single, status, bulk)
  - Database migration for Firebase fields
