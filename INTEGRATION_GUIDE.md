# Coopvest Africa - Frontend & Backend Integration Guide

This guide explains how the React/Vite frontend integrates with the Laravel backend API.

## 🔗 API Integration Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    React/Vite Frontend                       │
│                  (TypeScript + Tailwind CSS)                 │
└────────────────────────┬────────────────────────────────────┘
                         │
                         │ HTTP/REST API
                         │ (Axios)
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                  Laravel 11 Backend API                      │
│              (REST API + Laravel Sanctum Auth)               │
└─────────────────────────────────────────────────────────────┘
                         │
                         │ Database
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    MySQL Database                            │
│                (Migrations + Models)                         │
└─────────────────────────────────────────────────────────────┘
```

## 🔐 Authentication Flow

### 1. User Registration

**Frontend Request:**
```typescript
POST /api/auth/register
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "country": "Kenya",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Backend Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "john@example.com",
      "name": "John Doe",
      "role": "member"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refreshToken": "eyJ0eXAiOiJSZWZyZXNoVG9rZW4i..."
  }
}
```

**Frontend Action:**
- Store token in localStorage
- Store refreshToken in localStorage
- Redirect to KYC verification page

### 2. User Login

**Frontend Request:**
```typescript
POST /api/auth/login
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Backend Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "john@example.com",
      "name": "John Doe",
      "role": "member"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refreshToken": "eyJ0eXAiOiJSZWZyZXNoVG9rZW4i..."
  }
}
```

**Frontend Action:**
- Store token and refreshToken
- Redirect to dashboard

### 3. Token Refresh

When token expires (401 response):
```typescript
POST /api/auth/refresh
```

**Backend Response:**
```json
{
  "success": true,
  "data": {
    "token": "new_token_here",
    "refreshToken": "new_refresh_token_here"
  }
}
```

**Frontend Action:**
- Update stored token
- Retry original request

## 📡 API Endpoints Reference

### Authentication Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | No | Register new user |
| POST | `/api/auth/login` | No | Login user |
| POST | `/api/auth/logout` | Yes | Logout user |
| GET | `/api/auth/me` | Yes | Get current user |
| POST | `/api/auth/refresh` | Yes | Refresh token |
| POST | `/api/auth/password-reset/request` | No | Request password reset |
| POST | `/api/auth/password-reset/confirm` | No | Confirm password reset |

### Member Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/member/profile` | Yes | Get member profile |
| PUT | `/api/member/profile` | Yes | Update member profile |
| GET | `/api/member/dashboard` | Yes | Get dashboard data |
| GET | `/api/member/transactions` | Yes | Get transaction history |
| GET | `/api/member/savings` | Yes | Get savings accounts |
| GET | `/api/member/loans` | Yes | Get member loans |

### KYC Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/kyc/submit` | Yes | Submit KYC documents |
| GET | `/api/kyc/status` | Yes | Get KYC status |

### 2FA Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/2fa/setup` | Yes | Setup 2FA |
| POST | `/api/2fa/confirm` | Yes | Confirm 2FA setup |
| POST | `/api/2fa/verify` | Yes | Verify 2FA code |
| POST | `/api/2fa/disable` | Yes | Disable 2FA |

### Loan Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/loans/apply` | Yes | Apply for loan |
| POST | `/api/loans/calculate` | Yes | Calculate loan details |
| GET | `/api/loans/{id}` | Yes | Get loan details |
| POST | `/api/loans/{id}/payment` | Yes | Make loan payment |
| GET | `/api/loans/admin/pending` | Yes (Admin) | Get pending loans |
| POST | `/api/loans/{id}/approve` | Yes (Admin) | Approve loan |
| POST | `/api/loans/{id}/reject` | Yes (Admin) | Reject loan |

### Admin Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/admin/dashboard` | Yes (Admin) | Get admin dashboard |
| GET | `/api/admin/members` | Yes (Admin) | Get all members |
| GET | `/api/admin/members/{id}` | Yes (Admin) | Get member details |
| GET | `/api/admin/kyc` | Yes (Admin) | Get pending KYC |
| POST | `/api/kyc/{id}/approve` | Yes (Admin) | Approve KYC |
| POST | `/api/kyc/{id}/reject` | Yes (Admin) | Reject KYC |

### Super Admin Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/super-admin/dashboard` | Yes (Super Admin) | Get super admin dashboard |
| GET | `/api/super-admin/settings` | Yes (Super Admin) | Get global settings |
| PUT | `/api/super-admin/settings` | Yes (Super Admin) | Update global settings |
| GET | `/api/super-admin/audit-logs` | Yes (Super Admin) | Get audit logs |
| GET | `/api/super-admin/admins` | Yes (Super Admin) | Get all admins |
| POST | `/api/super-admin/admins` | Yes (Super Admin) | Create new admin |
| PUT | `/api/super-admin/admins/{id}` | Yes (Super Admin) | Update admin |
| DELETE | `/api/super-admin/admins/{id}` | Yes (Super Admin) | Delete admin |

## 🛠️ Frontend API Service Usage

### Basic Setup

The API service is located at `client/src/services/api.ts` and provides a centralized interface for all backend calls.

```typescript
import apiService from '@/services/api';

// Login
const response = await apiService.login({
  email: 'user@example.com',
  password: 'password123'
});

if (response.success) {
  console.log('Logged in:', response.data?.user);
} else {
  console.error('Login failed:', response.message);
}
```

### Common Patterns

**Getting Data:**
```typescript
const response = await apiService.getMemberProfile();
if (response.success) {
  const profile = response.data;
  // Use profile data
}
```

**Posting Data:**
```typescript
const response = await apiService.applyForLoan({
  amount: 50000,
  duration_months: 12,
  purpose: 'Business expansion'
});

if (response.success) {
  console.log('Loan applied successfully');
} else {
  console.error('Error:', response.errors);
}
```

**File Upload:**
```typescript
const formData = new FormData();
formData.append('document_type', 'national_id');
formData.append('document_image', fileInput.files[0]);
formData.append('proof_of_address', fileInput.files[1]);

const response = await apiService.submitKYC(formData);
```

## 🔄 Data Flow Examples

### Example 1: Member Registration & KYC

```
1. User fills registration form
   ↓
2. Frontend POST /api/auth/register
   ↓
3. Backend creates user, returns token
   ↓
4. Frontend stores token, redirects to KYC page
   ↓
5. User uploads KYC documents
   ↓
6. Frontend POST /api/kyc/submit (with token)
   ↓
7. Backend stores documents, sets status to "pending"
   ↓
8. Admin reviews KYC
   ↓
9. Admin POST /api/kyc/{id}/approve
   ↓
10. Backend updates user kyc_status to "verified"
    ↓
11. Member can now apply for loans
```

### Example 2: Loan Application & Approval

```
1. Member fills loan application form
   ↓
2. Frontend POST /api/loans/apply (with token)
   ↓
3. Backend creates loan with status "pending"
   ↓
4. Admin sees pending loan in dashboard
   ↓
5. Admin reviews loan details
   ↓
6. Admin POST /api/loans/{id}/approve
   ↓
7. Backend updates loan status to "approved"
   ↓
8. Admin POST /api/loans/{id}/disburse
   ↓
9. Backend updates loan status to "active"
   ↓
10. Member receives loan amount
    ↓
11. Member can make payments via POST /api/loans/{id}/payment
```

## 🔒 Security Considerations

### CORS Configuration

The backend is configured to accept requests from:
- `http://localhost:5173` (development)
- `http://localhost:3000` (development)
- `https://yourdomain.com` (production)

Update in `.env`:
```env
FRONTEND_URL=https://yourdomain.com
```

### Token Storage

Tokens are stored in `localStorage`:
```typescript
localStorage.getItem('auth_token')
localStorage.getItem('refresh_token')
```

For enhanced security, consider using:
- HttpOnly cookies (requires backend changes)
- Secure storage libraries

### Request Headers

All authenticated requests include:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Error Handling

API responses follow this format:
```json
{
  "success": true/false,
  "message": "Optional message",
  "data": {},
  "errors": {
    "field_name": ["Error message"]
  }
}
```

## 🧪 Testing the Integration

### Using Postman

1. **Register User:**
   - POST to `http://localhost:8000/api/auth/register`
   - Body: `{ "first_name": "Test", ... }`

2. **Login:**
   - POST to `http://localhost:8000/api/auth/login`
   - Body: `{ "email": "test@example.com", "password": "..." }`
   - Copy token from response

3. **Get Profile:**
   - GET to `http://localhost:8000/api/member/profile`
   - Header: `Authorization: Bearer {token}`

### Using Frontend

```typescript
// In browser console
import apiService from '@/services/api';

// Test login
await apiService.login({
  email: 'test@example.com',
  password: 'password123'
});

// Test get profile
await apiService.getMemberProfile();
```

## 📝 Environment Configuration

### Frontend (.env)

```env
VITE_API_URL=http://localhost:8000/api
```

### Backend (.env)

```env
FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000
```

## 🚀 Deployment Integration

### Production URLs

**Frontend:**
```env
VITE_API_URL=https://api.yourdomain.com/api
```

**Backend:**
```env
FRONTEND_URL=https://yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,api.yourdomain.com
```

## 📞 Troubleshooting

### CORS Errors

**Error:** `Access to XMLHttpRequest blocked by CORS policy`

**Solution:**
1. Check backend CORS configuration
2. Verify frontend URL is in allowed origins
3. Ensure credentials are properly configured

### 401 Unauthorized

**Error:** `Unauthorized`

**Solution:**
1. Verify token is stored correctly
2. Check token hasn't expired
3. Ensure token is included in request headers
4. Try refreshing token

### API Not Responding

**Error:** `Cannot reach API`

**Solution:**
1. Verify backend is running
2. Check API URL in environment variables
3. Verify network connectivity
4. Check firewall rules

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://react.dev)
- [Axios Documentation](https://axios-http.com)
- [REST API Best Practices](https://restfulapi.net)
