# Loan Application API - Quick Reference Guide

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+
- Laravel 10+
- Authenticated user with KYC verification

### Base URL
```
/api/loan-applications
```

### Authentication
All requests require Bearer token:
```
Authorization: Bearer {your_token}
```

---

## 📋 Common Tasks

### 1. Get My Loan Applications
```bash
curl -X GET "https://api.coopvest.com/api/loan-applications/my-applications" \
  -H "Authorization: Bearer {token}"
```

**Response:** List of all user's applications with statuses

---

### 2. Check Available Loan Types
```bash
curl -X GET "https://api.coopvest.com/api/loan-applications/available-types" \
  -H "Authorization: Bearer {token}"
```

**Response:** Active loan types with eligibility status

---

### 3. Create a Loan Application
```bash
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
```

**Response:** Created application with ID (status: draft)

---

### 4. View Application Details
```bash
curl -X GET "https://api.coopvest.com/api/loan-applications/1" \
  -H "Authorization: Bearer {token}"
```

**Response:** Complete application details including loan type info

---

### 5. Update Draft Application
```bash
curl -X PUT "https://api.coopvest.com/api/loan-applications/1" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "requested_amount": 12000,
    "loan_purpose": "Updated purpose"
  }'
```

**Note:** Only works for draft applications (status: draft)

---

### 6. Submit Application for Review
```bash
curl -X POST "https://api.coopvest.com/api/loan-applications/1/submit" \
  -H "Authorization: Bearer {token}"
```

**Eligibility Checks:**
- ✓ KYC verification required
- ✓ Meets minimum salary requirement
- ✓ Debt-to-income ratio ≤ 50%

**Response:** Application moved to "submitted" status

---

## 🔐 Admin Endpoints

### Get Applications for Review (Admin Only)
```bash
curl -X GET "https://api.coopvest.com/api/loan-applications/admin/review?status=submitted&stage=employment" \
  -H "Authorization: Bearer {admin_token}"
```

**Query Parameters:**
- `status`: Filter by status (draft, submitted, under_review, etc.)
- `stage`: Filter by stage (personal_info, employment, etc.)
- `page`: Page number for pagination

---

### Move Application to Next Stage (Admin Only)
```bash
curl -X POST "https://api.coopvest.com/api/loan-applications/1/next-stage" \
  -H "Authorization: Bearer {admin_token}"
```

**Stages:** personal_info → employment → financial → guarantors → documents → review

---

## 📊 Application Statuses

| Status | Meaning | User Can Edit |
|--------|---------|---------------|
| `draft` | Initial state | ✅ Yes |
| `submitted` | Waiting for review | ❌ No |
| `under_review` | Being reviewed | ❌ No |
| `approved` | Loan application approved | ❌ No |
| `rejected` | Application rejected | ❌ No |
| `withdrawn` | User withdrew application | ❌ No |
| `completed` | Loan created and funded | ❌ No |

---

## 🔄 Application Workflow

### For Users
1. **Check eligibility** → GET `/available-types`
2. **Create application** → POST `/create`
3. **Make updates** → PUT `/{id}` (only if draft)
4. **Submit** → POST `/{id}/submit`
5. **Monitor status** → GET `/{id}`

### For Admins
1. **Review pending** → GET `/admin/review?status=submitted`
2. **View details** → GET `/{id}`
3. **Progress through stages** → POST `/{id}/next-stage`
4. **Make decision** → (approval endpoint in Phase 2)

---

## ⚠️ Common Errors

### 422 - Validation Failed
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "loan_type_id": ["Loan type is required"],
    "monthly_salary": ["Salary must be a number"]
  }
}
```

**Solution:** Check required fields and data types

---

### 422 - KYC Not Verified
```json
{
  "success": false,
  "message": "KYC verification is required before applying for a loan"
}
```

**Solution:** Complete KYC verification first

---

### 403 - Unauthorized
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

**Solution:** 
- Ensure you have valid token
- Check if you're accessing your own data
- Admin access required for admin endpoints

---

### 404 - Not Found
```json
{
  "success": false,
  "message": "Application not found"
}
```

**Solution:** Verify application ID exists

---

### 422 - Cannot Update Submitted
```json
{
  "success": false,
  "message": "Cannot update applications that have been submitted"
}
```

**Solution:** Only draft applications can be updated

---

## 📱 Required Fields for Application

### Mandatory Fields
- `loan_type_id` *
- `requested_amount` *
- `requested_tenure` *
- `loan_purpose` *
- `employment_status` *
- `monthly_expenses` *
- `existing_loans` *
- `existing_loan_balance` *
- `savings_balance` *

### Conditional Fields (depends on employment_status)
- If `employed`:
  - `employer_name` *
  - `job_title` *
  - `employment_start_date` *
  - `monthly_salary` *

- If `self_employed`:
  - `job_title` *
  - `employment_start_date` *
  - `monthly_salary` or `business_revenue` *

- If `unemployed`:
  - `savings_balance` must be substantial

---

## 💡 Tips & Best Practices

### Before Creating Application
```bash
# 1. Verify you're KYC verified
GET /api/auth/me

# 2. Check available loan types
GET /api/loan-applications/available-types

# 3. Review your current financial status
GET /api/member/profile
```

### Error Prevention
```bash
# ✅ Always provide all required fields
# ✅ Use correct data types (numbers, dates, enums)
# ✅ Ensure employment_start_date is not in future
# ✅ Keep tenure between 1-60 months
# ✅ Don't update after submission
```

### Data Tips
```
Employment Status Options:
- "employed" → Full-time/part-time employment
- "self_employed" → Business owner/freelancer
- "unemployed" → Currently not employed

Currency: Always "USD" for now

Monthly Salary/Revenue: Must be realistic
Debt-to-Income: system checks this automatically
```

---

## 🔗 Related Endpoints

### User Authentication
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Current user info

### KYC Verification
- `POST /api/kyc/submit` - Submit KYC
- `GET /api/kyc/status` - Check KYC status

### Loan Information
- `GET /api/member/loans` - View existing loans
- `GET /api/member/dashboard` - Financial overview

---

## 📞 Support

For API issues:
- Check error messages carefully
- Verify all required fields are provided
- Ensure proper data types
- Contact: api-support@coopvest.com

---

## 🔄 API Versioning

Current Version: `v1`
Endpoints: `/api/loan-applications/...`

**Future Changes:**
- Backward compatible updates will use same paths
- Breaking changes will use `/api/v2/...`

---

Last Updated: January 2024
