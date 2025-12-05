# CoopVest Africa - API Documentation

## 📚 Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [API Endpoints](#api-endpoints)
4. [Error Handling](#error-handling)
5. [Rate Limiting](#rate-limiting)
6. [Examples](#examples)

---

## 🌐 Overview

**Base URL**: `http://localhost:8000/api`  
**Production URL**: `https://api.coopvest.africa/api`

**API Version**: 1.0.0  
**Authentication**: Laravel Sanctum (Token-based)  
**Content-Type**: `application/json`

---

## 🔐 Authentication

### Register
**POST** `/auth/register`

Register a new user account.

**Request Body**:
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "+234800000000",
  "country": "Nigeria",
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!"
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "email": "john.doe@example.com",
      "name": "John Doe",
      "role": "member"
    },
    "token": "1|abc123...",
    "refreshToken": "1|abc123..."
  }
}
```

### Login
**POST** `/auth/login`

Authenticate and receive access token.

**Request Body**:
```json
{
  "email": "john.doe@example.com",
  "password": "SecurePassword123!"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "email": "john.doe@example.com",
      "name": "John Doe",
      "role": "member"
    },
    "token": "1|abc123...",
    "refreshToken": "1|abc123..."
  }
}
```

### Logout
**POST** `/auth/logout`

Revoke current access token.

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

### Get Current User
**GET** `/auth/user`

Get authenticated user details.

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "+234800000000",
    "country": "Nigeria",
    "role": "member",
    "kyc_status": "pending",
    "is_active": true,
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

## 📋 API Endpoints

### Member Endpoints

#### Get Dashboard
**GET** `/member/dashboard`

Get member dashboard overview.

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "savings_balance": 50000.00,
    "total_contributions": 100000.00,
    "active_loans": 1,
    "pending_loans": 0,
    "recent_transactions": [...]
  }
}
```

#### Get Savings
**GET** `/member/savings`

Get member savings information.

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "current_balance": 50000.00,
    "total_deposits": 75000.00,
    "total_withdrawals": 25000.00,
    "interest_earned": 2500.00,
    "savings_history": [...]
  }
}
```

#### Get Transactions
**GET** `/member/transactions`

Get member transaction history.

**Query Parameters**:
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)
- `type` (optional): Transaction type filter
- `from_date` (optional): Start date filter
- `to_date` (optional): End date filter

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "transactions": [...],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "per_page": 15,
      "total": 75
    }
  }
}
```

---

### Loan Endpoints

#### Get Loan Types
**GET** `/loan-types`

Get available loan types.

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Personal Loan",
      "description": "Quick personal loans for emergencies",
      "min_amount": 10000,
      "max_amount": 500000,
      "interest_rate": 5.5,
      "max_duration_months": 12,
      "requires_guarantor": true,
      "min_guarantors": 2
    }
  ]
}
```

#### Create Loan Application
**POST** `/loan-applications`

Create a new loan application.

**Request Body**:
```json
{
  "loan_type_id": 1,
  "amount": 100000,
  "duration_months": 6,
  "purpose": "Business expansion",
  "employment_status": "employed",
  "monthly_income": 150000,
  "guarantors": [
    {
      "first_name": "Jane",
      "last_name": "Smith",
      "email": "jane.smith@example.com",
      "phone": "+234800000001",
      "relationship": "friend"
    }
  ]
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Loan application created successfully",
  "data": {
    "id": 1,
    "loan_type": "Personal Loan",
    "amount": 100000,
    "duration_months": 6,
    "status": "draft",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### Get Loan Application
**GET** `/loan-applications/{id}`

Get specific loan application details.

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "loan_type": "Personal Loan",
    "amount": 100000,
    "duration_months": 6,
    "interest_rate": 5.5,
    "monthly_payment": 17500,
    "total_repayment": 105000,
    "status": "pending",
    "guarantors": [...],
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### Submit Loan Application
**POST** `/loan-applications/{id}/submit`

Submit loan application for review.

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Loan application submitted successfully",
  "data": {
    "id": 1,
    "status": "pending",
    "submitted_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

### Guarantor Endpoints

#### Add Guarantor
**POST** `/guarantors`

Add a guarantor to loan application.

**Request Body**:
```json
{
  "loan_application_id": 1,
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane.smith@example.com",
  "phone": "+234800000001",
  "relationship": "friend",
  "address": "123 Main St, Lagos"
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Guarantor added successfully",
  "data": {
    "id": 1,
    "name": "Jane Smith",
    "status": "pending",
    "verification_status": "unverified"
  }
}
```

#### Send Guarantor Invitation
**POST** `/guarantors/{id}/invite`

Send invitation email to guarantor.

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Invitation sent successfully"
}
```

#### Verify Guarantor
**POST** `/guarantors/{id}/verify`

Verify guarantor information.

**Request Body**:
```json
{
  "verification_code": "ABC123",
  "id_number": "12345678",
  "id_type": "national_id"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Guarantor verified successfully"
}
```

---

### KYC Endpoints

#### Submit KYC
**POST** `/kyc/submit`

Submit KYC verification documents.

**Request Body** (multipart/form-data):
```
id_type: national_id
id_number: 12345678
id_document: [file]
proof_of_address: [file]
selfie: [file]
```

**Response** (201 Created):
```json
{
  "success": true,
  "message": "KYC documents submitted successfully",
  "data": {
    "status": "pending",
    "submitted_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### Get KYC Status
**GET** `/kyc/status`

Get current KYC verification status.

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "status": "approved",
    "verified_at": "2024-01-01T00:00:00.000000Z",
    "documents": [
      {
        "type": "national_id",
        "status": "approved"
      }
    ]
  }
}
```

---

### Two-Factor Authentication

#### Enable 2FA
**POST** `/2fa/enable`

Enable two-factor authentication.

**Response** (200 OK):
```json
{
  "success": true,
  "message": "2FA enabled successfully",
  "data": {
    "qr_code": "data:image/png;base64,...",
    "secret": "ABCD1234EFGH5678",
    "backup_codes": ["123456", "789012", ...]
  }
}
```

#### Verify 2FA
**POST** `/2fa/verify`

Verify 2FA code.

**Request Body**:
```json
{
  "code": "123456"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "2FA verified successfully"
}
```

---

### Admin Endpoints

#### Get Dashboard Statistics
**GET** `/admin/dashboard`

Get admin dashboard statistics.

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "total_members": 1500,
    "active_loans": 250,
    "pending_applications": 45,
    "total_disbursed": 50000000,
    "total_repaid": 35000000,
    "default_rate": 2.5
  }
}
```

#### Approve Loan
**POST** `/admin/loan-applications/{id}/approve`

Approve a loan application.

**Request Body**:
```json
{
  "approved_amount": 100000,
  "approved_duration": 6,
  "notes": "Approved with standard terms"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Loan approved successfully"
}
```

#### Reject Loan
**POST** `/admin/loan-applications/{id}/reject`

Reject a loan application.

**Request Body**:
```json
{
  "reason": "Insufficient credit history",
  "notes": "Please reapply after 6 months"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Loan rejected successfully"
}
```

---

## ⚠️ Error Handling

### Error Response Format

All errors follow this format:

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": ["Error detail"]
  }
}
```

### HTTP Status Codes

- `200 OK`: Request successful
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid request data
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation failed
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error

### Common Error Examples

#### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

#### Authentication Error (401)
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

#### Authorization Error (403)
```json
{
  "success": false,
  "message": "Insufficient permissions"
}
```

---

## 🚦 Rate Limiting

- **Default**: 60 requests per minute per IP
- **Authenticated**: 100 requests per minute per user
- **Admin**: 200 requests per minute

Rate limit headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640000000
```

---

## 📝 Examples

### Complete Loan Application Flow

```bash
# 1. Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+234800000000",
    "country": "Nigeria",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!"
  }'

# 2. Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "SecurePass123!"
  }'

# 3. Get Loan Types
curl -X GET http://localhost:8000/api/loan-types \
  -H "Authorization: Bearer {token}"

# 4. Create Loan Application
curl -X POST http://localhost:8000/api/loan-applications \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "loan_type_id": 1,
    "amount": 100000,
    "duration_months": 6,
    "purpose": "Business expansion"
  }'

# 5. Add Guarantor
curl -X POST http://localhost:8000/api/guarantors \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "loan_application_id": 1,
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane@example.com",
    "phone": "+234800000001",
    "relationship": "friend"
  }'

# 6. Submit Application
curl -X POST http://localhost:8000/api/loan-applications/1/submit \
  -H "Authorization: Bearer {token}"
```

---

## 🔗 Additional Resources

- [Postman Collection](./postman_collection.json)
- [OpenAPI Specification](./openapi.yaml)
- [Integration Guide](./INTEGRATION_GUIDE.md)
- [Setup Guide](../coopvest_africa_website/SETUP_GUIDE.md)

---

**Last Updated**: December 2, 2024  
**API Version**: 1.0.0
