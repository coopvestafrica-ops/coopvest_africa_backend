<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;

class FirebaseSync
{
    /**
     * Firebase instance
     */
    protected $firebase;

    /**
     * Create a new middleware instance.
     */
    public function __construct()
    {
        try {
            $credentialsPath = config('firebase.credentials_path');
            
            if (!file_exists($credentialsPath)) {
                throw new \Exception("Firebase credentials file not found at: {$credentialsPath}");
            }

            $this->firebase = (new Factory)
                ->withServiceAccount($credentialsPath)
                ->create();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed in sync middleware: ' . $e->getMessage());
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware if not enabled
        if (!config('firebase.middleware.sync.enabled')) {
            return $next($request);
        }

        // Skip if user sync is disabled
        if (!config('firebase.user_sync.enabled')) {
            return $next($request);
        }

        // Get Firebase UID from the request (set by FirebaseAuth middleware)
        $firebaseUid = $request->attributes->get('firebase_uid');
        $firebaseToken = $request->attributes->get('firebase_token');

        if ($firebaseUid && $firebaseToken) {
            try {
                $this->syncUserWithFirebase($firebaseUid, $firebaseToken);
            } catch (\Exception $e) {
                Log::error('Firebase user sync failed: ' . $e->getMessage());
                // Don't fail the request if sync fails
            }
        }

        return $next($request);
    }

    /**
     * Synchronize user data with Firebase
     *
     * @param string $firebaseUid
     * @param mixed $firebaseToken
     * @return void
     */
    protected function syncUserWithFirebase(string $firebaseUid, $firebaseToken): void
    {
        try {
            if (!$this->firebase) {
                throw new \Exception('Firebase not initialized');
            }

            $auth = $this->firebase->getAuth();
            $firebaseUser = $auth->getUser($firebaseUid);

            // Check if user exists in database
            $user = User::where('firebase_uid', $firebaseUid)->first();

            if (!$user && config('firebase.user_sync.auto_create_users')) {
                // Create new user from Firebase data
                $user = $this->createUserFromFirebase($firebaseUser);
                Log::info('Created new user from Firebase: ' . $firebaseUid);
            } elseif ($user) {
                // Update existing user
                $this->updateUserFromFirebase($user, $firebaseUser);
                Log::info('Updated user from Firebase: ' . $firebaseUid);
            }

            // Sync custom claims if enabled
            if (config('firebase.user_sync.sync_custom_claims') && $user) {
                $this->syncCustomClaims($user, $firebaseUser);
            }

            // Sync metadata if enabled
            if (config('firebase.user_sync.sync_metadata') && $user) {
                $this->syncMetadata($user, $firebaseUser);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing user with Firebase: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new user from Firebase user data
     *
     * @param mixed $firebaseUser
     * @return User
     */
    protected function createUserFromFirebase($firebaseUser): User
    {
        $user = new User();
        $user->firebase_uid = $firebaseUser->uid;
        $user->email = $firebaseUser->email ?? null;
        $user->name = $firebaseUser->displayName ?? 'Firebase User';
        $user->phone_number = $firebaseUser->phoneNumber ?? null;
        $user->firebase_email_verified = $firebaseUser->emailVerified ?? false;
        $user->firebase_disabled = $firebaseUser->disabled ?? false;
        $user->firebase_metadata = json_encode([
            'created_at' => $firebaseUser->metadata->createdAt ?? null,
            'last_sign_in' => $firebaseUser->metadata->lastSignInAt ?? null,
        ]);
        $user->save();

        return $user;
    }

    /**
     * Update an existing user from Firebase user data
     *
     * @param User $user
     * @param mixed $firebaseUser
     * @return void
     */
    protected function updateUserFromFirebase(User $user, $firebaseUser): void
    {
        $user->firebase_uid = $firebaseUser->uid;
        $user->email = $firebaseUser->email ?? $user->email;
        $user->name = $firebaseUser->displayName ?? $user->name;
        $user->phone_number = $firebaseUser->phoneNumber ?? $user->phone_number;
        $user->firebase_email_verified = $firebaseUser->emailVerified ?? $user->firebase_email_verified;
        $user->firebase_disabled = $firebaseUser->disabled ?? $user->firebase_disabled;
        $user->firebase_metadata = json_encode([
            'created_at' => $firebaseUser->metadata->createdAt ?? null,
            'last_sign_in' => $firebaseUser->metadata->lastSignInAt ?? null,
        ]);
        $user->save();
    }

    /**
     * Sync custom claims from Firebase to user
     *
     * @param User $user
     * @param mixed $firebaseUser
     * @return void
     */
    protected function syncCustomClaims(User $user, $firebaseUser): void
    {
        try {
            $customClaims = $firebaseUser->customClaims ?? [];
            
            if (!empty($customClaims)) {
                $user->firebase_custom_claims = json_encode($customClaims);
                $user->save();
                
                Log::info('Synced custom claims for user: ' . $user->id);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing custom claims: ' . $e->getMessage());
        }
    }

    /**
     * Sync metadata from Firebase to user
     *
     * @param User $user
     * @param mixed $firebaseUser
     * @return void
     */
    protected function syncMetadata(User $user, $firebaseUser): void
    {
        try {
            $metadata = [
                'uid' => $firebaseUser->uid,
                'email' => $firebaseUser->email,
                'email_verified' => $firebaseUser->emailVerified,
                'display_name' => $firebaseUser->displayName,
                'phone_number' => $firebaseUser->phoneNumber,
                'photo_url' => $firebaseUser->photoUrl,
                'disabled' => $firebaseUser->disabled,
                'created_at' => $firebaseUser->metadata->createdAt ?? null,
                'last_sign_in' => $firebaseUser->metadata->lastSignInAt ?? null,
            ];

            $user->firebase_metadata = json_encode($metadata);
            $user->save();

            Log::info('Synced metadata for user: ' . $user->id);
        } catch (\Exception $e) {
            Log::error('Error syncing metadata: ' . $e->getMessage());
        }
    }
}
