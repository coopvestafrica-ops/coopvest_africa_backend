# Coopvest Africa - Laravel Backend

A comprehensive REST API backend for the Coopvest Africa cooperative investment platform, built with Laravel 11, MySQL, and Laravel Sanctum for authentication.

## 🚀 Features

### Authentication & Security
- **User Registration & Login** - Secure account creation and authentication
- **Laravel Sanctum** - Token-based API authentication
- **Password Reset** - Secure password recovery via email
- **Two-Factor Authentication (2FA)** - TOTP-based 2FA with backup codes
- **KYC Verification** - Document-based identity verification
- **Role-Based Access Control** - Member, Admin, Super Admin roles

### Member Features
- **Profile Management** - Update personal information
- **Dashboard** - Overview of savings, loans, and contributions
- **Savings Management** - Track savings accounts and interest earned
- **Loan Management** - Apply for loans and track payments
- **Contributions** - Record monthly contributions
- **Transaction History** - View all financial transactions

### Admin Features
- **KYC Approval** - Review and approve/reject KYC submissions
- **Loan Approval** - Review and approve/reject loan applications
- **Member Management** - View and manage member accounts
- **Audit Logs** - Track all admin actions

### Super Admin Features
- **Global Settings** - Configure platform-wide settings
- **Interest Rates** - Set default loan interest rates
- **Admin Management** - Create and manage admin accounts
- **Security Configuration** - Configure platform security policies
- **Audit Logs** - Complete audit trail of all actions

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Node.js (for frontend integration)

## 🔧 Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd coopvest_africa_backend
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coopvest_africa
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run Migrations
```bash
php artisan migrate
php artisan db:seed
```

### 6. Create Storage Link
```bash
php artisan storage:link
```

### 7. Start Development Server
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## 📚 API Documentation

### Authentication Endpoints

#### Register
```
POST /api/auth/register
Content-Type: application/json

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

#### Login
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

#### Logout
```
POST /api/auth/logout
Authorization: Bearer {token}
```

#### Get Current User
```
GET /api/auth/me
Authorization: Bearer {token}
```

### Member Endpoints

#### Get Profile
```
GET /api/member/profile
Authorization: Bearer {token}
```

#### Update Profile
```
PUT /api/member/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890",
  "country": "Kenya"
}
```

#### Get Dashboard
```
GET /api/member/dashboard
Authorization: Bearer {token}
```

#### Get Transactions
```
GET /api/member/transactions?limit=20&offset=0
Authorization: Bearer {token}
```

#### Get Savings
```
GET /api/member/savings
Authorization: Bearer {token}
```

#### Get Loans
```
GET /api/member/loans
Authorization: Bearer {token}
```

### KYC Endpoints

#### Submit KYC
```
POST /api/kyc/submit
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
  "document_type": "national_id",
  "document_number": "12345678",
  "date_of_birth": "1990-01-01",
  "document_image": <file>,
  "proof_of_address": <file>
}
```

#### Get KYC Status
```
GET /api/kyc/status
Authorization: Bearer {token}
```

### 2FA Endpoints

#### Setup 2FA
```
POST /api/2fa/setup
Authorization: Bearer {token}
```

#### Confirm 2FA
```
POST /api/2fa/confirm
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "123456"
}
```

#### Verify 2FA
```
POST /api/2fa/verify
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "123456"
}
```

#### Disable 2FA
```
POST /api/2fa/disable
Authorization: Bearer {token}
Content-Type: application/json

{
  "password": "user_password"
}
```

### Loan Endpoints

#### Apply for Loan
```
POST /api/loans/apply
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 50000,
  "duration_months": 12,
  "purpose": "Business expansion"
}
```

#### Calculate Loan
```
POST /api/loans/calculate
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 50000,
  "duration_months": 12
}
```

#### Get Loan Details
```
GET /api/loans/{id}
Authorization: Bearer {token}
```

#### Make Loan Payment
```
POST /api/loans/{id}/payment
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 5000
}
```

## 🗄️ Database Schema

### Users Table
- id, first_name, last_name, email, phone, country
- password, role (member/admin/super_admin)
- kyc_status (pending/verified/rejected), kyc_verified_at
- two_fa_enabled, two_fa_secret
- is_active, last_login_at

### Loans Table
- id, member_id, amount, remaining_balance
- interest_rate, duration_months, purpose
- status (pending/approved/active/completed/rejected)
- approved_by, approved_at, disbursed_at
- monthly_payment_amount, next_payment_date

### Contributions Table
- id, member_id, amount, contribution_date
- status (pending/completed/failed)
- payment_method, transaction_reference

### Savings Table
- id, member_id, name, balance, rate
- total_interest_earned, last_interest_date

### Transactions Table
- id, user_id, loan_id, type
- amount, description, status, reference_number

### KYC Verifications Table
- id, user_id, document_type, document_number
- date_of_birth, document_image_path, proof_of_address_path
- status, rejection_reason, verified_by, verified_at

## 🔐 Security Features

- **CORS Configuration** - Configured for frontend integration
- **Sanctum Authentication** - Secure token-based authentication
- **Password Hashing** - Bcrypt password hashing
- **2FA Support** - TOTP-based two-factor authentication
- **File Upload Validation** - Secure file upload handling
- **Authorization** - Role-based access control
- **Audit Logging** - Track all admin actions

## 📦 Project Structure

```
coopvest_africa_backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php
│   │       ├── KYCController.php
│   │       ├── TwoFAController.php
│   │       ├── MemberController.php
│   │       └── LoanController.php
│   └── Models/
│       ├── User.php
│       ├── Loan.php
│       ├── Contribution.php
│       ├── Savings.php
│       ├── Transaction.php
│       ├── KYCVerification.php
│       ├── LoanPayment.php
│       ├── AuditLog.php
│       └── GlobalSetting.php
├── config/
│   └── cors.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── storage/
├── .env.example
├── composer.json
└── README.md
```

## 🧪 Testing

```bash
php artisan test
```

## 📝 Environment Variables

Key environment variables in `.env`:

```env
APP_NAME=Coopvest Africa
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coopvest_africa
DB_USERNAME=root
DB_PASSWORD=

FRONTEND_URL=http://localhost:5173

SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000
```

## 🚀 Deployment

### Production Checklist
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Configure proper database credentials
- [ ] Set up HTTPS/SSL
- [ ] Configure CORS for production domain
- [ ] Set up proper file storage (S3, etc.)
- [ ] Configure email service for password resets
- [ ] Set up monitoring and logging
- [ ] Run migrations on production database
- [ ] Set up regular backups

## 📞 Support

For issues or questions, please contact the development team or create an issue in the repository.

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.
