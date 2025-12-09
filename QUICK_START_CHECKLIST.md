# Quick Start: Unified Authentication Implementation

## 🎯 Choose Your Path

### ✅ **RECOMMENDED: Option 1 - Firebase Central Auth**
- ✨ Simplest to implement
- 🚀 Fastest time to market
- 🔄 Real-time sync built-in
- 📱 Works across all platforms

---

## 📋 Implementation Checklist

### Phase 1: Setup (Day 1)

#### Firebase Configuration
- [ ] Create Firebase project (if not exists)
- [ ] Enable Authentication methods:
  - [ ] Email/Password
  - [ ] Google Sign-In
  - [ ] Phone Authentication (optional)
- [ ] Get Firebase credentials
- [ ] Add web app to Firebase project
- [ ] Add iOS app to Firebase project
- [ ] Add Android app to Firebase project

#### Environment Variables
```bash
# coopvest_africa_website/.env
VITE_FIREBASE_API_KEY=xxx
VITE_FIREBASE_AUTH_DOMAIN=xxx
VITE_FIREBASE_PROJECT_ID=xxx
VITE_FIREBASE_STORAGE_BUCKET=xxx
VITE_FIREBASE_MESSAGING_SENDER_ID=xxx
VITE_FIREBASE_APP_ID=xxx
```

```bash
# coopvest_africa_backend/.env
FIREBASE_PROJECT_ID=xxx
FIREBASE_CREDENTIALS_PATH=/path/to/credentials.json
```

### Phase 2: Backend Setup (Day 2)

#### Install Firebase Admin SDK
```bash
cd coopvest_africa_backend
composer require kreait/firebase-php
```

#### Create Middleware
- [ ] Create `VerifyFirebaseToken` middleware
- [ ] Register in `app/Http/Kernel.php`
- [ ] Test token verification

#### Create User Model
```bash
php artisan make:model User -m
```

#### Database Migration
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('firebase_uid')->unique();
    $table->string('email')->unique();
    $table->string('name')->nullable();
    $table->string('phone')->nullable();
    $table->json('profile_data')->nullable();
    $table->timestamps();
});
```

### Phase 3: React Web App (Day 3)

#### Install Dependencies
```bash
cd coopvest_africa_website
npm install firebase
```

#### Create Auth Context
- [ ] Create `src/context/AuthContext.tsx`
- [ ] Implement `AuthProvider` component
- [ ] Create `useAuth` hook

#### Create Protected Routes
```typescript
// src/components/ProtectedRoute.tsx
import { useAuth } from '@/context/AuthContext';
import { Navigate } from 'react-router-dom';

export function ProtectedRoute({ children }) {
  const { user, loading } = useAuth();

  if (loading) return <div>Loading...</div>;
  if (!user) return <Navigate to="/login" />;

  return children;
}
```

#### Update API Calls
- [ ] Add Firebase token to all API requests
- [ ] Create axios interceptor

```typescript
// src/config/axiosConfig.ts
import axios from 'axios';
import { auth } from '@/config/firebase';

axios.interceptors.request.use(async (config) => {
  const token = await auth.currentUser?.getIdToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

### Phase 4: Flutter App (Day 4)

#### Verify Firebase Setup
- [ ] Check `pubspec.yaml` has firebase_auth
- [ ] Verify Firebase credentials in iOS/Android

#### Create Auth Service
- [ ] Create `lib/services/auth_service.dart`
- [ ] Implement sign up/sign in methods
- [ ] Add token management

#### Update API Calls
- [ ] Add Firebase token to HTTP headers
- [ ] Create HTTP client wrapper

```dart
// lib/services/api_service.dart
import 'package:http/http.dart' as http;
import 'package:firebase_auth/firebase_auth.dart';

class ApiService {
  final String baseUrl = 'https://api.coopvest.com';
  final FirebaseAuth _auth = FirebaseAuth.instance;

  Future<http.Response> get(String endpoint) async {
    final token = await _auth.currentUser?.getIdToken();
    return http.get(
      Uri.parse('$baseUrl$endpoint'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
    );
  }
}
```

### Phase 5: Testing (Day 5)

#### Unit Tests
- [ ] Test Firebase token verification
- [ ] Test user sync service
- [ ] Test auth context

#### Integration Tests
- [ ] Test Flutter → Backend flow
- [ ] Test React → Backend flow
- [ ] Test token refresh

#### Manual Testing
- [ ] Sign up with email
- [ ] Sign in with email
- [ ] Access protected endpoints
- [ ] Test logout
- [ ] Test token expiration

### Phase 6: Deployment (Day 6-7)

#### Staging
- [ ] Deploy backend to staging
- [ ] Deploy web app to staging
- [ ] Deploy Flutter app to staging
- [ ] Run full integration tests

#### Production
- [ ] Deploy backend to production
- [ ] Deploy web app to production
- [ ] Deploy Flutter app to production
- [ ] Monitor logs

---

## 🔧 Common Commands

### Backend Setup
```bash
# Install dependencies
composer install

# Run migrations
php artisan migrate

# Create Firebase middleware
php artisan make:middleware VerifyFirebaseToken

# Test API
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.coopvest.com/api/user/profile
```

### Web App Setup
```bash
# Install dependencies
npm install

# Start dev server
npm run dev

# Build for production
npm run build

# Start production server
npm start
```

### Flutter Setup
```bash
# Get dependencies
flutter pub get

# Run on device
flutter run

# Build APK
flutter build apk

# Build iOS
flutter build ios
```

---

## 🚨 Critical Points

### Security
- ✅ Never expose Firebase private key
- ✅ Always verify tokens on backend
- ✅ Use HTTPS only in production
- ✅ Implement rate limiting
- ✅ Add CORS restrictions

### Performance
- ✅ Cache user data locally
- ✅ Implement token refresh before expiry
- ✅ Use connection pooling for database
- ✅ Add CDN for static assets

### Monitoring
- ✅ Log all auth events
- ✅ Monitor failed login attempts
- ✅ Track token refresh rates
- ✅ Alert on suspicious activity

---

## 📞 Support Resources

### Firebase
- [Firebase Console](https://console.firebase.google.com)
- [Firebase Documentation](https://firebase.google.com/docs)
- [Firebase Support](https://firebase.google.com/support)

### Laravel
- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)

### React
- [React Documentation](https://react.dev)
- [Firebase React SDK](https://firebase.google.com/docs/web/setup)

### Flutter
- [Flutter Documentation](https://flutter.dev/docs)
- [Firebase Flutter](https://firebase.flutter.dev)

---

## ✅ Success Criteria

Your implementation is complete when:

- [ ] Users can sign up via web and mobile
- [ ] Users can sign in via web and mobile
- [ ] Tokens are verified on backend
- [ ] Protected endpoints require valid token
- [ ] User data syncs across platforms
- [ ] Logout clears all sessions
- [ ] Token refresh works automatically
- [ ] All tests pass
- [ ] No security vulnerabilities
- [ ] Performance meets requirements

---

**Ready to start? Pick Option 1 and follow the checklist above!**
