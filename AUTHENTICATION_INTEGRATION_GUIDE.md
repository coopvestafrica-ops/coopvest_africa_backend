# Coopvest Africa - Unified Authentication Integration Guide

## 📋 Project Overview

Your Coopvest Africa platform consists of three interconnected applications:

| Project | Tech Stack | Auth System | Purpose |
|---------|-----------|------------|---------|
| **Coopvest_Africa** | Flutter (Mobile/Desktop) | Firebase Auth | Mobile & Desktop App |
| **coopvest_africa_website** | React + TypeScript + Node.js | Custom/Session-based | Web Application |
| **coopvest_africa_backend** | Laravel 11 + PHP 8.2 | Laravel Sanctum | API Backend |

---

## 🔐 Current Authentication Architecture

### 1. **Flutter App (Coopvest_Africa)**
```
Dependencies:
- firebase_auth: ^6.1.0
- firebase_core: ^4.1.1
- cloud_firestore: ^6.0.2
- jwt_decoder: ^2.0.1
- flutter_secure_storage: ^9.2.2

Flow:
User → Firebase Auth → JWT Token → Secure Storage → API Calls
```

### 2. **Web App (coopvest_africa_website)**
```
Tech Stack:
- React + TypeScript
- Vite (Build tool)
- tRPC (Type-safe API)
- Drizzle ORM
- Axios for HTTP

Current Auth: Likely session-based or custom JWT
```

### 3. **Backend (coopvest_africa_backend)**
```
Framework: Laravel 11
Auth: Laravel Sanctum (Token-based)
Database: Likely PostgreSQL/MySQL
API: RESTful endpoints
```

---

## ✅ Recommended Solution: Unified Auth Architecture

### **Option 1: Firebase as Central Auth (RECOMMENDED)**

**Best for:** Rapid development, minimal backend changes, real-time sync

```
┌─────────────────────────────────────────────────────────┐
│                    Firebase Auth                         │
│  (Email/Password, Google, Phone, Custom Claims)         │
└────────────────┬────────────────────────────────────────┘
                 │
        ┌────────┼────────┐
        │        │        │
        ▼        ▼        ▼
    Flutter   React Web  Backend
     App       App      (Verify)
```

**Implementation Steps:**

#### Step 1: Configure Firebase for Web
```typescript
// coopvest_africa_website/src/config/firebase.ts
import { initializeApp } from 'firebase/app';
import { getAuth } from 'firebase/auth';
import { getFirestore } from 'firebase/firestore';

const firebaseConfig = {
  apiKey: process.env.VITE_FIREBASE_API_KEY,
  authDomain: process.env.VITE_FIREBASE_AUTH_DOMAIN,
  projectId: process.env.VITE_FIREBASE_PROJECT_ID,
  storageBucket: process.env.VITE_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: process.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: process.env.VITE_FIREBASE_APP_ID,
};

export const app = initializeApp(firebaseConfig);
export const auth = getAuth(app);
export const db = getFirestore(app);
```

#### Step 2: Create Auth Context (React)
```typescript
// coopvest_africa_website/src/context/AuthContext.tsx
import { createContext, useContext, useEffect, useState } from 'react';
import { 
  User, 
  signInWithEmailAndPassword, 
  signOut,
  onAuthStateChanged 
} from 'firebase/auth';
import { auth } from '@/config/firebase';

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  getIdToken: () => Promise<string>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, (currentUser) => {
      setUser(currentUser);
      setLoading(false);
    });

    return unsubscribe;
  }, []);

  const login = async (email: string, password: string) => {
    await signInWithEmailAndPassword(auth, email, password);
  };

  const logout = async () => {
    await signOut(auth);
  };

  const getIdToken = async () => {
    return user ? await user.getIdToken() : '';
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, getIdToken }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}
```

#### Step 3: Backend Verification (Laravel)
```php
// coopvest_africa_backend/app/Http/Middleware/VerifyFirebaseToken.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class VerifyFirebaseToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $firebase = (new Factory)
                ->withServiceAccount(config('firebase.credentials'))
                ->create();

            $auth = $firebase->getAuth();
            $verifiedIdToken = $auth->verifyIdToken($token);
            $uid = $verifiedIdToken->claims()->get('sub');

            // Attach user info to request
            $request->merge(['firebase_uid' => $uid]);

            return $next($request);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }
}
```

#### Step 4: API Middleware Setup
```php
// coopvest_africa_backend/routes/api.php
Route::middleware(['auth:sanctum', 'verify.firebase.token'])->group(function () {
    Route::post('/loans/apply', [LoanController::class, 'apply']);
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::post('/guarantor/add', [GuarantorController::class, 'add']);
    // ... other protected routes
});
```

---

### **Option 2: Hybrid Approach (Firebase + Custom Backend)**

**Best for:** Maximum control, custom business logic, gradual migration

```
┌──────────────────────────────────────────────────────────┐
│                  Firebase Auth                            │
│         (Handles authentication only)                     │
└────────────────┬─────────────────────────────────────────┘
                 │
        ┌────────┼────────┐
        │        │        │
        ▼        ▼        ▼
    Flutter   React Web  Backend
     App       App      (Sync User)
                         │
                         ▼
                    User Database
                    (PostgreSQL)
```

**Implementation:**

#### Step 1: Create User Sync Service
```php
// coopvest_africa_backend/app/Services/FirebaseUserSyncService.php
<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Factory;

class FirebaseUserSyncService
{
    public function syncUser(string $firebaseUid, array $userData)
    {
        return User::updateOrCreate(
            ['firebase_uid' => $firebaseUid],
            [
                'email' => $userData['email'],
                'name' => $userData['name'] ?? null,
                'phone' => $userData['phone'] ?? null,
                'profile_data' => json_encode($userData),
            ]
        );
    }

    public function getUserByFirebaseUid(string $uid)
    {
        return User::where('firebase_uid', $uid)->first();
    }
}
```

#### Step 2: Create Sync Endpoint
```php
// coopvest_africa_backend/app/Http/Controllers/AuthController.php
<?php

namespace App\Http\Controllers;

use App\Services\FirebaseUserSyncService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private FirebaseUserSyncService $syncService) {}

    public function syncUser(Request $request)
    {
        $firebaseUid = $request->input('firebase_uid');
        $userData = $request->input('user_data');

        $user = $this->syncService->syncUser($firebaseUid, $userData);

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $user->createToken('api-token')->plainTextToken,
        ]);
    }
}
```

#### Step 3: React Integration
```typescript
// coopvest_africa_website/src/hooks/useFirebaseAuth.ts
import { useAuth } from '@/context/AuthContext';
import { useEffect } from 'react';
import axios from 'axios';

export function useFirebaseAuth() {
  const { user, getIdToken } = useAuth();

  useEffect(() => {
    if (user) {
      syncUserToBackend();
    }
  }, [user]);

  const syncUserToBackend = async () => {
    try {
      const idToken = await getIdToken();
      
      await axios.post('/api/auth/sync-user', {
        firebase_uid: user?.uid,
        user_data: {
          email: user?.email,
          name: user?.displayName,
          phone: user?.phoneNumber,
        },
      }, {
        headers: { Authorization: `Bearer ${idToken}` },
      });
    } catch (error) {
      console.error('Failed to sync user:', error);
    }
  };

  return { user };
}
```

---

### **Option 3: Supabase (All-in-One Solution)**

**Best for:** Simplicity, built-in real-time, PostgreSQL database

```
Supabase = Firebase + PostgreSQL + Real-time
- Auth (Email, OAuth, MFA)
- Database (PostgreSQL)
- Real-time subscriptions
- Storage
- Edge Functions
```

**Setup:**
```bash
npm install @supabase/supabase-js
```

```typescript
// coopvest_africa_website/src/config/supabase.ts
import { createClient } from '@supabase/supabase-js';

export const supabase = createClient(
  process.env.VITE_SUPABASE_URL!,
  process.env.VITE_SUPABASE_ANON_KEY!
);
```

---

## 🔄 Data Synchronization Strategy

### Real-time Sync Between Apps

```typescript
// coopvest_africa_website/src/services/syncService.ts
import { useAuth } from '@/context/AuthContext';
import { supabase } from '@/config/supabase';

export async function syncUserData() {
  const { user } = useAuth();
  
  if (!user) return;

  // Subscribe to real-time changes
  const subscription = supabase
    .from('users')
    .on('*', (payload) => {
      console.log('User data updated:', payload);
      // Update local state
    })
    .subscribe();

  return () => subscription.unsubscribe();
}
```

---

## 🛡️ Security Best Practices

### 1. **Token Management**
```typescript
// Store tokens securely
const storeToken = (token: string) => {
  // Web: Use httpOnly cookies (server-side)
  // Mobile: Use flutter_secure_storage
  localStorage.setItem('auth_token', token); // NOT RECOMMENDED for sensitive data
};
```

### 2. **CORS Configuration**
```php
// coopvest_africa_backend/config/cors.php
'allowed_origins' => [
    'https://coopvest.com',
    'https://app.coopvest.com',
    'http://localhost:3000', // Development
],
```

### 3. **Rate Limiting**
```php
// coopvest_africa_backend/routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});
```

### 4. **HTTPS Enforcement**
```php
// coopvest_africa_backend/app/Http/Middleware/ForceHttps.php
if (!$request->secure() && config('app.env') === 'production') {
    return redirect()->secure($request->getRequestUri());
}
```

---

## 📱 Flutter Implementation

### Firebase Auth Setup
```dart
// lib/services/auth_service.dart
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class AuthService {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final _secureStorage = const FlutterSecureStorage();

  Future<UserCredential> signUp(String email, String password) async {
    return await _auth.createUserWithEmailAndPassword(
      email: email,
      password: password,
    );
  }

  Future<UserCredential> signIn(String email, String password) async {
    return await _auth.signInWithEmailAndPassword(
      email: email,
      password: password,
    );
  }

  Future<String?> getIdToken() async {
    return await _auth.currentUser?.getIdToken();
  }

  Future<void> saveTokenSecurely(String token) async {
    await _secureStorage.write(key: 'auth_token', value: token);
  }

  Future<String?> getStoredToken() async {
    return await _secureStorage.read(key: 'auth_token');
  }

  Future<void> logout() async {
    await _auth.signOut();
    await _secureStorage.delete(key: 'auth_token');
  }
}
```

---

## 🚀 Implementation Roadmap

### Phase 1: Foundation (Week 1)
- [ ] Set up Firebase for web app
- [ ] Create auth context in React
- [ ] Implement Firebase token verification in Laravel
- [ ] Test Flutter ↔ Backend communication

### Phase 2: Integration (Week 2)
- [ ] Implement user sync service
- [ ] Create unified user database schema
- [ ] Set up real-time sync
- [ ] Add error handling & logging

### Phase 3: Security (Week 3)
- [ ] Implement refresh token rotation
- [ ] Add MFA support
- [ ] Set up rate limiting
- [ ] Security audit

### Phase 4: Testing & Deployment (Week 4)
- [ ] Integration tests
- [ ] Load testing
- [ ] Staging deployment
- [ ] Production rollout

---

## 🔗 API Endpoints Reference

### Authentication Endpoints
```
POST   /api/auth/register          - Register new user
POST   /api/auth/login             - Login user
POST   /api/auth/logout            - Logout user
POST   /api/auth/refresh-token     - Refresh access token
POST   /api/auth/sync-user         - Sync Firebase user to backend
GET    /api/auth/profile           - Get current user profile
POST   /api/auth/verify-token      - Verify token validity
```

### Protected Endpoints (Require Auth)
```
GET    /api/user/profile           - User profile
POST   /api/loans/apply            - Apply for loan
GET    /api/loans/status           - Loan status
POST   /api/guarantor/add          - Add guarantor
GET    /api/guarantor/list         - List guarantors
```

---

## 📊 Database Schema

### Users Table
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    firebase_uid VARCHAR(255) UNIQUE,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255),
    phone VARCHAR(20),
    profile_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE user_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    token VARCHAR(500),
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

## 🐛 Troubleshooting

### Issue: Token Verification Fails
**Solution:**
```php
// Ensure Firebase credentials are properly loaded
$firebase = (new Factory)
    ->withServiceAccount(json_decode(
        file_get_contents(config('firebase.credentials_path')),
        true
    ))
    ->create();
```

### Issue: CORS Errors
**Solution:**
```php
// Add CORS middleware to all routes
Route::middleware('cors')->group(function () {
    // Your routes
});
```

### Issue: Session Timeout
**Solution:**
```typescript
// Implement token refresh logic
const refreshToken = async () => {
  const response = await axios.post('/api/auth/refresh-token');
  localStorage.setItem('auth_token', response.data.token);
};
```

---

## 📚 Resources

- [Firebase Authentication Docs](https://firebase.google.com/docs/auth)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [React Firebase Integration](https://firebase.google.com/docs/web/setup)
- [Flutter Firebase](https://firebase.flutter.dev/)
- [Supabase Documentation](https://supabase.com/docs)

---

## ✨ Next Steps

1. **Choose your approach** (Option 1, 2, or 3)
2. **Set up Firebase** for web and mobile
3. **Implement backend verification**
4. **Create user sync service**
5. **Test end-to-end flow**
6. **Deploy to staging**
7. **Production rollout**

---

**Last Updated:** December 9, 2025
**Status:** Ready for Implementation
