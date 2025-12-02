# Loan Application API - Integration & Deployment Guide

## 📋 Pre-Deployment Checklist

### Code Quality
- [ ] All tests pass: `php artisan test tests/Feature/LoanApplicationTest.php`
- [ ] No linting errors: `php artisan lint`
- [ ] Code review completed
- [ ] Documentation reviewed and updated

### Database Preparation
- [ ] Backup existing database
- [ ] Review migration: `2024_11_12_000003_create_loan_applications_table.php`
- [ ] Test migration on staging environment
- [ ] Confirm rollback procedure

### Security
- [ ] All endpoints protected with authentication
- [ ] Authorization checks in place
- [ ] Input validation comprehensive
- [ ] Error messages don't expose sensitive info
- [ ] Rate limiting configured (if applicable)
- [ ] CORS settings reviewed

### Performance
- [ ] Database indexes created
- [ ] Query efficiency verified
- [ ] Pagination limits set
- [ ] Cache strategy defined

---

## 🚀 Deployment Steps

### Step 1: Database Migration

```bash
# Pull latest code
git pull origin main

# Run migration
php artisan migrate

# Verify migration
php artisan migrate:status
```

**Rollback (if needed):**
```bash
php artisan migrate:rollback
```

### Step 2: Cache & Configuration

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Rebuild cache (production)
php artisan config:cache
php artisan route:cache
```

### Step 3: Verify Installation

```bash
# Check routes
php artisan route:list | grep loan-applications

# Run tests
php artisan test

# Test specific endpoint (requires auth token)
curl -X GET "https://api.coopvest.com/api/loan-applications/available-types" \
  -H "Authorization: Bearer {test_token}"
```

### Step 4: Monitor

```bash
# Check logs
tail -f storage/logs/laravel.log

# Monitor errors
php artisan tinker
>>> \App\Models\LoanApplication::count()
```

---

## 🔧 Configuration

### Environment Variables (if needed)

Add to `.env`:
```env
# Loan Application Settings
LOAN_APP_PROCESSING_FEE_PERCENTAGE=2
LOAN_APP_MAX_DEBT_TO_INCOME_RATIO=0.5
LOAN_APP_REQUIRES_GUARANTOR=false
LOAN_APP_NOTIFICATION_EMAIL=loans@coopvest.com
```

### Middleware

Ensure these middleware exist:
- `auth:sanctum` - Authentication
- `admin` - Admin role check

Check `app/Http/Kernel.php`:
```php
protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'admin' => \App\Http\Middleware\IsAdmin::class,
    // ...
];
```

---

## 🧪 Testing Guide

### Run All Loan Application Tests

```bash
php artisan test tests/Feature/LoanApplicationTest.php
```

### Run Specific Test

```bash
php artisan test tests/Feature/LoanApplicationTest.php --filter=test_create_loan_application
```

### Test Coverage

```bash
php artisan test --coverage
```

### Manual Testing Workflow

```bash
# 1. Create test user
POST /api/auth/register
{
  "name": "Test User",
  "email": "test@coopvest.com",
  "password": "TestPassword123!"
}

# 2. Login
POST /api/auth/login
{
  "email": "test@coopvest.com",
  "password": "TestPassword123!"
}
# Save token: {token}

# 3. Complete KYC (or manually set in DB)
POST /api/kyc/submit
Header: Authorization: Bearer {token}

# 4. Get available loan types
GET /api/loan-applications/available-types
Header: Authorization: Bearer {token}

# 5. Create application
POST /api/loan-applications/create
Header: Authorization: Bearer {token}
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
  "savings_balance": 5000
}

# 6. Submit application
POST /api/loan-applications/{id}/submit
Header: Authorization: Bearer {token}

# 7. Admin review (login as admin)
GET /api/loan-applications/admin/review
Header: Authorization: Bearer {admin_token}
```

---

## 📊 Monitoring & Analytics

### Key Metrics to Track

```sql
-- Applications by status
SELECT status, COUNT(*) FROM loan_applications GROUP BY status;

-- Average application time in stage
SELECT stage, AVG(DATEDIFF(now(), created_at)) FROM loan_applications 
GROUP BY stage;

-- Approval rate
SELECT 
  (COUNT(CASE WHEN status='approved' THEN 1 END) / COUNT(*)) * 100 as approval_rate
FROM loan_applications;

-- Total requested vs approved
SELECT 
  SUM(requested_amount) as total_requested,
  SUM(CASE WHEN status='approved' THEN requested_amount ELSE 0 END) as total_approved
FROM loan_applications;
```

### Application Logs

Monitor these log entries:
- `LoanApplicationCreated` - New applications
- `LoanApplicationSubmitted` - User submissions
- `LoanApplicationApproved` - Approvals
- `LoanApplicationRejected` - Rejections

---

## 🐛 Troubleshooting

### Common Issues

#### Issue: "Migration failed"
```bash
# Solution: Check migration status
php artisan migrate:status

# Rollback and re-run
php artisan migrate:rollback
php artisan migrate
```

#### Issue: "KYC verification required" error
```bash
# Solution: Verify user KYC status
php artisan tinker
>>> $user = \App\Models\User::find(1);
>>> $user->kyc_verified = true;
>>> $user->save();
```

#### Issue: "Unauthorized" on admin endpoints
```bash
# Solution: Check user is_admin flag
php artisan tinker
>>> $user = \App\Models\User::find(1);
>>> $user->is_admin = true;
>>> $user->save();
```

#### Issue: Applications not showing up
```bash
# Solution: Check database connection
php artisan tinker
>>> \App\Models\LoanApplication::count()

# Check relationships
>>> $app = \App\Models\LoanApplication::first();
>>> $app->user
>>> $app->loanType
```

#### Issue: Validation errors unclear
```bash
# Solution: Enable detailed error logging
# In .env: APP_DEBUG=true (development only)

# Check validation rules in request class
cat app/Http/Requests/CreateLoanApplicationRequest.php
```

---

## 🔄 Rollback Plan

If issues occur after deployment:

### Option 1: Rollback Migration
```bash
php artisan migrate:rollback

# This will drop the loan_applications table
# Foreign key constraints will prevent if referenced
```

### Option 2: Disable Endpoints
Comment out routes in `routes/api.php`:
```php
// Route::prefix('loan-applications')->group(function () {
//     // Routes...
// });
```

### Option 3: Feature Flag
Implement feature flag:
```php
Route::middleware('auth:sanctum')
    ->when(config('features.loan_applications_enabled'))
    ->group(function () {
        // Routes...
    });
```

---

## 📈 Performance Optimization

### Database Query Optimization

```php
// ✅ Good - Eager loading
$applications = LoanApplication::with('loanType', 'user')->get();

// ❌ Bad - N+1 problem
$applications = LoanApplication::all();
foreach ($applications as $app) {
    $app->loanType; // Separate query for each
}
```

### Caching Strategy

```php
// Cache available loan types
$loanTypes = Cache::remember('active_loan_types', 3600, function () {
    return LoanType::where('is_active', true)->get();
});
```

### Pagination

```php
// For admin review with large datasets
$applications = LoanApplication::with('user', 'loanType')
    ->paginate(20); // Default 20 per page
```

---

## 🔐 Security Hardening

### Rate Limiting (if not already configured)

```php
// In middleware
Route::middleware('throttle:60,1')->group(function () {
    Route::prefix('loan-applications')->group(function () {
        // Routes with rate limiting
    });
});
```

### CSRF Protection
Already handled by Laravel (only for non-API requests).

### XSS Protection
- All responses sanitized
- Input validation in place
- Error messages don't expose system details

### SQL Injection Protection
- Using Eloquent ORM (parameterized queries)
- All user inputs validated

---

## 📚 Documentation Updates

After deployment, update:

### Frontend Documentation
- [ ] Add API endpoints to frontend docs
- [ ] Update integration guide
- [ ] Add examples for your framework

### Internal Documentation
- [ ] Update system architecture diagram
- [ ] Document database schema changes
- [ ] Add API to team wiki

### User Documentation
- [ ] Update help center
- [ ] Create user guide for loan application
- [ ] Add FAQ section

---

## 🎓 Team Training

Before release, ensure team knows:

### Backend Team
- [ ] How to run tests
- [ ] How to interpret error logs
- [ ] How to add new endpoints
- [ ] Database schema and relationships

### Frontend Team
- [ ] API endpoints and authentication
- [ ] Request/response formats
- [ ] Error handling
- [ ] Example workflow

### Admin Team
- [ ] How to review applications
- [ ] How to move applications through stages
- [ ] How to view analytics
- [ ] Troubleshooting steps

---

## 📞 Post-Deployment Support

### First 24 Hours
- Monitor error logs closely
- Be ready for quick fixes
- Have rollback plan ready

### First Week
- Gather user feedback
- Monitor API usage patterns
- Fine-tune performance if needed

### First Month
- Analyze usage statistics
- Identify improvements
- Plan Phase 2 features

---

## ✅ Post-Deployment Verification

Run this checklist 24 hours after deployment:

```bash
# 1. Check application count
curl -X GET "https://api.coopvest.com/api/loan-applications/admin/review?page=1" \
  -H "Authorization: Bearer {admin_token}"

# 2. Verify recent application
curl -X GET "https://api.coopvest.com/api/loan-applications/1" \
  -H "Authorization: Bearer {user_token}"

# 3. Check error logs
tail -100 storage/logs/laravel.log | grep -i error

# 4. Database integrity
php artisan tinker
>>> $count = \App\Models\LoanApplication::count();
>>> echo "Total applications: $count";

# 5. Test admin functions
# Create test application, move through stages, verify

# 6. Performance check
# Measure response times
# Check database queries
```

---

## 🎯 Success Criteria

✅ **All users can:**
- Create loan applications
- View their applications
- Submit applications
- See application status

✅ **All admins can:**
- View pending applications
- Move applications through stages
- Review application details

✅ **System:**
- No 500 errors in logs
- Response times < 200ms
- Database integrity maintained
- All tests passing

---

## 📅 Maintenance Schedule

### Weekly
- [ ] Review error logs
- [ ] Check application volume
- [ ] Monitor performance metrics

### Monthly
- [ ] Backup database
- [ ] Analyze usage statistics
- [ ] Plan improvements

### Quarterly
- [ ] Security audit
- [ ] Performance optimization review
- [ ] Feature planning

---

**Deployment Ready!**

For any questions, see LOAN_APPLICATION_API.md or PHASE_1_IMPLEMENTATION_SUMMARY.md

Last Updated: January 2024
