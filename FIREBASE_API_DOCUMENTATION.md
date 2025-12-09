# Firebase User Sync API Documentation

## Base URL

```
https://api.coopvestafrica.com/api/firebase
```

## Authentication

All Firebase endpoints require a valid Firebase ID token in the Authorization header:

```
Authorization: Bearer <firebase_id_token>
```

## Endpoints

### 1. Sync User

Synchronize a user with Firebase and create/update in the database.

**Endpoint**: `POST /sync`

**Method**: POST

**Headers**:
```
Authorization: Bearer <firebase_id_token>
Content-Type: application/json
```

**Request Body**:
```json
{
    "firebase_uid": "string (required)"
}
```

**Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| firebase_uid | string | Yes | The Firebase user ID to sync |

**Success Response** (201):
```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "user_id": 1,
        "firebase_uid": "abc123def456",
        "email": "user@example.com",
        "name": "John Doe",
        "action": "created"
    }
}
```

**Error Responses**:

400 - Missing Firebase UID:
```json
{
    "success": false,
    "message": "Firebase UID is required",
    "error": "MISSING_FIREBASE_UID"
}
```

401 - Unauthorized:
```json
{
    "success": false,
    "message": "Invalid or expired token",
    "error": "INVALID_TOKEN"
}
```

404 - User not found:
```json
{
    "success": false,
    "message": "User not found and auto-creation is disabled",
    "error": "USER_NOT_FOUND"
}
```

500 - Server error:
```json
{
    "success": false,
    "message": "Error syncing user",
    "error": "SYNC_ERROR",
    "details": "Error message details"
}
```

**Example Request**:
```bash
curl -X POST https://api.coopvestafrica.com/api/firebase/sync \
  -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6IjEyMyJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "firebase_uid": "abc123def456"
  }'
```

**Example Response**:
```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "user_id": 42,
        "firebase_uid": "abc123def456",
        "email": "john.doe@example.com",
        "name": "John Doe",
        "action": "created"
    }
}
```

---

### 2. Get Sync Status

Check if a user is synchronized with Firebase.

**Endpoint**: `GET /sync/status`

**Method**: GET

**Headers**:
```
Authorization: Bearer <firebase_id_token>
Content-Type: application/json
```

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| firebase_uid | string | Yes | The Firebase user ID to check |

**Success Response** (200 - User Synced):
```json
{
    "success": true,
    "message": "User sync status retrieved",
    "data": {
        "synced": true,
        "user_id": 42,
        "firebase_uid": "abc123def456",
        "email": "john.doe@example.com",
        "name": "John Doe",
        "email_verified": true,
        "disabled": false,
        "synced_at": "2024-12-09T10:30:00Z"
    }
}
```

**Success Response** (200 - User Not Synced):
```json
{
    "success": true,
    "message": "User not synced",
    "data": {
        "synced": false,
        "firebase_uid": "abc123def456"
    }
}
```

**Error Responses**:

400 - Missing Firebase UID:
```json
{
    "success": false,
    "message": "Firebase UID is required",
    "error": "MISSING_FIREBASE_UID"
}
```

401 - Unauthorized:
```json
{
    "success": false,
    "message": "Invalid or expired token",
    "error": "INVALID_TOKEN"
}
```

**Example Request**:
```bash
curl -X GET "https://api.coopvestafrica.com/api/firebase/sync/status?firebase_uid=abc123def456" \
  -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6IjEyMyJ9..."
```

**Example Response**:
```json
{
    "success": true,
    "message": "User sync status retrieved",
    "data": {
        "synced": true,
        "user_id": 42,
        "firebase_uid": "abc123def456",
        "email": "john.doe@example.com",
        "name": "John Doe",
        "email_verified": true,
        "disabled": false,
        "synced_at": "2024-12-09T10:30:00Z"
    }
}
```

---

### 3. Bulk Sync Users

Synchronize multiple users at once.

**Endpoint**: `POST /sync/bulk`

**Method**: POST

**Headers**:
```
Authorization: Bearer <firebase_id_token>
Content-Type: application/json
```

**Request Body**:
```json
{
    "firebase_uids": ["string", "string", ...]
}
```

**Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| firebase_uids | array | Yes | Array of Firebase user IDs to sync |

**Success Response** (200):
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

**Error Responses**:

400 - Missing Firebase UIDs:
```json
{
    "success": false,
    "message": "Firebase UIDs array is required",
    "error": "MISSING_FIREBASE_UIDS"
}
```

401 - Unauthorized:
```json
{
    "success": false,
    "message": "Invalid or expired token",
    "error": "INVALID_TOKEN"
}
```

500 - Server error:
```json
{
    "success": false,
    "message": "Error in bulk sync",
    "error": "BULK_SYNC_ERROR",
    "details": "Error message details"
}
```

**Example Request**:
```bash
curl -X POST https://api.coopvestafrica.com/api/firebase/sync/bulk \
  -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6IjEyMyJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "firebase_uids": [
        "uid-1",
        "uid-2",
        "uid-3"
    ]
  }'
```

**Example Response**:
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

---

## Error Codes

| Error Code | HTTP Status | Description |
|-----------|-------------|-------------|
| MISSING_AUTH_HEADER | 401 | Authorization header is missing |
| INVALID_AUTH_FORMAT | 401 | Authorization header format is invalid |
| INVALID_TOKEN | 401 | Token is invalid or expired |
| AUTH_ERROR | 500 | Firebase authentication service error |
| MIDDLEWARE_ERROR | 500 | Middleware processing error |
| MISSING_FIREBASE_UID | 400 | Firebase UID parameter is missing |
| FIREBASE_NOT_INITIALIZED | 500 | Firebase service not initialized |
| USER_NOT_FOUND | 404 | User not found in database |
| SYNC_ERROR | 500 | Error during user synchronization |
| MISSING_FIREBASE_UIDS | 400 | Firebase UIDs array is missing |
| BULK_SYNC_ERROR | 500 | Error during bulk synchronization |
| STATUS_ERROR | 500 | Error retrieving sync status |

---

## Rate Limiting

The API implements rate limiting to prevent abuse:

- **Sync endpoint**: 100 requests per hour per user
- **Status endpoint**: 1000 requests per hour per user
- **Bulk sync endpoint**: 50 requests per hour per user

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 99
X-RateLimit-Reset: 1702200600
```

---

## Response Format

All responses follow a consistent JSON format:

**Success Response**:
```json
{
    "success": true,
    "message": "Description of the operation",
    "data": {
        // Response data
    }
}
```

**Error Response**:
```json
{
    "success": false,
    "message": "Error description",
    "error": "ERROR_CODE",
    "details": "Additional error details (optional)"
}
```

---

## Data Models

### User Object

```json
{
    "user_id": 42,
    "firebase_uid": "abc123def456",
    "email": "john.doe@example.com",
    "name": "John Doe",
    "phone_number": "+1234567890",
    "email_verified": true,
    "disabled": false,
    "synced_at": "2024-12-09T10:30:00Z"
}
```

### Sync Status Object

```json
{
    "synced": true,
    "user_id": 42,
    "firebase_uid": "abc123def456",
    "email": "john.doe@example.com",
    "name": "John Doe",
    "email_verified": true,
    "disabled": false,
    "synced_at": "2024-12-09T10:30:00Z"
}
```

### Bulk Sync Result Object

```json
{
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
```

---

## Best Practices

1. **Token Management**:
   - Always include a valid Firebase ID token
   - Refresh tokens before expiration (1 hour)
   - Never expose tokens in logs or error messages

2. **Error Handling**:
   - Check the `success` field in responses
   - Handle specific error codes appropriately
   - Log errors for debugging

3. **Performance**:
   - Use bulk sync for multiple users
   - Implement caching for frequently accessed data
   - Monitor rate limits and adjust accordingly

4. **Security**:
   - Use HTTPS for all requests
   - Validate input data
   - Implement proper access controls

---

## Changelog

### Version 1.0.0 (2024-12-09)
- Initial release
- Sync user endpoint
- Get sync status endpoint
- Bulk sync endpoint
- Firebase authentication middleware
- User synchronization middleware

---

## Support

For API support and issues:
- Email: support@coopvestafrica.com
- Documentation: https://docs.coopvestafrica.com
- GitHub Issues: https://github.com/coopvestafrica-ops/coopvest_africa_backend/issues
