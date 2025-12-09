<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TokenVerificationService
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Verify and validate token
     *
     * @param string $token
     * @return array|null
     */
    public function verifyToken(string $token): ?array
    {
        try {
            // Verify Firebase token
            $verifiedToken = $this->firebaseService->verifyIdToken($token);
            
            if (!$verifiedToken) {
                return null;
            }

            $uid = $verifiedToken->claims()->get('sub');
            $email = $verifiedToken->claims()->get('email');

            // Get or sync user
            $user = User::where('firebase_uid', $uid)->first();
            
            if (!$user && config('firebase.user_sync.auto_create_users')) {
                $user = $this->syncUserFromFirebase($uid, $email);
            }

            if (!$user) {
                Log::warning('User not found for Firebase UID', ['uid' => $uid]);
                return null;
            }

            // Check if user is active
            if (!$user->is_active || $user->account_status !== 'active') {
                Log::warning('User account is not active', ['user_id' => $user->id]);
                return null;
            }

            return [
                'user' => $user,
                'token' => $verifiedToken,
                'uid' => $uid,
                'email' => $email,
                'verified_at' => Carbon::now(),
            ];
        } catch (\Exception $e) {
            Log::error('Token verification error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    /**
     * Sync user from Firebase
     *
     * @param string $uid
     * @param string|null $email
     * @return User|null
     */
    public function syncUserFromFirebase(string $uid, ?string $email = null): ?User
    {
        try {
            $firebaseUser = $this->firebaseService->getUser($uid);
            
            if (!$firebaseUser) {
                return null;
            }

            // Check if user already exists by email
            if ($email) {
                $user = User::where('email', $email)->first();
                
                if ($user) {
                    // Update existing user with Firebase UID
                    $user->update([
                        'firebase_uid' => $uid,
                        'firebase_email' => $firebaseUser->email,
                        'firebase_synced_at' => Carbon::now(),
                        'firebase_metadata' => json_encode([
                            'display_name' => $firebaseUser->displayName,
                            'phone_number' => $firebaseUser->phoneNumber,
                            'photo_url' => $firebaseUser->photoUrl,
                            'email_verified' => $firebaseUser->emailVerified,
                            'disabled' => $firebaseUser->disabled,
                        ]),
                    ]);

                    Log::info('User synced with Firebase UID', [
                        'user_id' => $user->id,
                        'uid' => $uid,
                    ]);

                    return $user;
                }
            }

            // Create new user from Firebase
            $user = User::create([
                'firebase_uid' => $uid,
                'email' => $firebaseUser->email,
                'firebase_email' => $firebaseUser->email,
                'first_name' => $firebaseUser->displayName ?? 'Firebase',
                'last_name' => 'User',
                'phone' => $firebaseUser->phoneNumber ?? 'N/A',
                'country' => 'Unknown',
                'password' => bcrypt(str_random(32)),
                'role' => 'member',
                'is_active' => !$firebaseUser->disabled,
                'firebase_synced_at' => Carbon::now(),
                'firebase_metadata' => json_encode([
                    'display_name' => $firebaseUser->displayName,
                    'phone_number' => $firebaseUser->phoneNumber,
                    'photo_url' => $firebaseUser->photoUrl,
                    'email_verified' => $firebaseUser->emailVerified,
                    'disabled' => $firebaseUser->disabled,
                ]),
            ]);

            Log::info('New user created from Firebase', [
                'user_id' => $user->id,
                'uid' => $uid,
                'email' => $firebaseUser->email,
            ]);

            return $user;
        } catch (\Exception $e) {
            Log::error('Firebase user sync failed', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if token is expired
     *
     * @param string $token
     * @return bool
     */
    public function isTokenExpired(string $token): bool
    {
        try {
            $verifiedToken = $this->firebaseService->verifyIdToken($token);
            
            if (!$verifiedToken) {
                return true;
            }

            $expiresAt = $verifiedToken->claims()->get('exp');
            return Carbon::createFromTimestamp($expiresAt)->isPast();
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Get token expiration time
     *
     * @param string $token
     * @return Carbon|null
     */
    public function getTokenExpirationTime(string $token): ?Carbon
    {
        try {
            $verifiedToken = $this->firebaseService->verifyIdToken($token);
            
            if (!$verifiedToken) {
                return null;
            }

            $expiresAt = $verifiedToken->claims()->get('exp');
            return Carbon::createFromTimestamp($expiresAt);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get token claims
     *
     * @param string $token
     * @return array|null
     */
    public function getTokenClaims(string $token): ?array
    {
        try {
            $verifiedToken = $this->firebaseService->verifyIdToken($token);
            
            if (!$verifiedToken) {
                return null;
            }

            return $verifiedToken->claims()->all();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate token format
     *
     * @param string $token
     * @return bool
     */
    public function isValidTokenFormat(string $token): bool
    {
        // Firebase tokens are JWT format: header.payload.signature
        $parts = explode('.', $token);
        return count($parts) === 3 && !empty($parts[0]) && !empty($parts[1]) && !empty($parts[2]);
    }

    /**
     * Extract user ID from token
     *
     * @param string $token
     * @return string|null
     */
    public function extractUserIdFromToken(string $token): ?string
    {
        try {
            $verifiedToken = $this->firebaseService->verifyIdToken($token);
            
            if (!$verifiedToken) {
                return null;
            }

            return $verifiedToken->claims()->get('sub');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract email from token
     *
     * @param string $token
     * @return string|null
     */
    public function extractEmailFromToken(string $token): ?string
    {
        try {
            $verifiedToken = $this->firebaseService->verifyIdToken($token);
            
            if (!$verifiedToken) {
                return null;
            }

            return $verifiedToken->claims()->get('email');
        } catch (\Exception $e) {
            return null;
        }
    }
}
