<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\AuthException;
use Illuminate\Support\Facades\Log;

class UserSyncController extends Controller
{
    /**
     * Firebase instance
     */
    protected $firebase;

    /**
     * Create a new controller instance.
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
            Log::error('Firebase initialization failed in UserSyncController: ' . $e->getMessage());
        }
    }

    /**
     * Sync a user with Firebase
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            // Get Firebase UID from request or authenticated user
            $firebaseUid = $request->input('firebase_uid') ?? $request->attributes->get('firebase_uid');

            if (!$firebaseUid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Firebase UID is required',
                    'error' => 'MISSING_FIREBASE_UID'
                ], 400);
            }

            if (!$this->firebase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Firebase service not initialized',
                    'error' => 'FIREBASE_NOT_INITIALIZED'
                ], 500);
            }

            $auth = $this->firebase->getAuth();
            $firebaseUser = $auth->getUser($firebaseUid);

            // Check if user exists in database
            $user = User::where('firebase_uid', $firebaseUid)->first();

            if (!$user && config('firebase.user_sync.auto_create_users')) {
                // Create new user from Firebase data
                $user = $this->createUserFromFirebase($firebaseUser);
                $action = 'created';
            } elseif ($user) {
                // Update existing user
                $this->updateUserFromFirebase($user, $firebaseUser);
                $action = 'updated';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found and auto-creation is disabled',
                    'error' => 'USER_NOT_FOUND'
                ], 404);
            }

            // Sync custom claims if enabled
            if (config('firebase.user_sync.sync_custom_claims') && $user) {
                $this->syncCustomClaims($user, $firebaseUser);
            }

            // Sync metadata if enabled
            if (config('firebase.user_sync.sync_metadata') && $user) {
                $this->syncMetadata($user, $firebaseUser);
            }

            Log::info("User {$action} via sync endpoint: {$firebaseUid}");

            return response()->json([
                'success' => true,
                'message' => "User {$action} successfully",
                'data' => [
                    'user_id' => $user->id,
                    'firebase_uid' => $user->firebase_uid,
                    'email' => $user->email,
                    'name' => $user->name,
                    'action' => $action,
                ]
            ], 200);
        } catch (AuthException $e) {
            Log::error('Firebase auth exception in sync: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Firebase authentication error',
                'error' => 'AUTH_ERROR',
                'details' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error syncing user: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error syncing user',
                'error' => 'SYNC_ERROR',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user sync status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $firebaseUid = $request->input('firebase_uid') ?? $request->attributes->get('firebase_uid');

            if (!$firebaseUid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Firebase UID is required',
                    'error' => 'MISSING_FIREBASE_UID'
                ], 400);
            }

            $user = User::where('firebase_uid', $firebaseUid)->first();

            if (!$user) {
                return response()->json([
                    'success' => true,
                    'message' => 'User not synced',
                    'data' => [
                        'synced' => false,
                        'firebase_uid' => $firebaseUid,
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'User sync status retrieved',
                'data' => [
                    'synced' => true,
                    'user_id' => $user->id,
                    'firebase_uid' => $user->firebase_uid,
                    'email' => $user->email,
                    'name' => $user->name,
                    'email_verified' => $user->firebase_email_verified,
                    'disabled' => $user->firebase_disabled,
                    'synced_at' => $user->updated_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error getting user sync status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving sync status',
                'error' => 'STATUS_ERROR',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk sync users from Firebase
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkSync(Request $request): JsonResponse
    {
        try {
            $firebaseUids = $request->input('firebase_uids', []);

            if (empty($firebaseUids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Firebase UIDs array is required',
                    'error' => 'MISSING_FIREBASE_UIDS'
                ], 400);
            }

            if (!$this->firebase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Firebase service not initialized',
                    'error' => 'FIREBASE_NOT_INITIALIZED'
                ], 500);
            }

            $auth = $this->firebase->getAuth();
            $results = [
                'total' => count($firebaseUids),
                'synced' => 0,
                'failed' => 0,
                'errors' => []
            ];

            foreach ($firebaseUids as $firebaseUid) {
                try {
                    $firebaseUser = $auth->getUser($firebaseUid);
                    $user = User::where('firebase_uid', $firebaseUid)->first();

                    if (!$user && config('firebase.user_sync.auto_create_users')) {
                        $user = $this->createUserFromFirebase($firebaseUser);
                    } elseif ($user) {
                        $this->updateUserFromFirebase($user, $firebaseUser);
                    }

                    if ($user) {
                        if (config('firebase.user_sync.sync_custom_claims')) {
                            $this->syncCustomClaims($user, $firebaseUser);
                        }
                        if (config('firebase.user_sync.sync_metadata')) {
                            $this->syncMetadata($user, $firebaseUser);
                        }
                        $results['synced']++;
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'firebase_uid' => $firebaseUid,
                        'error' => $e->getMessage()
                    ];
                    Log::error("Failed to sync user {$firebaseUid}: " . $e->getMessage());
                }
            }

            Log::info("Bulk sync completed: {$results['synced']} synced, {$results['failed']} failed");

            return response()->json([
                'success' => true,
                'message' => 'Bulk sync completed',
                'data' => $results
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in bulk sync: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error in bulk sync',
                'error' => 'BULK_SYNC_ERROR',
                'details' => $e->getMessage()
            ], 500);
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
        } catch (\Exception $e) {
            Log::error('Error syncing metadata: ' . $e->getMessage());
        }
    }
}
