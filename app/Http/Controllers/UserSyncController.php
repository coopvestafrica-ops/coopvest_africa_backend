<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FirebaseService;
use App\Services\TokenVerificationService;
use App\Helpers\ApiResponse;
use App\Exceptions\UserSyncException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UserSyncController extends Controller
{
    protected $firebaseService;
    protected $tokenVerificationService;

    public function __construct(
        FirebaseService $firebaseService,
        TokenVerificationService $tokenVerificationService
    ) {
        $this->firebaseService = $firebaseService;
        $this->tokenVerificationService = $tokenVerificationService;
    }

    /**
     * Sync user from Firebase
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
                return ApiResponse::error(
                    'Firebase UID is required',
                    'MISSING_FIREBASE_UID',
                    400
                );
            }

            // Get Firebase user
            $firebaseUser = $this->firebaseService->getUser($firebaseUid);

            if (!$firebaseUser) {
                return ApiResponse::error(
                    'Firebase user not found',
                    'FIREBASE_USER_NOT_FOUND',
                    404
                );
            }

            // Check if user exists in database
            $user = User::where('firebase_uid', $firebaseUid)->first();

            if ($user) {
                // Update existing user
                $user = $this->updateUserFromFirebase($user, $firebaseUser);
                $action = 'updated';
            } else {
                // Create new user
                $user = $this->createUserFromFirebase($firebaseUser);
                $action = 'created';
            }

            Log::info("User {$action} from Firebase sync", [
                'user_id' => $user->id,
                'firebase_uid' => $firebaseUid,
                'action' => $action,
            ]);

            return ApiResponse::success(
                $user->only(['id', 'firebase_uid', 'email', 'first_name', 'last_name', 'is_active']),
                "User {$action} successfully",
                200
            );
        } catch (UserSyncException $e) {
            Log::warning('User sync exception', [
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
            ]);

            return ApiResponse::error(
                $e->getMessage(),
                $e->getErrorCode(),
                422
            );
        } catch (\Exception $e) {
            Log::error('User sync error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return ApiResponse::serverError(
                'Failed to sync user',
                'USER_SYNC_ERROR'
            );
        }
    }

    /**
     * Create user from Firebase
     *
     * @param mixed $firebaseUser
     * @return User
     * @throws UserSyncException
     */
    protected function createUserFromFirebase($firebaseUser): User
    {
        try {
            // Check if user already exists by email
            if ($firebaseUser->email) {
                $existingUser = User::where('email', $firebaseUser->email)->first();
                
                if ($existingUser) {
                    throw new UserSyncException(
                        'User with this email already exists',
                        'USER_EMAIL_EXISTS'
                    );
                }
            }

            $user = User::create([
                'firebase_uid' => $firebaseUser->uid,
                'email' => $firebaseUser->email ?? 'firebase-' . $firebaseUser->uid . '@coopvest.local',
                'firebase_email' => $firebaseUser->email,
                'first_name' => $this->extractFirstName($firebaseUser->displayName),
                'last_name' => $this->extractLastName($firebaseUser->displayName),
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
                    'created_at' => $firebaseUser->metadata->createdAt ?? null,
                    'last_sign_in' => $firebaseUser->metadata->lastSignInAt ?? null,
                ]),
            ]);

            Log::info('New user created from Firebase', [
                'user_id' => $user->id,
                'firebase_uid' => $firebaseUser->uid,
                'email' => $firebaseUser->email,
            ]);

            return $user;
        } catch (UserSyncException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error creating user from Firebase', [
                'firebase_uid' => $firebaseUser->uid,
                'error' => $e->getMessage(),
            ]);

            throw new UserSyncException(
                'Failed to create user from Firebase',
                'CREATE_USER_FAILED'
            );
        }
    }

    /**
     * Update user from Firebase
     *
     * @param User $user
     * @param mixed $firebaseUser
     * @return User
     * @throws UserSyncException
     */
    protected function updateUserFromFirebase(User $user, $firebaseUser): User
    {
        try {
            $updateData = [
                'firebase_uid' => $firebaseUser->uid,
                'firebase_email' => $firebaseUser->email,
                'firebase_synced_at' => Carbon::now(),
                'firebase_metadata' => json_encode([
                    'display_name' => $firebaseUser->displayName,
                    'phone_number' => $firebaseUser->phoneNumber,
                    'photo_url' => $firebaseUser->photoUrl,
                    'email_verified' => $firebaseUser->emailVerified,
                    'disabled' => $firebaseUser->disabled,
                    'created_at' => $firebaseUser->metadata->createdAt ?? null,
                    'last_sign_in' => $firebaseUser->metadata->lastSignInAt ?? null,
                ]),
            ];

            // Update email if it changed
            if ($firebaseUser->email && $firebaseUser->email !== $user->email) {
                // Check if new email is already in use
                $emailExists = User::where('email', $firebaseUser->email)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($emailExists) {
                    throw new UserSyncException(
                        'Email is already in use by another user',
                        'EMAIL_ALREADY_IN_USE'
                    );
                }

                $updateData['email'] = $firebaseUser->email;
            }

            // Update display name if provided
            if ($firebaseUser->displayName) {
                $updateData['first_name'] = $this->extractFirstName($firebaseUser->displayName);
                $updateData['last_name'] = $this->extractLastName($firebaseUser->displayName);
            }

            // Update phone if provided
            if ($firebaseUser->phoneNumber) {
                $updateData['phone'] = $firebaseUser->phoneNumber;
            }

            // Update active status based on Firebase disabled flag
            $updateData['is_active'] = !$firebaseUser->disabled;

            $user->update($updateData);

            Log::info('User updated from Firebase', [
                'user_id' => $user->id,
                'firebase_uid' => $firebaseUser->uid,
            ]);

            return $user;
        } catch (UserSyncException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error updating user from Firebase', [
                'user_id' => $user->id,
                'firebase_uid' => $firebaseUser->uid,
                'error' => $e->getMessage(),
            ]);

            throw new UserSyncException(
                'Failed to update user from Firebase',
                'UPDATE_USER_FAILED'
            );
        }
    }

    /**
     * Extract first name from display name
     *
     * @param string|null $displayName
     * @return string
     */
    protected function extractFirstName(?string $displayName): string
    {
        if (!$displayName) {
            return 'User';
        }

        $parts = explode(' ', trim($displayName));
        return $parts[0] ?? 'User';
    }

    /**
     * Extract last name from display name
     *
     * @param string|null $displayName
     * @return string
     */
    protected function extractLastName(?string $displayName): string
    {
        if (!$displayName) {
            return 'Account';
        }

        $parts = explode(' ', trim($displayName));
        return isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : 'Account';
    }

    /**
     * Get sync status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $firebaseUid = $request->input('firebase_uid') ?? $request->attributes->get('firebase_uid');

            if (!$firebaseUid) {
                return ApiResponse::error(
                    'Firebase UID is required',
                    'MISSING_FIREBASE_UID',
                    400
                );
            }

            $user = User::where('firebase_uid', $firebaseUid)->first();

            if (!$user) {
                return ApiResponse::notFound('User not found');
            }

            return ApiResponse::success([
                'user_id' => $user->id,
                'firebase_uid' => $user->firebase_uid,
                'email' => $user->email,
                'is_synced' => !is_null($user->firebase_synced_at),
                'last_synced_at' => $user->firebase_synced_at,
                'is_active' => $user->is_active,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sync status', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::serverError(
                'Failed to get sync status',
                'SYNC_STATUS_ERROR'
            );
        }
    }

    /**
     * Bulk sync users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkSync(Request $request): JsonResponse
    {
        try {
            $firebaseUids = $request->input('firebase_uids', []);

            if (empty($firebaseUids)) {
                return ApiResponse::error(
                    'Firebase UIDs array is required',
                    'MISSING_FIREBASE_UIDS',
                    400
                );
            }

            if (count($firebaseUids) > 100) {
                return ApiResponse::error(
                    'Maximum 100 users can be synced at once',
                    'TOO_MANY_USERS',
                    400
                );
            }

            $results = [
                'successful' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            foreach ($firebaseUids as $uid) {
                try {
                    $firebaseUser = $this->firebaseService->getUser($uid);

                    if (!$firebaseUser) {
                        $results['failed']++;
                        $results['errors'][] = [
                            'uid' => $uid,
                            'error' => 'Firebase user not found',
                        ];
                        continue;
                    }

                    $user = User::where('firebase_uid', $uid)->first();

                    if ($user) {
                        $this->updateUserFromFirebase($user, $firebaseUser);
                    } else {
                        $this->createUserFromFirebase($firebaseUser);
                    }

                    $results['successful']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'uid' => $uid,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::info('Bulk user sync completed', $results);

            return ApiResponse::success($results, 'Bulk sync completed');
        } catch (\Exception $e) {
            Log::error('Bulk sync error', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::serverError(
                'Failed to perform bulk sync',
                'BULK_SYNC_ERROR'
            );
        }
    }
}
