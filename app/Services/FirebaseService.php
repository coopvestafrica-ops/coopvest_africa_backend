<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Database;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\InvalidIdToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $auth;
    protected $database;
    protected $factory;

    public function __construct()
    {
        try {
            $credentialsPath = config('firebase.credentials_path');
            
            if (!file_exists($credentialsPath)) {
                throw new \Exception("Firebase credentials file not found at: {$credentialsPath}");
            }

            $this->factory = (new Factory)
                ->withServiceAccount($credentialsPath)
                ->withDatabaseUri(config('firebase.database_url'));

            $this->auth = $this->factory->createAuth();
            $this->database = $this->factory->createDatabase();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify Firebase ID token
     *
     * @param string $token
     * @return \Kreait\Firebase\Auth\Token|null
     */
    public function verifyIdToken(string $token)
    {
        try {
            // Check cache first if enabled
            if (config('firebase.admin_sdk.cache_tokens')) {
                $cacheKey = 'firebase_token_' . hash('sha256', $token);
                $cached = Cache::get($cacheKey);
                
                if ($cached) {
                    return $cached;
                }
            }

            $verifiedToken = $this->auth->verifyIdToken($token);

            // Cache the token if enabled
            if (config('firebase.admin_sdk.cache_tokens')) {
                $ttl = config('firebase.admin_sdk.token_cache_ttl', 3600);
                Cache::put($cacheKey, $verifiedToken, $ttl);
            }

            return $verifiedToken;
        } catch (FailedToVerifyToken | InvalidIdToken $e) {
            Log::warning('Firebase token verification failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Firebase token verification error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    /**
     * Get Firebase user by UID
     *
     * @param string $uid
     * @return \Kreait\Firebase\Auth\User|null
     */
    public function getUser(string $uid)
    {
        try {
            return $this->auth->getUser($uid);
        } catch (\Exception $e) {
            Log::warning('Firebase get user failed', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get Firebase user by email
     *
     * @param string $email
     * @return \Kreait\Firebase\Auth\User|null
     */
    public function getUserByEmail(string $email)
    {
        try {
            return $this->auth->getUserByEmail($email);
        } catch (\Exception $e) {
            Log::warning('Firebase get user by email failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create Firebase user
     *
     * @param array $data
     * @return \Kreait\Firebase\Auth\User|null
     */
    public function createUser(array $data)
    {
        try {
            $userProperties = [];
            
            if (isset($data['email'])) {
                $userProperties['email'] = $data['email'];
            }
            
            if (isset($data['password'])) {
                $userProperties['password'] = $data['password'];
            }
            
            if (isset($data['display_name'])) {
                $userProperties['displayName'] = $data['display_name'];
            }
            
            if (isset($data['phone_number'])) {
                $userProperties['phoneNumber'] = $data['phone_number'];
            }
            
            if (isset($data['photo_url'])) {
                $userProperties['photoUrl'] = $data['photo_url'];
            }

            $createdUser = $this->auth->createUser($userProperties);
            
            Log::info('Firebase user created', [
                'uid' => $createdUser->uid,
                'email' => $createdUser->email,
            ]);

            return $createdUser;
        } catch (\Exception $e) {
            Log::error('Firebase create user failed', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update Firebase user
     *
     * @param string $uid
     * @param array $data
     * @return \Kreait\Firebase\Auth\User|null
     */
    public function updateUser(string $uid, array $data)
    {
        try {
            $userProperties = [];
            
            if (isset($data['email'])) {
                $userProperties['email'] = $data['email'];
            }
            
            if (isset($data['password'])) {
                $userProperties['password'] = $data['password'];
            }
            
            if (isset($data['display_name'])) {
                $userProperties['displayName'] = $data['display_name'];
            }
            
            if (isset($data['phone_number'])) {
                $userProperties['phoneNumber'] = $data['phone_number'];
            }
            
            if (isset($data['photo_url'])) {
                $userProperties['photoUrl'] = $data['photo_url'];
            }

            $updatedUser = $this->auth->updateUser($uid, $userProperties);
            
            Log::info('Firebase user updated', [
                'uid' => $uid,
            ]);

            return $updatedUser;
        } catch (\Exception $e) {
            Log::error('Firebase update user failed', [
                'uid' => $uid,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Delete Firebase user
     *
     * @param string $uid
     * @return bool
     */
    public function deleteUser(string $uid): bool
    {
        try {
            $this->auth->deleteUser($uid);
            
            Log::info('Firebase user deleted', [
                'uid' => $uid,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Firebase delete user failed', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Set custom claims for user
     *
     * @param string $uid
     * @param array $claims
     * @return bool
     */
    public function setCustomClaims(string $uid, array $claims): bool
    {
        try {
            $this->auth->setCustomUserClaims($uid, $claims);
            
            Log::info('Firebase custom claims set', [
                'uid' => $uid,
                'claims' => $claims,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Firebase set custom claims failed', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get Auth instance
     *
     * @return Auth
     */
    public function getAuthInstance(): Auth
    {
        return $this->auth;
    }

    /**
     * Get Database instance
     *
     * @return Database
     */
    public function getDatabaseInstance(): Database
    {
        return $this->database;
    }
}
