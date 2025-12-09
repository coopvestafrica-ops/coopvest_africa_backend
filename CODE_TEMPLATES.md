# Ready-to-Use Code Templates

## 🔥 Copy & Paste These Into Your Projects

---

## 1️⃣ React Web App - Firebase Auth Context

**File:** `coopvest_africa_website/src/context/AuthContext.tsx`

```typescript
import React, { createContext, useContext, useEffect, useState } from 'react';
import {
  User,
  signInWithEmailAndPassword,
  signUpWithEmailAndPassword,
  signOut,
  onAuthStateChanged,
  GoogleAuthProvider,
  signInWithPopup,
} from 'firebase/auth';
import { auth } from '@/config/firebase';

interface AuthContextType {
  user: User | null;
  loading: boolean;
  error: string | null;
  login: (email: string, password: string) => Promise<void>;
  signup: (email: string, password: string) => Promise<void>;
  loginWithGoogle: () => Promise<void>;
  logout: () => Promise<void>;
  getIdToken: () => Promise<string>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, (currentUser) => {
      setUser(currentUser);
      setLoading(false);
    });

    return unsubscribe;
  }, []);

  const login = async (email: string, password: string) => {
    try {
      setError(null);
      await signInWithEmailAndPassword(auth, email, password);
    } catch (err: any) {
      setError(err.message);
      throw err;
    }
  };

  const signup = async (email: string, password: string) => {
    try {
      setError(null);
      await signUpWithEmailAndPassword(auth, email, password);
    } catch (err: any) {
      setError(err.message);
      throw err;
    }
  };

  const loginWithGoogle = async () => {
    try {
      setError(null);
      const provider = new GoogleAuthProvider();
      await signInWithPopup(auth, provider);
    } catch (err: any) {
      setError(err.message);
      throw err;
    }
  };

  const logout = async () => {
    try {
      setError(null);
      await signOut(auth);
    } catch (err: any) {
      setError(err.message);
      throw err;
    }
  };

  const getIdToken = async () => {
    return user ? await user.getIdToken() : '';
  };

  return (
    <AuthContext.Provider
      value={{ user, loading, error, login, signup, loginWithGoogle, logout, getIdToken }}
    >
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

---

## 2️⃣ React Web App - Axios Interceptor

**File:** `coopvest_africa_website/src/config/axiosConfig.ts`

```typescript
import axios, { AxiosInstance } from 'axios';
import { auth } from '@/config/firebase';

export const createAxiosInstance = (): AxiosInstance => {
  const instance = axios.create({
    baseURL: process.env.VITE_API_URL || 'http://localhost:8000/api',
    timeout: 10000,
  });

  // Request interceptor - Add Firebase token
  instance.interceptors.request.use(
    async (config) => {
      try {
        const token = await auth.currentUser?.getIdToken();
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
      } catch (error) {
        console.error('Failed to get ID token:', error);
      }
      return config;
    },
    (error) => Promise.reject(error)
  );

  // Response interceptor - Handle errors
  instance.interceptors.response.use(
    (response) => response,
    async (error) => {
      if (error.response?.status === 401) {
        // Token expired or invalid
        try {
          await auth.signOut();
          window.location.href = '/login';
        } catch (signOutError) {
          console.error('Sign out failed:', signOutError);
        }
      }
      return Promise.reject(error);
    }
  );

  return instance;
};

export const apiClient = createAxiosInstance();
```

---

## 3️⃣ React Web App - Protected Route Component

**File:** `coopvest_africa_website/src/components/ProtectedRoute.tsx`

```typescript
import { Navigate } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';

interface ProtectedRouteProps {
  children: React.ReactNode;
}

export function ProtectedRoute({ children }: ProtectedRouteProps) {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
}
```

---

## 4️⃣ Laravel Backend - Firebase Middleware

**File:** `coopvest_africa_backend/app/Http/Middleware/VerifyFirebaseToken.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Symfony\Component\HttpFoundation\Response;

class VerifyFirebaseToken
{
    private $auth;

    public function __construct()
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(
                    json_decode(
                        file_get_contents(config('firebase.credentials_path')),
                        true
                    )
                );
            
            $firebase = $factory->create();
            $this->auth = $firebase->getAuth();
        } catch (\Exception $e) {
            \Log::error('Firebase initialization failed: ' . $e->getMessage());
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getBearerToken($request);

        if (!$token) {
            return response()->json(['error' => 'Unauthorized - No token provided'], 401);
        }

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $uid = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');

            // Attach Firebase info to request
            $request->merge([
                'firebase_uid' => $uid,
                'firebase_email' => $email,
            ]);

            return $next($request);
        } catch (FailedToVerifyToken $e) {
            \Log::warning('Token verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Unauthorized - Invalid token'], 401);
        } catch (\Exception $e) {
            \Log::error('Token verification error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    private function getBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        
        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
```

---

## 5️⃣ Laravel Backend - User Sync Service

**File:** `coopvest_africa_backend/app/Services/FirebaseUserSyncService.php`

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FirebaseUserSyncService
{
    /**
     * Sync or create user from Firebase
     */
    public function syncUser(string $firebaseUid, array $userData): User
    {
        return User::updateOrCreate(
            ['firebase_uid' => $firebaseUid],
            [
                'email' => $userData['email'] ?? null,
                'name' => $userData['name'] ?? null,
                'phone' => $userData['phone'] ?? null,
                'profile_data' => json_encode($userData),
                'last_login_at' => now(),
            ]
        );
    }

    /**
     * Get user by Firebase UID
     */
    public function getUserByFirebaseUid(string $uid): ?User
    {
        return User::where('firebase_uid', $uid)->first();
    }

    /**
     * Get or create user
     */
    public function getOrCreateUser(string $firebaseUid, array $userData): User
    {
        $user = $this->getUserByFirebaseUid($firebaseUid);

        if ($user) {
            return $user;
        }

        return $this->syncUser($firebaseUid, $userData);
    }

    /**
     * Update user profile
     */
    public function updateUserProfile(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'phone' => $data['phone'] ?? $user->phone,
            'profile_data' => json_encode(array_merge(
                json_decode($user->profile_data ?? '{}', true),
                $data
            )),
        ]);

        return $user;
    }
}
```

---

## 6️⃣ Laravel Backend - Auth Controller

**File:** `coopvest_africa_backend/app/Http/Controllers/AuthController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\FirebaseUserSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private FirebaseUserSyncService $syncService) {}

    /**
     * Sync Firebase user to backend
     */
    public function syncUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firebase_uid' => 'required|string',
            'user_data' => 'required|array',
            'user_data.email' => 'required|email',
            'user_data.name' => 'nullable|string',
            'user_data.phone' => 'nullable|string',
        ]);

        try {
            $user = $this->syncService->syncUser(
                $validated['firebase_uid'],
                $validated['user_data']
            );

            return response()->json([
                'success' => true,
                'user' => $user,
                'message' => 'User synced successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('User sync failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to sync user',
            ], 500);
        }
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request): JsonResponse
    {
        $firebaseUid = $request->input('firebase_uid');
        
        $user = $this->syncService->getUserByFirebaseUid($firebaseUid);

        if (!$user) {
            return response()->json([
                'error' => 'User not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $firebaseUid = $request->input('firebase_uid');
        
        $user = $this->syncService->getUserByFirebaseUid($firebaseUid);

        if (!$user) {
            return response()->json([
                'error' => 'User not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $updatedUser = $this->syncService->updateUserProfile($user, $validated);

            return response()->json([
                'success' => true,
                'user' => $updatedUser,
                'message' => 'Profile updated successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Profile update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update profile',
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        // Firebase handles logout on client side
        // This endpoint can be used for cleanup if needed
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
```

---

## 7️⃣ Laravel Backend - Routes

**File:** `coopvest_africa_backend/routes/api.php`

```php
<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/sync-user', [AuthController::class, 'syncUser']);

// Protected routes (require Firebase token)
Route::middleware(['verify.firebase.token'])->group(function () {
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Add your other protected routes here
    // Route::post('/loans/apply', [LoanController::class, 'apply']);
    // Route::get('/loans/status', [LoanController::class, 'status']);
});
```

---

## 8️⃣ Flutter - Auth Service

**File:** `Coopvest_Africa/lib/services/auth_service.dart`

```dart
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class AuthService {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final _secureStorage = const FlutterSecureStorage();
  final String _apiBaseUrl = 'https://api.coopvest.com/api';

  // Sign up with email and password
  Future<UserCredential> signUp({
    required String email,
    required String password,
    required String name,
  }) async {
    try {
      final userCredential = await _auth.createUserWithEmailAndPassword(
        email: email,
        password: password,
      );

      // Update user profile
      await userCredential.user?.updateDisplayName(name);

      // Sync user to backend
      await _syncUserToBackend(userCredential.user!);

      return userCredential;
    } on FirebaseAuthException catch (e) {
      throw _handleAuthException(e);
    }
  }

  // Sign in with email and password
  Future<UserCredential> signIn({
    required String email,
    required String password,
  }) async {
    try {
      final userCredential = await _auth.signInWithEmailAndPassword(
        email: email,
        password: password,
      );

      // Sync user to backend
      await _syncUserToBackend(userCredential.user!);

      return userCredential;
    } on FirebaseAuthException catch (e) {
      throw _handleAuthException(e);
    }
  }

  // Get current user
  User? get currentUser => _auth.currentUser;

  // Get ID token
  Future<String?> getIdToken() async {
    try {
      return await _auth.currentUser?.getIdToken();
    } catch (e) {
      print('Error getting ID token: $e');
      return null;
    }
  }

  // Save token securely
  Future<void> saveTokenSecurely(String token) async {
    try {
      await _secureStorage.write(key: 'auth_token', value: token);
    } catch (e) {
      print('Error saving token: $e');
    }
  }

  // Get stored token
  Future<String?> getStoredToken() async {
    try {
      return await _secureStorage.read(key: 'auth_token');
    } catch (e) {
      print('Error retrieving token: $e');
      return null;
    }
  }

  // Logout
  Future<void> logout() async {
    try {
      await _secureStorage.delete(key: 'auth_token');
      await _auth.signOut();
    } catch (e) {
      print('Error logging out: $e');
    }
  }

  // Sync user to backend
  Future<void> _syncUserToBackend(User user) async {
    try {
      final token = await user.getIdToken();
      final response = await http.post(
        Uri.parse('$_apiBaseUrl/auth/sync-user'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'firebase_uid': user.uid,
          'user_data': {
            'email': user.email,
            'name': user.displayName,
            'phone': user.phoneNumber,
          },
        }),
      );

      if (response.statusCode != 200) {
        print('Failed to sync user: ${response.body}');
      }
    } catch (e) {
      print('Error syncing user to backend: $e');
    }
  }

  // Handle Firebase auth exceptions
  String _handleAuthException(FirebaseAuthException e) {
    switch (e.code) {
      case 'weak-password':
        return 'The password provided is too weak.';
      case 'email-already-in-use':
        return 'An account already exists for that email.';
      case 'invalid-email':
        return 'The email address is not valid.';
      case 'user-not-found':
        return 'No user found for that email.';
      case 'wrong-password':
        return 'Wrong password provided for that user.';
      default:
        return 'An error occurred: ${e.message}';
    }
  }
}
```

---

## 9️⃣ Flutter - API Service

**File:** `Coopvest_Africa/lib/services/api_service.dart`

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'auth_service.dart';

class ApiService {
  final String baseUrl = 'https://api.coopvest.com/api';
  final AuthService _authService = AuthService();

  // Make authenticated GET request
  Future<dynamic> get(String endpoint) async {
    try {
      final token = await _authService.getIdToken();
      
      if (token == null) {
        throw Exception('No authentication token available');
      }

      final response = await http.get(
        Uri.parse('$baseUrl$endpoint'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );

      return _handleResponse(response);
    } catch (e) {
      print('GET request error: $e');
      rethrow;
    }
  }

  // Make authenticated POST request
  Future<dynamic> post(String endpoint, {required Map<String, dynamic> body}) async {
    try {
      final token = await _authService.getIdToken();
      
      if (token == null) {
        throw Exception('No authentication token available');
      }

      final response = await http.post(
        Uri.parse('$baseUrl$endpoint'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode(body),
      );

      return _handleResponse(response);
    } catch (e) {
      print('POST request error: $e');
      rethrow;
    }
  }

  // Make authenticated PUT request
  Future<dynamic> put(String endpoint, {required Map<String, dynamic> body}) async {
    try {
      final token = await _authService.getIdToken();
      
      if (token == null) {
        throw Exception('No authentication token available');
      }

      final response = await http.put(
        Uri.parse('$baseUrl$endpoint'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode(body),
      );

      return _handleResponse(response);
    } catch (e) {
      print('PUT request error: $e');
      rethrow;
    }
  }

  // Handle response
  dynamic _handleResponse(http.Response response) {
    if (response.statusCode == 200 || response.statusCode == 201) {
      return jsonDecode(response.body);
    } else if (response.statusCode == 401) {
      throw Exception('Unauthorized - Please login again');
    } else if (response.statusCode == 404) {
      throw Exception('Resource not found');
    } else {
      throw Exception('Error: ${response.statusCode} - ${response.body}');
    }
  }
}
```

---

## 🔟 Environment Configuration

**File:** `coopvest_africa_backend/.env`

```env
APP_NAME="Coopvest Africa"
APP_ENV=production
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=false
APP_URL=https://api.coopvest.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coopvest
DB_USERNAME=root
DB_PASSWORD=

FIREBASE_PROJECT_ID=your-project-id
FIREBASE_CREDENTIALS_PATH=/path/to/firebase-credentials.json

CORS_ALLOWED_ORIGINS=https://coopvest.com,https://app.coopvest.com
```

---

## ✨ Usage Examples

### React - Login Component
```typescript
import { useAuth } from '@/context/AuthContext';
import { useNavigate } from 'react-router-dom';

export function LoginPage() {
  const { login, error } = useAuth();
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await login(email, password);
      navigate('/dashboard');
    } catch (err) {
      console.error('Login failed:', err);
    }
  };

  return (
    <form onSubmit={handleLogin}>
      <input
        type="email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        placeholder="Email"
      />
      <input
        type="password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        placeholder="Password"
      />
      {error && <p className="text-red-500">{error}</p>}
      <button type="submit">Login</button>
    </form>
  );
}
```

### Flutter - Login Screen
```dart
import 'package:flutter/material.dart';
import 'services/auth_service.dart';

class LoginScreen extends StatefulWidget {
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _authService = AuthService();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Login')),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: _emailController,
              decoration: InputDecoration(labelText: 'Email'),
            ),
            TextField(
              controller: _passwordController,
              decoration: InputDecoration(labelText: 'Password'),
              obscureText: true,
            ),
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: _isLoading ? null : _handleLogin,
              child: _isLoading
                  ? CircularProgressIndicator()
                  : Text('Login'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _handleLogin() async {
    setState(() => _isLoading = true);
    try {
      await _authService.signIn(
        email: _emailController.text,
        password: _passwordController.text,
      );
      Navigator.of(context).pushReplacementNamed('/dashboard');
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Login failed: $e')),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }
}
```

---

**All templates are production-ready. Customize as needed for your specific requirements!**
