# Loan Application API Documentation

## Overview

The Loan Application API enables users to apply for loans through a multi-stage process. Applications progress through various stages (personal info, employment, financial, guarantors, documents, review) and statuses (draft, submitted, under_review, approved, rejected, withdrawn, completed).

## Base URL
```
/api/loan-applications
```

## Authentication
All endpoints (except public routes) require authentication via Bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

## Endpoints

### 1. Get User's Loan Applications

**Endpoint:** `GET /my-applications`

**Authentication:** Required

**Description:** Retrieve all loan applications submitted by the authenticated user.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "loan_type_id": 1,
      "loan_type": {
        "id": 1,
        "name": "Personal Loan",
        "description": "Unsecured personal loan",
        "interest_rate": "12.5"
      },
      "requested_amount": 10000.00,
      "currency": "USD",
      "requested_tenure": 12,
      "loan_purpose": "Business expansion",
      "employment_status": "employed",
      "employer_name": "Tech Corp",
      "job_title": "Software Engineer",
      "employment_start_date": "2020-01-15",
      "monthly_salary": 5000.00,
      "monthly_expenses": 2000.00,
      "existing_loans": 1,
      "existing_loan_balance": 3000.00,
      "savings_balance": 5000.00,
      "business_revenue": null,
      "status": "submitted",
      "stage": "employment",
      "submitted_at": "2024-01-15T10:30:00Z",
      "reviewed_at": null,
      "approved_at": null,
      "rejection_reason": null,
      "notes": null,
      "created_at": "2024-01-15T09:00:00Z",
      "updated_at": "2024-01-15T10:30:00Z"
    }
  ]
}
```

---

### 2. Get Available Loan Types

**Endpoint:** `GET /available-types`

**Authentication:** Required

**Description:** Retrieve all active loan types available to the user and their eligibility status for each.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Personal Loan",
      "description": "Unsecured personal loan",
      "minimum_amount": 1000.00,
      "maximum_amount": 50000.00,
      "interest_rate": "12.5",
      "is_eligible": true
    },
    {
      "id": 2,
      "name": "Business Loan",
      "description": "Loan for business purposes",
      "minimum_amount": 5000.00,
      "maximum_amount": 100000.00,
      "interest_rate": "10.0",
      "is_eligible": false
    }
  ]
}
```

---

### 3. Create Loan Application

**Endpoint:** `POST /create`

**Authentication:** Required

**Description:** Create a new loan application. User must have completed KYC verification.

**Request Body:**
```json
{
  "loan_type_id": 1,
  "requested_amount": 10000,
  "requested_tenure": 12,
  "loan_purpose": "Business expansion",
  "employment_status": "employed",
  "employer_name": "Tech Corp",
  "job_title": "Software Engineer",
  "employment_start_date": "2020-01-15",
  "monthly_salary": 5000,
  "monthly_expenses": 2000,
  "existing_loans": 1,
  "existing_loan_balance": 3000,
  "savings_balance": 5000,
  "business_revenue": null
}
```

**Validation Rules:**
- `loan_type_id`: Required, must exist in loan_types table
- `requested_amount`: Required, numeric, minimum 1
- `requested_tenure`: Required, integer, 1-60 months
- `loan_purpose`: Required, string, max 500 characters
- `employment_status`: Required, one of: employed, self_employed, unemployed
- `employer_name`: Optional, string, max 255 characters
- `job_title`: Optional, string, max 255 characters
- `employment_start_date`: Optional, date, cannot be in future
- `monthly_salary`: Optional, numeric, minimum 0
- `monthly_expenses`: Required, numeric, minimum 0
- `existing_loans`: Required, integer, minimum 0
- `existing_loan_balance`: Required, numeric, minimum 0
- `savings_balance`: Required, numeric, minimum 0
- `business_revenue`: Optional, numeric, minimum 0

**Response (Success - 201):**
```json
{
  "success": true,
  "message": "Loan application created successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "loan_type_id": 1,
    "requested_amount": 10000.00,
    "currency": "USD",
    "requested_tenure": 12,
    "loan_purpose": "Business expansion",
    "status": "draft",
    "stage": "personal_info",
    "created_at": "2024-01-15T09:00:00Z",
    "updated_at": "2024-01-15T09:00:00Z"
  }
}
```

**Error Responses:**
- `422` - Validation failed (missing/invalid required fields)
- `422` - KYC verification not completed
- `500` - Server error

---

### 4. Get Loan Application Details

**Endpoint:** `GET /{id}`

**Authentication:** Required

**Description:** Retrieve details of a specific loan application. User can view their own applications, admins can view any application.

**Parameters:**
- `id`: Application ID (path parameter)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "loan_type_id": 1,
    "loan_type": {
      "id": 1,
      "name": "Personal Loan",
      "description": "Unsecured personal loan",
      "interest_rate": "12.5"
    },
    "requested_amount": 10000.00,
    "currency": "USD",
    "requested_tenure": 12,
    "loan_purpose": "Business expansion",
    "employment_status": "employed",
    "employer_name": "Tech Corp",
    "job_title": "Software Engineer",
    "employment_start_date": "2020-01-15",
    "monthly_salary": 5000.00,
    "monthly_expenses": 2000.00,
    "existing_loans": 1,
    "existing_loan_balance": 3000.00,
    "savings_balance": 5000.00,
    "business_revenue": null,
    "status": "submitted",
    "stage": "employment",
    "submitted_at": "2024-01-15T10:30:00Z",
    "reviewed_at": null,
    "approved_at": null,
    "rejection_reason": null,
    "notes": null,
    "created_at": "2024-01-15T09:00:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

**Error Responses:**
- `403` - Unauthorized (not owner and not admin)
- `404` - Application not found

---

### 5. Update Loan Application

**Endpoint:** `PUT /{id}`

**Authentication:** Required

**Description:** Update a loan application. Only draft applications can be updated.

**Parameters:**
- `id`: Application ID (path parameter)

**Request Body:** (same as Create, but all fields optional)
```json
{
  "requested_amount": 12000,
  "loan_purpose": "Updated purpose",
  "monthly_salary": 5500
}
```

**Response:**
```json
{
  "success": true,
  "message": "Loan application updated successfully",
  "data": { ... }
}
```

**Error Responses:**
- `403` - Unauthorized
- `404` - Application not found
- `422` - Cannot update submitted applications
- `422` - Validation failed

---

### 6. Submit Loan Application

**Endpoint:** `POST /{id}/submit`

**Authentication:** Required

**Description:** Submit a draft application for review. Application must meet eligibility requirements.

**Parameters:**
- `id`: Application ID (path parameter)

**Eligibility Checks:**
- User must have KYC verification
- If loan type requires minimum salary, user must meet it
- Debt-to-income ratio must not exceed 50%

**Response:**
```json
{
  "success": true,
  "message": "Application submitted successfully",
  "data": {
    "id": 1,
    "status": "submitted",
    "submitted_at": "2024-01-15T10:30:00Z",
    ...
  }
}
```

**Error Responses:**
- `403` - Unauthorized
- `404` - Application not found
- `422` - Only draft applications can be submitted
- `422` - Application does not meet eligibility requirements

---

### 7. Move Application to Next Stage (Admin Only)

**Endpoint:** `POST /{id}/next-stage`

**Authentication:** Required (Admin only)

**Description:** Move application to the next processing stage. Stages progress: personal_info → employment → financial → guarantors → documents → review

**Parameters:**
- `id`: Application ID (path parameter)

**Response:**
```json
{
  "success": true,
  "message": "Application moved to next stage",
  "data": {
    "id": 1,
    "stage": "employment",
    ...
  }
}
```

**Error Responses:**
- `403` - Unauthorized (not admin)
- `404` - Application not found

---

### 8. Get Applications for Review (Admin Only)

**Endpoint:** `GET /admin/review`

**Authentication:** Required (Admin only)

**Description:** Retrieve paginated list of applications for admin review with optional filtering.

**Query Parameters:**
- `status`: Filter by status (draft, submitted, under_review, approved, rejected, withdrawn, completed)
- `stage`: Filter by stage (personal_info, employment, financial, guarantors, documents, review)
- `page`: Page number for pagination (default: 1)

**Example:**
```
GET /admin/review?status=submitted&stage=employment&page=1
```

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      { ... },
      { ... }
    ],
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8,
    "from": 1,
    "to": 20
  }
}
```

**Error Responses:**
- `403` - Unauthorized (not admin)

---

## Application Statuses

| Status | Description |
|--------|-------------|
| `draft` | Initial state, user can edit |
| `submitted` | User submitted for review |
| `under_review` | Admin is reviewing |
| `approved` | Loan application approved |
| `rejected` | Loan application rejected |
| `withdrawn` | User withdrew application |
| `completed` | Loan created and funded |

## Application Stages

| Stage | Description |
|-------|-------------|
| `personal_info` | Personal information and loan purpose |
| `employment` | Employment and income details |
| `financial` | Financial status and existing obligations |
| `guarantors` | Guarantor information (if required) |
| `documents` | Required supporting documents |
| `review` | Final review and approval |

## Error Responses

All error responses follow this format:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

## Rate Limiting

API requests may be rate limited. Check response headers for rate limit information:
- `X-RateLimit-Limit`: Total requests allowed
- `X-RateLimit-Remaining`: Requests remaining
- `X-RateLimit-Reset`: Time when limit resets

## Examples

### Example 1: Complete Application Flow

```bash
# 1. Get available loan types
curl -X GET "https://api.coopvest.com/api/loan-applications/available-types" \
  -H "Authorization: Bearer {token}"

# 2. Create application
curl -X POST "https://api.coopvest.com/api/loan-applications/create" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "loan_type_id": 1,
    "requested_amount": 10000,
    "requested_tenure": 12,
    "loan_purpose": "Business expansion",
    "employment_status": "employed",
    "employer_name": "Tech Corp",
    "job_title": "Software Engineer",
    "employment_start_date": "2020-01-15",
    "monthly_salary": 5000,
    "monthly_expenses": 2000,
    "existing_loans": 1,
    "existing_loan_balance": 3000,
    "savings_balance": 5000
  }'

# 3. Submit application
curl -X POST "https://api.coopvest.com/api/loan-applications/1/submit" \
  -H "Authorization: Bearer {token}"

# 4. Check application status
curl -X GET "https://api.coopvest.com/api/loan-applications/1" \
  -H "Authorization: Bearer {token}"
```

## Contact & Support

For API support, contact: api-support@coopvest.com
