<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\AuthException;
use Illuminate\Support\Facades\Log;

class FirebaseAuth
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
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            if (config('firebase.middleware.auth.throw_exceptions')) {
                throw $e;
            }
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
        if (!config('firebase.middleware.auth.enabled')) {
            return $next($request);
        }

        // Get the authorization header
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization header missing',
                'error' => 'MISSING_AUTH_HEADER'
            ], 401);
        }

        // Extract the token from "Bearer <token>"
        $parts = explode(' ', $authHeader);
        if (count($parts) !== 2 || $parts[0] !== 'Bearer') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authorization header format',
                'error' => 'INVALID_AUTH_FORMAT'
            ], 401);
        }

        $token = $parts[1];

        try {
            // Verify the ID token
            if (!$this->firebase) {
                throw new \Exception('Firebase not initialized');
            }

            $auth = $this->firebase->getAuth();
            
            if (!config('firebase.admin_sdk.verify_id_token')) {
                return $next($request);
            }

            $verifiedIdToken = $auth->verifyIdToken($token);
            $uid = $verifiedIdToken->claims()->get('sub');

            // Store the user ID in the request for later use
            $request->attributes->set('firebase_uid', $uid);
            $request->attributes->set('firebase_token', $verifiedIdToken);

            Log::info('Firebase token verified for user: ' . $uid);

            return $next($request);
        } catch (FailedToVerifyToken $e) {
            Log::warning('Firebase token verification failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
                'error' => 'INVALID_TOKEN'
            ], 401);
        } catch (AuthException $e) {
            Log::error('Firebase auth exception: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Authentication service error',
                'error' => 'AUTH_ERROR'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Firebase middleware error: ' . $e->getMessage());
            
            if (config('firebase.middleware.auth.throw_exceptions')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => 'MIDDLEWARE_ERROR'
                ], 500);
            }

            return $next($request);
        }
    }
}
